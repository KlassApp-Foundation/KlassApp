<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\EarlyAccessRequest;
use App\Models\User;
use App\Notifications\EarlyAccessRequestedNotification;
use App\Support\PhoneCountry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EarlyAccessController extends Controller
{
    /**
     * Friendly, inline-only validation messages (no developer field names).
     */
    public const MESSAGES = [
        'school_name.required' => 'Please tell us the name of your school.',
        'school_name.max' => 'Please shorten the school name to 255 characters or fewer.',
        'contact_name.required' => 'Please tell us your name.',
        'contact_name.max' => 'Please shorten your name to 255 characters or fewer.',
        'contact_role.required' => 'Please tell us your role at the school, for example head teacher.',
        'contact_role.max' => 'Please shorten your role to 120 characters or fewer.',
        'email.required' => 'Please enter your email address so we can reply.',
        'email.email' => 'Please enter a valid email address, like name@example.com.',
        'email.max' => 'Please use an email address of 255 characters or fewer.',
        'phone.required' => 'Please enter a phone number we can reach you on.',
        'phone.max' => 'Please shorten the phone number to 50 characters or fewer.',
        'country.required' => 'Please choose your country.',
        'country.in' => 'Please choose your country from the list.',
        'approx_students.required' => 'Please tell us roughly how many students you have.',
        'approx_students.integer' => 'Please enter the number of students as a whole number, like 300.',
        'approx_students.min' => 'Please enter at least 1 student.',
        'approx_students.max' => 'Please enter a number of students up to 1,000,000.',
        'consent.accepted' => 'Please tick the box to let us store your details and contact you.',
    ];

    public function show(Request $request)
    {
        return response()
            ->view('early-access.show', [
                'launchAt' => \Carbon\CarbonImmutable::parse(config('klassapp.launch_at'))->utc(),
                'privacyUrl' => self::privacyNoticeUrl(),
                'countries' => self::countryOptions(),
                'submitted' => (bool) session('early_access_submitted'),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    public function store(Request $request)
    {
        // Spam trap: a person never fills this hidden field. Accept and drop.
        if ($request->filled('website')) {
            return redirect()->route('early-access.show')->with('early_access_submitted', true);
        }

        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_role' => 'required|string|max:120',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'country' => ['required', 'string', Rule::in(self::countryOptions())],
            'approx_students' => 'required|integer|min:1|max:1000000',
            'consent' => 'accepted',
        ], self::MESSAGES);

        $phone = PhoneCountry::normalise($validated['phone'], $validated['country']);
        if ($phone === null) {
            throw ValidationException::withMessages([
                'phone' => PhoneCountry::error($validated['country'], false),
            ]);
        }
        $validated['phone'] = $phone;

        unset($validated['consent']);
        $validated['consented_at'] = now();

        $earlyAccess = EarlyAccessRequest::create($validated);

        Log::info('Early access request received', ['early_access_request_id' => $earlyAccess->id]);

        try {
            $admins = User::where('usergroup_id', 1)->where('status', 'active')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new EarlyAccessRequestedNotification($earlyAccess));
            }
        } catch (\Throwable $e) {
            Log::warning('Early access request: admin notification failed', [
                'early_access_request_id' => $earlyAccess->id,
                'exception' => $e::class,
            ]);
        }

        return redirect()->route('early-access.show')->with('early_access_submitted', true);
    }

    /**
     * The same value the demo WhatsApp welcome links to (PRIVACY_NOTICE_URL).
     * Empty means no link; no default URL is invented.
     */
    public static function privacyNoticeUrl(): string
    {
        return trim((string) config('services.whatsapp.privacy_notice_url'));
    }

    /**
     * Country select options: the app's active countries table merged with
     * a small generic list (config klassapp.countries), sorted, "Other" last.
     *
     * @return list<string>
     */
    public static function countryOptions(): array
    {
        $names = (array) config('klassapp.countries', []);

        try {
            $names = array_merge($names, Country::query()->where('status', 1)->pluck('name')->all());
        } catch (\Throwable) {
            // No countries table (fresh install): the generic list is enough.
        }

        $names = array_values(array_unique(array_filter(array_map(
            fn ($name) => trim((string) $name),
            $names
        ), fn ($name) => $name !== '' && strcasecmp($name, 'Other') !== 0)));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        $names[] = 'Other';

        return $names;
    }
}
