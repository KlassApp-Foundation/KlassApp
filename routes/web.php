<?php

use App\Http\Controllers\Superadmin\DashboardController;

/*Route::get('/', function () {
  return redirect()->route('login');
    //return view('welcome');
});*/
Route::get('/', 'WelcomeController');

// Community documentation (docsify-based site)
Route::get('/docs/community/{path?}', function ($path = '') {
    $base = base_path('docs/community');
    $file = $path ? "{$base}/{$path}" : "{$base}/index.html";

    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimes = [
            'md' => 'text/markdown', 'svg' => 'image/svg+xml',
            'css' => 'text/css', 'js' => 'application/javascript',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'html' => 'text/html',
        ];
        return response(file_get_contents($file), 200, ['Content-Type' => $mimes[$ext] ?? 'text/plain']);
    }

    // SPA fallback — docsify handles client-side routing
    return response(file_get_contents("{$base}/index.html"), 200, ['Content-Type' => 'text/html']);
})->where('path', '.*');

// Landing page v2 (Flare-style)
Route::get('/landing2', function () {
    return view('landing2');
});

// Landing page (public)
Route::get('/landing', function () {
    return view('landing');
});

// Clean landing section URLs (no more hash anchors)
Route::get('/features', fn() => view('landing', ['scrollTo' => 'features']));
Route::get('/pricing', fn() => view('landing', ['scrollTo' => 'pricing']));
Route::get('/schools', fn() => view('landing', ['scrollTo' => 'schools']));
Route::get('/contact', fn() => view('landing', ['scrollTo' => 'contact']));

Route::post('/contact', function (Illuminate\Http\Request $request) {
    $request->validate([
        'fullname' => 'required|string|max:255',
        'emailid' => 'required|email|max:255',
        'school_name' => 'nullable|string|max:255',
        'message' => 'required|string',
    ]);

    $contact = new App\Models\Contact();
    $contact->fullname = $request->fullname;
    $contact->email = $request->emailid;
    $contact->school_name = $request->school_name ?? '';
    $contact->message = $request->message;
    $contact->contact_no = $request->contact_no ?? '';
    $contact->save();

    if (env('MAIL_STATUS') == 'on') {
        Mail::to('team@klassapp.xyz')->send(new App\Mail\ContactMail($contact));
    }

    return redirect('/contact?sent=true#contact');
})->name('contact.send');
Route::get('/demo', fn() => view('landing', ['scrollTo' => 'demo']));

// Terms of Service and Privacy Policy
Route::get('/terms-of-service', [App\Http\Controllers\AboutController::class, 'terms']);
Route::get('/privacy-policy', [App\Http\Controllers\AboutController::class, 'create']);

// Premium School Pages (public)
Route::get('/schools/{slug}', [App\Http\Controllers\SchoolPageController::class, 'show']);

Auth::routes();


// Onboarding booking form (from docs)
Route::post('/api/onboarding/book', [App\Http\Controllers\OnboardingBookingController::class, 'store']);

// Dismiss onboarding reminder (sets session flag to hide the setup banner)
Route::get('/dismiss-onboarding', function () {
    session(['onboarding_reminder_dismissed' => true]);
    return redirect()->back();
})->name('dismiss.onboarding.reminder')->middleware('auth');

//Impersonate as teacher
Route::get('/teacher/{id}/impersonate', 'Auth\ImpersonateController@impersonate')->middleware('auth', 'schooladmin');
Route::get('/library/{id}/impersonate', 'Auth\ImpersonateController@librarianimpersonate')->middleware('auth', 'schooladmin');
Route::get('/student/{id}/impersonate', 'Auth\ImpersonateController@studentimpersonate')->middleware('auth', 'schooladmin');
Route::get('/teacher/impersonate/stop', 'Auth\ImpersonateController@stopImpersonate');

Route::get('/schooladmin/{id}/impersonate', 'Auth\ImpersonateController@schoolAdminimpersonate')->middleware('auth', 'superadmin');

//Reset Password for member
Route::get('/password/reset/{token}', 'Auth\ResetPasswordController@showResetForm');
Route::post('/password/reset', 'Auth\ResetPasswordController@reset')->name('password.reset');
//Email Verification for Member
Route::get('/emailverification/{token}', 'Auth\EmailVerificationController@emailverification');
// OTP Verification
Route::get('/checksms', 'TestController@checksms');
Route::get('/verifyotp', 'OTPController@create');
Route::post('/verifyotp', 'OTPController@store');

// Google OAuth
Route::get('/auth/google', 'Auth\GoogleAuthController@redirect');
Route::get('/auth/google/callback', 'Auth\GoogleAuthController@callback');

