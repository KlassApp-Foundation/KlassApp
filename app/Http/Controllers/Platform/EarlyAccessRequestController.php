<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\EarlyAccessRequest;
use App\Models\SchoolInvite;
use App\Models\User;
use App\Services\SchoolInviteService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Early access requests list and CSV export. Platform admins only
 * (usergroup 1); school admins and everyone else get 403.
 */
class EarlyAccessRequestController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePlatformAdmin($request);

        $requests = EarlyAccessRequest::orderByDesc('id')->paginate(50);
        $emails = collect($requests->items())
            ->pluck('email')
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $invites = SchoolInvite::whereIn('email', $emails)
            ->orderByDesc('id')
            ->get()
            ->keyBy(fn (SchoolInvite $invite) => mb_strtolower($invite->email));

        $existingAccounts = array_fill_keys(EarlyAccessRequest::existingAccountEmails($emails), true);

        return view('platform.early-access.index', [
            'requests' => $requests,
            'invites' => $invites,
            'existingAccounts' => $existingAccounts,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizePlatformAdmin($request);

        $columns = ['id', 'created_at', 'school_name', 'contact_name', 'contact_role', 'email', 'phone', 'country', 'approx_students', 'consented_at'];

        return response()->streamDownload(function () use ($columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns, ',', '"', '\\');
            EarlyAccessRequest::orderBy('id')->chunk(500, function ($rows) use ($out, $columns) {
                foreach ($rows as $row) {
                    fputcsv($out, array_map(function ($column) use ($row) {
                        $value = $row->{$column};
                        if ($value instanceof \DateTimeInterface) {
                            return \Carbon\Carbon::instance($value)->utc()->format('Y-m-d\TH:i:s\Z');
                        }
                        $value = (string) $value;

                        // Neutralise spreadsheet formulas in user-supplied text.
                        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
                    }, $columns), ',', '"', '\\');
                }
            });
            fclose($out);
        }, 'early-access-requests.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function invite(Request $request, EarlyAccessRequest $earlyAccessRequest)
    {
        $this->authorizePlatformAdmin($request);

        $email = mb_strtolower(trim((string) $earlyAccessRequest->email));

        if (User::withTrashed()->where('email', $email)->exists()) {
            return back()->with('error', 'A user with '.$email.' already exists. This request cannot be invited again.');
        }

        $active = SchoolInvite::where('email', $email)
            ->whereNull('claimed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($active) {
            return back()->with('error', 'An active invite already exists for '.$email.'. Use resend instead.');
        }

        // authorizePlatformAdmin() already ran. The claim request never reaches this flag.
        ['invite' => $invite, 'token' => $token] = SchoolInviteService::issue(
            $earlyAccessRequest,
            $request->user(),
            $request->boolean('is_test'),
        );

        if (! SchoolInviteService::sendEmail($invite, $token)) {
            return back()
                ->with('error', 'The invite for '.$email.' was created, but the email could not be queued. Nothing was sent. Use Retry to send it again.')
                ->with('retry_invite_id', $invite->id);
        }

        return back()->with('success', 'Invite sent to '.$email.'.');
    }

    public function resend(Request $request, SchoolInvite $schoolInvite)
    {
        $this->authorizePlatformAdmin($request);

        if ($schoolInvite->isClaimed()) {
            return back()->with('error', 'This invite has already been claimed.');
        }

        ['invite' => $invite, 'token' => $token] = SchoolInviteService::reissue($schoolInvite);

        if (! SchoolInviteService::sendEmail($invite, $token)) {
            return back()
                ->with('error', 'The email to '.$invite->email.' could not be queued. Nothing was sent. Use Retry to send it again.')
                ->with('retry_invite_id', $invite->id);
        }

        return back()->with('success', 'A fresh invite was sent to '.$invite->email.'.');
    }

    public function revoke(Request $request, SchoolInvite $schoolInvite)
    {
        $this->authorizePlatformAdmin($request);

        if ($schoolInvite->isClaimed()) {
            return back()->with('error', 'A claimed invite cannot be revoked.');
        }

        SchoolInviteService::revoke($schoolInvite);

        return back()->with('success', 'Invite revoked for '.$schoolInvite->email.'.');
    }

    private function authorizePlatformAdmin(Request $request): void
    {
        abort_unless($request->user() && (int) $request->user()->usergroup_id === 1, 403);
    }
}
