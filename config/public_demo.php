<?php

/*
| Public demo lockdown (App\Http\Middleware\PublicDemoLockdown).
|
| Applies ONLY to a user with users.is_public_demo = true (the live demo's
| admin, whose password is public; set by demo:provision). Everyone else is
| untouched. Each rule: [methods, URI regex (no leading slash), reason].
| methods '*' = every method; 'write' = anything but GET/HEAD/OPTIONS.
| The full route audit is in docs/demo/public-demo-lockdown.md.
*/

return [
    'routes' => [
        // Site-admin and platform tools.
        ['*', '#^(superadmin|platform|filament|tinker|_boost|_debugbar|horizon|telescope|log-viewer|pulse)(/|$)#', 'site-admin or platform route'],
        // Own account: password, email, name, avatar, 2FA, password resets.
        ['*', '#^(admin|teacher|student|receptionist|accountant|library|parent)/(changepassword|changeavatar|editprofile|profile)(/|$)#', 'own profile or password'],
        ['*', '#^password(/|$)#', 'password reset or change'],
        ['*', '#^api/(password|reset|teacher/change/password|v2/password)(/|$)#', 'password reset or change'],
        ['*', '#(two-factor|2fa|change-email|email/change)#i', 'two-factor or email change'],
        ['*', '#^admin/user/resetPassword/#', 'reset link or password for another user'],
        ['*', '#^admin/parent/show/[^/]+/reset-password$#', 'reset link or password for another user'],
        // Impersonation (it would leave the locked account).
        ['*', '#(^|/)(impersonate|impersonation)(/|$)#i', 'impersonation'],
        // Invites and messages that leave the app.
        ['*', '#^admin/class-teacher-invite(/|$)#', 'invite email'],
        ['*', '#^admin/(student|teacher)/sendMessageToAll$#', 'broadcast message'],
        ['*', '#^admin/dashboard/send/reminder/#', 'fee reminder'],
        ['*', '#^admin/fees/payments/reminders$#', 'fee reminder'],
        ['write', '#^admin/phonenumber(/|$)#', 'contact numbers that could receive sends'],
        ['write', '#^admin/whatsapp/phone$#', 'WhatsApp number setting'],
        ['write', '#^admin/whatsapp/parents/unlink/#', 'WhatsApp link change'],
        // Uploads and imports (marks entry is a form and stays allowed).
        ['*', '#^livewire/upload-file$#', 'file upload'],
        ['*', '#^admin/(import|import/teacher|import/parents|importTeachers|importParents|importUsers|promotion/import|report/importHolidays|teacher-links/import)$#', 'import'],
        ['*', '#^admin/exams/[^/]+/marks/import(/|$)#', 'marks file import'],
        ['write', '#^admin/(document|upload|changeavatar)(/|$)#', 'file upload'],
        ['write', '#^admin/classwall/post/(add|edit)/attachment#', 'file upload'],
        ['write', '#^admin/teacher/add/validationAvatar$#', 'file upload'],
        // School settings, integrations, payments, AI.
        ['write', '#^admin/settings/(generalsettings|maintenancesettings|seodetailsettings)$#', 'school-wide settings'],
        ['write', '#^admin/schooldetails/#', 'school details (name, logo, domain)'],
        ['*', '#^admin/settings/integrations(/|$)#', 'integrations'],
        ['*', '#^(admin/)?(subscriptions|payment)(/|$)#', 'payments or subscription'],
        ['*', '#^auth/google(/|$)#', 'linking a Google account'],
        ['*', '#^admin/toshi#', 'Toshi / AI'],
    ],

    // Livewire components refused for the public demo admin (name regex).
    'livewire' => [
        ['#^(manual-onboarding-wizard)$#', 'onboarding'],
        ['#^superadmin\.#', 'site-admin component'],
        ['#(profile|password|avatar|two-factor|invite|broadcast|reminder|integration|import|upload)#i', 'profile, invite, broadcast, import or upload component'],
    ],
];