// Google Onboarding (interstitial after first Google sign-in)
Route::get('/welcome', 'Auth\GoogleAuthController@showOnboarding')->middleware('auth');
Route::post('/welcome', 'Auth\GoogleAuthController@storeOnboarding')->middleware('auth');

//siteadmin
Route::group(['middleware' => ['siteadmin'], 'namespace' => 'Admin'], function () {
    Route::get('/payment/subscription', 'PaymentController@Subscription');
});

// Route::get('/superadmin/dashboard', 'Superadmin\DashboardController@index')->name( 'dashboard');


// Route::group(['middleware' => ['superadmin','auth'], 'namespace' => 'Superadmin'], function () {
//    Route::get('/superadmin/dashboard', 'DashboardController@index')->name('dashboard');
// });
//test pages hidden
  /*video chat room*/ 
  /*Route::view('/video-chat-grid', 'pages.video.grid');
  Route::view('/video-chat-collaboration', 'pages.video.collaboration');
  Route::view('/video-chat-tile', 'pages.video.tile');
  Route::view('/video-chat-presentation', 'pages.video.presentation');*/
  /*Route::view('/admission-form', 'pages.admission.admission');*/
//test pages hidden

// Route::view('/admission-form', 'pages.admission.admission')

//user list
Route::get('/demo/schoolList', 'Demo\WelcomeController@schoolList');
Route::get('/demo/list/{school_id}', 'Demo\WelcomeController@list');

Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');
});

Route::get('/{slug}/standardlist','AdmissionController@list');
Route::get( '/{slug}/admission-form', 'AdmissionController@create' );
Route::post( '/{slug}/admission-form', 'AdmissionController@store' );


Route::post( '/{slug}/admission-form/validationAvatar', 'AdmissionController@validationAvatar' );
Route::post( '/{slug}/admission-form/validationFatherAvatar', 'AdmissionController@validationFatherAvatar' );
Route::post( '/{slug}/admission-form/validationMotherAvatar', 'AdmissionController@validationMotherAvatar' );
Route::post( '/{slug}/admission-form/validationStandard', 'AdmissionController@validationStandard' );
Route::post( '/{slug}/admission-form/validationStudentDetail', 'AdmissionController@validationStudentDetail' );
Route::post( '/{slug}/admission-form/validationAcademicDetail', 'AdmissionController@validationAcademicDetail' );
Route::post( '/{slug}/admission-form/validationParentDetail', 'AdmissionController@validationParentDetail' );
Route::post( '/{slug}/admission-form/validationPersonalDetail', 'AdmissionController@validationPersonalDetail' );






