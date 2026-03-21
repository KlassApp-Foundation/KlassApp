<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Auth\Events\Registered;
use App\Http\Requests\RegisterRequest;
use App\Traits\AuthenticationProcess;
use App\Mail\AdminNotifyNewUserMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Mail\EmailVerification;
use App\Models\AcademicYear;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\SchoolDetail;
use App\Models\Usergroup;
use Illuminate\Support\Str;
use App\Models\Userprofile;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Throwable;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */
    use AuthenticationProcess;
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Create a new school and user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $school = $this->createSchool($data);

            //$this->createSchoolDetails($school); //added in observer

            $user = $this->createSchoolAdmin($school, $data);

            $this->createUserProfile($user);

            $this->createSchoolSubscription($user);

            $this->createAcademicYear($school);

            return $user;
        });
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $registrationData = $this->prepareRegistrationData($request->all());

        try {
            event(new Registered($user = $this->create($registrationData)));

            // Non-critical side effects should not break registration success.
            $this->dispatchRegistrationSideEffects($user);
        } catch (Throwable $e) {
            $this->safeLogError('Registration Failed', [
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput(
                $request->except(['password', 'password_confirmation'])
            )->withErrors([
                'register' => 'We could not complete your registration right now. Please try again in a moment.',
            ]);
        }

        $this->guard()->login($user);

        if($user->email_verified == 0)
        {
            $this->createAuthentication($user,$request,'register');
            return redirect('/verifyotp');
        }

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    protected function guard()
    {
        return Auth::guard();
    }

    private function createSchool($data)
    {
        try
        {
            $schoolData = [
                'name'          =>  $data['school_name'],
                'email'         =>  $data['email'],
                'phone'         =>  $data['mobile_no'],
                'slug'          =>  Str::slug($data['school_name'], '-'),
                'status'        =>  "1",
                'created_at'    =>  Carbon::now(),
                'updated_at'    =>  Carbon::now(),
            ];

            if (Schema::hasColumn('schools', 'registration_country')) {
                $schoolData['registration_country'] = $data['country'] ?? null;
            }

            if (Schema::hasColumn('schools', 'student_size')) {
                $schoolData['student_size'] = $data['student_size'] ?? null;
            }

            $school = School::create($schoolData);

            Log::info('New School Created. School Id : '. $school->id. ' Name : '. $school->name );

            return $school;
        }
        catch(Exception $e)
        {
            $this->safeLogWarning('School creation failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function createUserProfile(User $user)
    {
        try
        {
            $userProfile = Userprofile::create([
                'user_id'           => $user->id,
                'school_id'         => $user->school_id,
                'usergroup_id'      => $user->usergroup_id,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now(),
            ]);

            Log::info('User Profile created '. $userProfile->id. ' for user' . $user->name);
        }
        catch(Exception $e)
        {
            $this->safeLogWarning('User profile creation failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function createSchoolSubscription(User $user)
    {
        try
        {
            $defaultPlanId = $this->resolveDefaultPlanId();

            if (!$defaultPlanId) {
                $defaultPlanId = $this->createFallbackPlan();
            }

            if (!$defaultPlanId) {
                $this->safeLogWarning('Skipping subscription creation: no plan available', [
                    'school_id' => $user->school_id,
                    'user_id' => $user->id,
                ]);

                return;
            }

            Subscription::create([
                'school_id'     =>  $user->school_id,
                'user_id'       =>  $user->id,
                'plan_id'       =>  $defaultPlanId,
                'status'        =>  "pending",
                'created_at'    =>  Carbon::now(),
                'updated_at'    =>  Carbon::now(),
            ]);

            Log::info('School subscription created for School Id '. $user->school_id, [
                'plan_id' => $defaultPlanId,
            ]);
        }
        catch(Throwable $e)
        {
            $this->safeLogWarning('Subscription creation failed', [
                'message' => $e->getMessage(),
                'school_id' => $user->school_id,
                'user_id' => $user->id,
            ]);
        }
    }

    private function resolveDefaultPlanId()
    {
        $planQuery = Plan::query();

        if (Schema::hasColumn('plans', 'is_active')) {
            $planQuery->where('is_active', 1);
        }

        if (Schema::hasColumn('plans', 'order')) {
            $planQuery->orderBy('order');
        } else {
            $planQuery->orderBy('id');
        }

        $activePlanId = $planQuery->value('id');

        if ($activePlanId) {
            return (int) $activePlanId;
        }

        $anyPlanId = Plan::query()
            ->orderBy('id')
            ->value('id');

        return $anyPlanId ? (int) $anyPlanId : null;
    }

    private function createFallbackPlan()
    {
        try {
            $existingPlanId = Plan::query()->orderBy('id')->value('id');
            if ($existingPlanId) {
                return (int) $existingPlanId;
            }

            $now = Carbon::now();

            $columns = Schema::getColumnListing('plans');
            $payload = [];

            $defaults = [
                'cycle' => 30,
                'name' => 'free',
                'display_name' => 'Free',
                'order' => 1,
                'is_active' => 1,
                'amount' => 0,
                'no_of_members' => 50,
                'no_of_events' => 5,
                'no_of_folders' => 5,
                'no_of_files' => 50,
                'no_of_videos' => 5,
                'no_of_audios' => 0,
                'no_of_bulletins' => 5,
                'no_of_groups' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($defaults as $column => $value) {
                if (in_array($column, $columns, true)) {
                    $payload[$column] = $value;
                }
            }

            $fallbackPlanId = DB::table('plans')->insertGetId($payload);

            $this->safeLogWarning('Created fallback free plan for registration flow', [
                'plan_id' => $fallbackPlanId,
            ]);

            return (int) $fallbackPlanId;
        } catch (Throwable $e) {
            $this->safeLogWarning('Failed to create fallback plan', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function createSchoolAdmin($school, $data)
    {
        try
        {
            $schoolAdminGroupId = $this->resolveSchoolAdminGroupId();

            if (!$schoolAdminGroupId) {
                throw new Exception('No usergroup is available for school administrator accounts.');
            }

            $userData = [
                'school_id'     => $school->id,
                'usergroup_id'  => $schoolAdminGroupId,
                'name'          => $data['name'],
                'email'         => $data['email'],
                'mobile_no'     => $data['mobile_no'],
                'password'      => Hash::make($data['password']),
                'email_verification_code' => Str::random(40),
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ];

            if (Schema::hasColumn('users', 'registration_role')) {
                $userData['registration_role'] = $data['role'] ?? null;
            }

            if (Schema::hasColumn('users', 'username')) {
                $userData['username'] = $this->generateUsernameFromName($data['name']);
            }

            $user = User::create($userData);

            Log::info('School Admin created for School Id ' . $user->school_id);

        return $user;
        }
        catch(Exception $e)
        {
            $this->safeLogWarning('School admin creation failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function resolveSchoolAdminGroupId()
    {
        // Primary expected role id used by legacy code and seeders.
        if (DB::table('usergroups')->where('id', 3)->exists()) {
            return 3;
        }

        $nameMatchId = Usergroup::query()
            ->whereRaw('LOWER(name) IN (?, ?, ?)', ['schooladmin', 'school admin', 'school administrator'])
            ->value('id');

        if ($nameMatchId) {
            return (int) $nameMatchId;
        }

        $anyGroupId = DB::table('usergroups')->orderBy('id')->value('id');
        if ($anyGroupId) {
            $this->safeLogWarning('SchoolAdmin usergroup id=3 is missing; using fallback group id', [
                'fallback_usergroup_id' => (int) $anyGroupId,
            ]);

            return (int) $anyGroupId;
        }

        try {
            $now = Carbon::now();

            DB::table('usergroups')->insert([
                'id' => 3,
                'name' => 'SchoolAdmin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->safeLogWarning('Created fallback SchoolAdmin usergroup for registration flow', [
                'usergroup_id' => 3,
            ]);

            return 3;
        } catch (Throwable $e) {
            $this->safeLogWarning('Failed to create fallback SchoolAdmin usergroup', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function prepareRegistrationData(array $data)
    {
        $normalizedMobileNo = $this->normalizeMobileNo(
            $data['mobile_no'] ?? '',
            $data['country'] ?? null
        );

        $data['mobile_no'] = $normalizedMobileNo;

        return $data;
    }

    private function normalizeMobileNo($mobileNo, $country)
    {
        $digitsOnly = preg_replace('/\D+/', '', (string) $mobileNo);

        if ($digitsOnly !== '' && strpos($digitsOnly, '0') === 0) {
            $digitsOnly = substr($digitsOnly, 1);
        }

        $countryCode = $this->resolveCountryCode($country);

        return $countryCode . $digitsOnly;
    }

    private function resolveCountryCode($country)
    {
        $countryCodeMap = [
            'Uganda' => '+256',
            'Kenya' => '+254',
            'Tanzania' => '+255',
            'Rwanda' => '+250',
            'Nigeria' => '+234',
            'Ghana' => '+233',
            'South Africa' => '+27',
            'United Kingdom' => '+44',
            'United States' => '+1',
        ];

        return $countryCodeMap[$country] ?? '+';
    }

    private function generateUsernameFromName($name)
    {
        $baseUsername = Str::of((string) $name)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s\-]/', '')
            ->trim()
            ->replaceMatches('/[\s\-]+/', '.')
            ->value();

        if ($baseUsername === '') {
            $baseUsername = 'user';
        }

        $username = $baseUsername;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '.' . $counter;
            $counter++;
        }

        return $username;
    }

    private function createSchoolDetails($school)
    {
        try
        {
            $keys = ['school_logo', 'moto', 'affiliated_by', 'affiliation_no', 'date_of_establishment', 'board', 'landline_no', 'about_us' , 'website'];

            foreach ($keys as $key) {
                $detail = SchoolDetail::create([
                    'school_id' =>  $school->id,
                    'meta_key'  =>  $key,
                    'meta_value' => "-",
                ]);
            }

            Log::info('School Details for School Id ' . $school->id);
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    private function createAcademicYear($school)
    {
        try
        {
            $detail = AcademicYear::create([
                'school_id'     =>  $school->id,
                'name'          =>  '2020-2021',
                'description'   =>  'Current Academic Year',
                'start_date'    =>  '2020-06-01',
                'end_date'      =>  '2021-05-31',
                'status'        =>  1,
            ]);

            Log::info('Academic Year for School Id ' . $school->id);
        }
        catch(Exception $e)
        {
            Log::info($e->getMessage());
            //dd($e->getMessage());
        }
    }

    private function sendEmailVerification(User $user)
    {
        try
        {
            if (env('MAIL_STATUS') == 'on')
            {
                Mail::to($user->email)->queue(new EmailVerification($user));

                Log::info('Verification Email Sent');
            }
        }
        catch(Throwable $e)
        {
            $this->safeLogWarning('Email verification queue failed', ['message' => $e->getMessage()]);
        }
    }

    private function sendAdminNotifyMail(User $user)
    {
        try
        {
            $admin = User::where('usergroup_id',1)->first();
            if ($admin && env('MAIL_STATUS') == 'on')
            {
                Mail::to($admin->email)->queue(new AdminNotifyNewUserMail($user));

                Log::info('Verification Email Sent');
            }
        }
        catch(Throwable $e)
        {
            $this->safeLogWarning('Admin notify email queue failed', ['message' => $e->getMessage()]);
        }
    }

    private function logNewRegistrationToSlack(User $user)
    {
        try {
            Log::channel('slack')->info('A new user registered.', [
                'Website' => env('APP_URL'),
                'User_id' => $user->id,
                'User_name' => $user->name,
                'School_id' => $user->school_id,
                'School_name' => optional($user->school)->name,
            ]);
        } catch (Throwable $e) {
            $this->safeLogWarning('Slack registration log skipped', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function dispatchRegistrationSideEffects(User $user)
    {
        try {
            $this->sendEmailVerification($user);
            $this->sendAdminNotifyMail($user);
            $this->logNewRegistrationToSlack($user);
        } catch (Throwable $e) {
            $this->safeLogWarning('Registration side effects failed', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }
    }

    private function safeLogError($message, array $context = [])
    {
        try {
            Log::error($message, $context);
        } catch (Throwable $e) {
            error_log($message . ': ' . ($context['message'] ?? $e->getMessage()));
        }
    }

    private function safeLogWarning($message, array $context = [])
    {
        try {
            Log::warning($message, $context);
        } catch (Throwable $e) {
            error_log($message . ': ' . ($context['message'] ?? $e->getMessage()));
        }
    }
}
