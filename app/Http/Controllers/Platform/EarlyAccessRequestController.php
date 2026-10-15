<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\EarlyAccessRequest;
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

        return view('platform.early-access.index', [
            'requests' => EarlyAccessRequest::orderByDesc('id')->paginate(50),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizePlatformAdmin($request);

        $columns = ['id', 'created_at', 'school_name', 'contact_name', 'contact_role', 'email', 'phone', 'country', 'approx_students', 'consented_at'];

        return response()->streamDownload(function () use ($columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
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
                    }, $columns));
                }
            });
            fclose($out);
        }, 'early-access-requests.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizePlatformAdmin(Request $request): void
    {
        abort_unless($request->user() && (int) $request->user()->usergroup_id === 1, 403);
    }
}