Route::group(['middleware' => ['superadmin','auth'],'prefix'=>'superadmin', 'namespace' => 'Superadmin'], function () {

      Route::get('/dashboard', 'DashboardSuperController@index')->name('superadmin.dashboard');
      
   //Route::get('/dashboard', 'DashboardController@index')->name('dashboard');

   //Contact
   Route::get('reports/contact', function () {
        return view('superadmin.reports.contactlist');
    })->name('superadmin.reports.contactlist');

   //School
   Route::get('academics/school/create', function () {
        return view('superadmin.academics.createschool');
    })->name('superadmin.academics.school.create');

   Route::get('academics/schools', function () {
        return view('superadmin.academics.schoollist');
    })->name('superadmin.academics.schoollist');

   Route::get('academics/school/update/{id}', function ($id) {
        return view('superadmin.academics.createschool',compact('id'));
    })->name('superadmin.academics.school.update');

   Route::get('academics/school/detail/{id}', function ($id) {
        return view('superadmin.academics.schooldetail',compact('id'));
    })->name('superadmin.academics.school.detail');

   //Admin

   Route::get('academics/school/admin/create/{id}', function ($id) {
        return view('superadmin.academics.adminform', compact('id'));
    })->name('superadmin.academics.school.createadmin');

   Route::get('academics/school/admin/update/{id}', function ($id) {
        return view('superadmin.academics.adminform',compact('id'));
    })->name('superadmin.academics.school.updateadmin');

   Route::get('academics/school/admin/detail/{id}', function ($id) {
        return view('superadmin.academics.admindetail',compact('id'));
    })->name('superadmin.academics.school.admindetail');

   Route::get('academics/school/admin/list/{id}', function ($id) {
        return view('superadmin.academics.adminlist', compact('id'));
    })->name('superadmin.academics.school.adminlist');

   Route::get('academics/school/user/list/{id}', function ($id) {
        return view('superadmin.academics.userlist', compact('id'));
    })->name('superadmin.academics.school.userlist');

   Route::get('academics/school/user/detail/{id}', function ($id) {
        return view('superadmin.academics.userdetail',compact('id'));
    })->name('superadmin.academics.school.userdetail');

   Route::get('academics/school/userprofile/create/{id}', function ($id) {
        return view('superadmin.academics.userprofileform', compact('id'));
    })->name('superadmin.academics.school.createuserprofile');

   Route::get('academics/school/userprofile/detail/{id}', function ($id) {
        return view('superadmin.academics.userprofiledetail',compact('id'));
    })->name('superadmin.academics.school.userprofiledetail');

   Route::get('academics/school/userprofile/update/{id}', function ($id) {
        return view('superadmin.academics.userprofileform', compact('id'));
    })->name('superadmin.academics.school.updateuserprofile');

   //Setting

   //City
   Route::get('setting/cities', function () {
        return view('superadmin.setting.cities');
    })->name('superadmin.setting.cities');

   Route::get('setting/city/create', function () {
        return view('superadmin.setting.cityform');
    })->name('superadmin.setting.cities.create');

   Route::get('setting/city/update/{id}', function ($id) {
        return view('superadmin.setting.cityform', compact('id'));
    })->name('superadmin.setting.cities.update');

   Route::get('setting/city/detail/{id}', function ($id) {
        return view('superadmin.setting.citydetail',compact('id'));
    })->name('superadmin.setting.city.detail');

   //Country
   Route::get('setting/countries', function () {
        return view('superadmin.setting.countries');
    })->name('superadmin.setting.countries');

   /*Route::get('setting/country/create', function () {
        return view('superadmin.setting.countryform');
    })->name('superadmin.setting.countries.create');*/

    Route::get('setting/country/update/{id}', function ($id) {
        return view('superadmin.setting.countryform', compact('id'));
    })->name('superadmin.setting.countries.update');

    Route::get('setting/country/detail/{id}', function ($id) {
        return view('superadmin.setting.countrydetail',compact('id'));
    })->name('superadmin.setting.countries.detail');

    //Plan
    Route::get('setting/plans', function () {
        return view('superadmin.setting.planlist');
    })->name('superadmin.setting.planlist');

    Route::get('setting/plan/create', function () {
        return view('superadmin.setting.planform');
    })->name('superadmin.setting.plan.create');

    Route::get('setting/plan/update/{id}', function ($id) {
        return view('superadmin.setting.planform', compact('id'));
    })->name('superadmin.setting.plan.update');

    Route::get('setting/plan/detail/{id}', function ($id) {
        return view('superadmin.setting.plandetail',compact('id'));
    })->name('superadmin.setting.plan.detail');

    //Reports
    Route::get('reports', function () {
        return view('superadmin.reports.index');
    })->name('superadmin.reports.index');

    //Subscription
    Route::get('reports/subscriptions', function () {
        return view('superadmin.reports.subscriptions');
    })->name('superadmin.reports.subscriptionlist');

    Route::get('reports/subscription/create', function () {
        return view('superadmin.reports.subscriptionform');
    })->name('superadmin.reports.subscription.create');

    Route::get('reports/subscription/update/{id}', function ($id) {
        return view('superadmin.reports.subscriptionform', compact('id'));
    })->name('superadmin.reports.subscription.update');

    Route::get('reports/subscription/detail/{id}', function ($id) {
        return view('superadmin.reports.subscriptiondetail',compact('id'));
    })->name('superadmin.reports.subscription.detail');

    //Change Password
    Route::get('changepassword', function () {
        return view('superadmin.changepassword');
    })->name('superadmin.changepassword');

    //Change Avatar
    Route::get('changeavatar', function () {
        return view('superadmin.changeavatar');
    })->name('superadmin.changeavatar');

    // Settings pages
    Route::get('/settings', function () {
        return view('superadmin.settings.index');
    })->name('superadmin.settings');

    Route::get('/settings/system', function () {
        return view('superadmin.settings.system-settings');
    })->name('superadmin.settings.system');

    Route::get('/settings/co-admins', function () {
        return view('superadmin.settings.co-admins');
    })->name('superadmin.settings.co-admins');

    Route::get('/settings/features', function () {
        return view('superadmin.settings.features');
    })->name('superadmin.settings.features');

    Route::get('/settings/locations', function () {
        return view('superadmin.settings.locations');
    })->name('superadmin.settings.locations');

    Route::get('/settings/emis', function () {
        return view('superadmin.settings.emis-schools');
    })->name('superadmin.settings.emis');
});



// Mail list subscription
Route::post('/subscribe', function (Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email|unique:mail_list,email']);
    App\Models\MailList::create(['email' => $request->email, 'name' => $request->name ?? '']);
    return back()->with('subscribed', true);
})->name('subscribe');

// Super admin mail list view
Route::get('/superadmin/mail-list', function () {
    $subscribers = App\Models\MailList::where('subscribed', true)->latest()->paginate(50);
    return view('superadmin.mail-list', compact('subscribers'));
})->name('superadmin.mail-list')->middleware(['web', 'auth', 'superadmin']);
