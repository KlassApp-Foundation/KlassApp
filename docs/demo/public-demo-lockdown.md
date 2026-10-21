# Public demo admin lockdown: route inventory

Generated from `php artisan route:list` and `config/public_demo.php` (PR "Demo on production").

```
Public demo admin lockdown — route inventory (generated from route:list and config/public_demo.php)


## Cross-school
-       any route-bound record or {school_id}                                  BLOCKED: 404 when it belongs to another school

## Livewire components
POST    livewire/update component #(profile|password|avatar|two-factor|invite|broadcast|reminder|integration|import|upload)#i BLOCKED: profile, invite, broadcast, import or upload component
POST    livewire/update component #^(agent-toshi|manual-onboarding-wizard)$#   BLOCKED: Toshi / AI or onboarding
POST    livewire/update component #^superadmin\.#                              BLOCKED: site-admin component
POST    livewire/upload-file                                                   BLOCKED: file upload

## Mail (any route or queued job)
-       MessageSending / NotificationSending                                   BLOCKED: every email and notification started by the public demo admin is cancelled

## SMS/WhatsApp/broadcast/reminder
POST    /admin/dashboard/send/reminder/{fee_id}                                BLOCKED: fee reminder
POST    /admin/fees/payments/reminders                                         BLOCKED: fee reminder
GET     /admin/messages                                                        allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
GET     /admin/sentmessages                                                    allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
POST    /admin/student/sendMessageToAll                                        BLOCKED: broadcast message
POST    /admin/teacher/sendMessageToAll                                        BLOCKED: broadcast message
GET     /admin/whatsapp/dashboard                                              allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
GET     /admin/whatsapp/parents                                                allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
POST    /admin/whatsapp/parents/unlink/{id}                                    BLOCKED: WhatsApp link change
GET     /admin/whatsapp/phone                                                  allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
POST    /admin/whatsapp/phone                                                  BLOCKED: WhatsApp number setting
GET     /api/teacher/messages                                                  allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
POST    /api/v2/message/read/{id}                                              allowed: mobile API (token users); the lockdown also runs in the api group
GET     /api/v2/messages                                                       allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
POST    /api/whatsapp/delivery                                                 allowed: inbound webhook from Meta/SchoolPay, no admin session (signature checked)
GET     /api/whatsapp/inbound                                                  allowed: inbound webhook from Meta/SchoolPay, no admin session (signature checked)
POST    /api/whatsapp/inbound                                                  allowed: inbound webhook from Meta/SchoolPay, no admin session (signature checked)
GET     /broadcasting/auth                                                     allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
POST    /broadcasting/auth                                                     allowed: mail from it is cancelled by the mail guard; WhatsApp by WHATSAPP_DEMO_ONLY_RECIPIENTS
GET     /dismiss-onboarding                                                    allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)
GET     /whatsapp/report-files/{token}                                         allowed: read-only page (sends are blocked or limited by WHATSAPP_DEMO_ONLY_RECIPIENTS)

## Toshi/AI/LLM
GET     /admin/toshi-activity                                                  BLOCKED: Toshi / AI
GET     /superadmin/toshi/ops/{conversation}                                   BLOCKED: site-admin or platform route
POST    /superadmin/toshi/ops/{conversation}                                   BLOCKED: site-admin or platform route

## WhatsApp (any route)
-       WhatsAppBusinessService                                                LIMITED: WHATSAPP_DEMO_ONLY_RECIPIENTS=true (demo-bound / pending / current sender only)

## contacts/phone numbers
GET     /admin/phonenumber/add                                                 allowed: read-only page; its saves are blocked or stay inside the demo school
POST    /admin/phonenumber/add                                                 BLOCKED: contact numbers that could receive sends
DELETE  /admin/phonenumber/delete/{id}                                         BLOCKED: contact numbers that could receive sends
GET     /admin/phonenumber/edit/{id}                                           allowed: read-only page; its saves are blocked or stay inside the demo school
POST    /admin/phonenumber/edit/{id}                                           BLOCKED: contact numbers that could receive sends
GET     /admin/phonenumber/editlist/{id}                                       allowed: read-only page; its saves are blocked or stay inside the demo school
GET     /admin/phonenumber/list                                                allowed: read-only page; its saves are blocked or stay inside the demo school
GET     /admin/phonenumber/show/{id}                                           allowed: read-only page; its saves are blocked or stay inside the demo school
GET     /admin/phonenumbers                                                    allowed: read-only page; its saves are blocked or stay inside the demo school
GET     /contact                                                               allowed: read-only page; its saves are blocked or stay inside the demo school
POST    /contact                                                               allowed: mail from it is cancelled by the mail guard; WhatsApp by WHATSAPP_DEMO_ONLY_RECIPIENTS
GET     /superadmin/reports/contact                                            BLOCKED: site-admin or platform route

## email/invite/reset
POST    /admin/class-teacher-invite/{invite}/resend                            BLOCKED: invite email
GET     /admin/class-teacher-invite/{section}/create                           BLOCKED: invite email
POST    /admin/class-teacher-invite/{standardLink}/store                       BLOCKED: invite email
POST    /admin/parent/show/{name}/reset-password                               BLOCKED: reset link or password for another user
GET     /admin/user/resetPassword/{id}                                         BLOCKED: reset link or password for another user
POST    /api/password/reset                                                    BLOCKED: password reset or change
POST    /api/reset/change/password                                             BLOCKED: password reset or change
POST    /api/reset/check                                                       BLOCKED: password reset or change
GET     /emailverification/{token}                                             allowed: public token link for the invitee, not an admin action
GET     /invite/co-admin/{token}                                               allowed: public token link for the invitee, not an admin action
POST    /invite/co-admin/{token}                                               allowed: public token link for the invitee, not an admin action
GET     /invite/teacher/{token}                                                allowed: public token link for the invitee, not an admin action
POST    /invite/teacher/{token}                                                allowed: public token link for the invitee, not an admin action
POST    /password/email                                                        BLOCKED: password reset or change
GET     /password/reset                                                        BLOCKED: password reset or change
POST    /password/reset                                                        BLOCKED: password reset or change
GET     /password/reset-code                                                   BLOCKED: password reset or change
GET     /password/reset-code/resend                                            BLOCKED: password reset or change
POST    /password/reset-code/verify                                            BLOCKED: password reset or change
POST    /password/reset/change                                                 BLOCKED: password reset or change
GET     /password/reset/{token}                                                BLOCKED: password reset or change
GET     /preview/reset-code                                                    allowed: read-only page; its saves are blocked or stay inside the demo school
GET     /preview/reset-newpw                                                   allowed: read-only page; its saves are blocked or stay inside the demo school
GET     /preview/reset-request                                                 allowed: read-only page; its saves are blocked or stay inside the demo school
POST    /register/verify/resend                                                allowed: public token link for the invitee, not an admin action
GET     /superadmin/mail-list                                                  BLOCKED: site-admin or platform route

## exports (download to the browser)
GET     /admin/attendance/export/{standardLink_id}                             allowed: download of demo-school data to the same browser; nothing leaves the app
GET     /admin/export                                                          allowed: download of demo-school data to the same browser; nothing leaves the app
GET     /admin/exportTeachers                                                  allowed: download of demo-school data to the same browser; nothing leaves the app
GET     /admin/exportUsers                                                     allowed: download of demo-school data to the same browser; nothing leaves the app
POST    /admin/promotion/export                                                allowed: download of demo-school data to the same browser; nothing leaves the app
GET     /admin/report/exportHolidays                                           allowed: download of demo-school data to the same browser; nothing leaves the app
GET     /admin/staff/export                                                    allowed: download of demo-school data to the same browser; nothing leaves the app
POST    /admin/staff/export                                                    allowed: download of demo-school data to the same browser; nothing leaves the app
GET     /admin/student/export                                                  allowed: download of demo-school data to the same browser; nothing leaves the app
POST    /admin/student/export                                                  allowed: download of demo-school data to the same browser; nothing leaves the app
GET     /admin/teacher/export                                                  allowed: download of demo-school data to the same browser; nothing leaves the app
POST    /admin/teacher/export                                                  allowed: download of demo-school data to the same browser; nothing leaves the app

## impersonation/site admin/platform
POST    /_boost/browser-logs                                                   BLOCKED: site-admin or platform route
GET     /_debugbar/assets/javascript                                           BLOCKED: site-admin or platform route
GET     /_debugbar/assets/stylesheets                                          BLOCKED: site-admin or platform route
DELETE  /_debugbar/cache/{key}/{tags?}                                         BLOCKED: site-admin or platform route
GET     /_debugbar/clockwork/{id}                                              BLOCKED: site-admin or platform route
GET     /_debugbar/open                                                        BLOCKED: site-admin or platform route
POST    /_debugbar/queries/explain                                             BLOCKED: site-admin or platform route
GET     /filament/exports/{export}/download                                    BLOCKED: site-admin or platform route
GET     /library/{id}/impersonate                                              BLOCKED: impersonation
GET     /platform/early-access                                                 BLOCKED: site-admin or platform route
GET     /platform/early-access/export                                          BLOCKED: site-admin or platform route
GET     /schooladmin/{id}/impersonate                                          BLOCKED: impersonation
GET     /student/{id}/impersonate                                              BLOCKED: impersonation
GET     /superadmin/academics/school/admin/create/{id}                         BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/admin/detail/{id}                         BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/admin/list/{id}                           BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/admin/update/{id}                         BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/create                                    BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/detail/{id}                               BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/update/{id}                               BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/user/detail/{id}                          BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/user/list/{id}                            BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/userprofile/create/{id}                   BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/userprofile/detail/{id}                   BLOCKED: site-admin or platform route
GET     /superadmin/academics/school/userprofile/update/{id}                   BLOCKED: site-admin or platform route
GET     /superadmin/academics/schools                                          BLOCKED: site-admin or platform route
GET     /superadmin/changepassword                                             BLOCKED: site-admin or platform route
GET     /superadmin/dashboard                                                  BLOCKED: site-admin or platform route
GET     /superadmin/notification/list                                          BLOCKED: site-admin or platform route
POST    /superadmin/notification/read                                          BLOCKED: site-admin or platform route
GET     /superadmin/notification/showList                                      BLOCKED: site-admin or platform route
GET     /superadmin/notifications                                              BLOCKED: site-admin or platform route
GET     /superadmin/reports                                                    BLOCKED: site-admin or platform route
GET     /superadmin/reports/demo-requests                                      BLOCKED: site-admin or platform route
GET     /superadmin/setting/cities                                             BLOCKED: site-admin or platform route
GET     /superadmin/setting/city/create                                        BLOCKED: site-admin or platform route
GET     /superadmin/setting/city/detail/{id}                                   BLOCKED: site-admin or platform route
GET     /superadmin/setting/city/update/{id}                                   BLOCKED: site-admin or platform route
GET     /superadmin/setting/countries                                          BLOCKED: site-admin or platform route
GET     /superadmin/setting/country/create                                     BLOCKED: site-admin or platform route
GET     /superadmin/setting/country/detail/{id}                                BLOCKED: site-admin or platform route
GET     /superadmin/setting/country/update/{id}                                BLOCKED: site-admin or platform route
GET     /superadmin/setting/plan/create                                        BLOCKED: site-admin or platform route
GET     /superadmin/setting/plan/detail/{id}                                   BLOCKED: site-admin or platform route
GET     /superadmin/setting/plan/update/{id}                                   BLOCKED: site-admin or platform route
GET     /superadmin/setting/plans                                              BLOCKED: site-admin or platform route
GET     /superadmin/settings                                                   BLOCKED: site-admin or platform route
GET     /superadmin/settings/co-admins                                         BLOCKED: site-admin or platform route
GET     /superadmin/settings/emis                                              BLOCKED: site-admin or platform route
GET     /superadmin/settings/features                                          BLOCKED: site-admin or platform route
GET     /superadmin/settings/locations                                         BLOCKED: site-admin or platform route
GET     /superadmin/settings/system                                            BLOCKED: site-admin or platform route
GET     /teacher/impersonate/stop                                              BLOCKED: impersonation
GET     /teacher/{id}/impersonate                                              BLOCKED: impersonation
GET     /tinker                                                                BLOCKED: site-admin or platform route
POST    /tinker                                                                BLOCKED: site-admin or platform route

## integrations/webhooks/Google/Slack/tokens/domains
GET     /admin/settings/integrations                                           BLOCKED: integrations
POST    /admin/settings/integrations/{type}/disconnect                         BLOCKED: integrations
POST    /api/schoolpay/webhook                                                 allowed: inbound webhook from Meta/SchoolPay, no admin session (signature checked)
GET     /api/v2/updatetoken                                                    allowed: mobile API (token users); the lockdown also runs in the api group
GET     /auth/google                                                           BLOCKED: linking a Google account
GET     /auth/google/callback                                                  BLOCKED: linking a Google account
POST    /auth/google/start                                                     BLOCKED: linking a Google account
GET     /register/verify/link/{token}                                          allowed: public token link for the invitee, not an admin action
POST    /register/verify/link/{token}                                          allowed: public token link for the invitee, not an admin action

## payments/subscriptions
GET     /admin/fees/payments                                                   allowed: records a payment in the demo school only; no money moves (no mobile-money call)
GET     /admin/fees/payments/create                                            allowed: records a payment in the demo school only; no money moves (no mobile-money call)
POST    /admin/fees/payments/store                                             allowed: records a payment in the demo school only; no money moves (no mobile-money call)
GET     /admin/fees/payments/unmatched                                         allowed: records a payment in the demo school only; no money moves (no mobile-money call)
POST    /admin/fees/payments/unmatched/{transaction}/match                     allowed: records a payment in the demo school only; no money moves (no mobile-money call)
GET     /admin/payment/index/{id}                                              BLOCKED: payments or subscription
POST    /admin/payment/response                                                BLOCKED: payments or subscription
GET     /admin/payment/subscription                                            BLOCKED: payments or subscription
GET     /admin/subscriptions                                                   BLOCKED: payments or subscription
POST    /admin/subscriptions                                                   BLOCKED: payments or subscription
GET     /admin/subscriptions/submit                                            BLOCKED: payments or subscription
GET     /admin/subscriptions/{subscription}                                    BLOCKED: payments or subscription
PUT     /admin/subscriptions/{subscription}                                    BLOCKED: payments or subscription
GET     /payment/subscription                                                  BLOCKED: payments or subscription
GET     /superadmin/reports/subscription/create                                BLOCKED: site-admin or platform route
GET     /superadmin/reports/subscription/detail/{id}                           BLOCKED: site-admin or platform route
GET     /superadmin/reports/subscription/update/{id}                           BLOCKED: site-admin or platform route
GET     /superadmin/reports/subscriptions                                      BLOCKED: site-admin or platform route

## profile/password/2FA
GET     /admin/changepassword                                                  BLOCKED: own profile or password
POST    /admin/changepassword                                                  BLOCKED: own profile or password
GET     /admin/editprofile                                                     BLOCKED: own profile or password
GET     /admin/profile                                                         BLOCKED: own profile or password
POST    /admin/profile                                                         BLOCKED: own profile or password
GET     /admin/school-profile                                                  allowed: read-only school profile page
POST    /admin/student/health/profile/{userId}                                 allowed: student health record form in the demo school
GET     /admin/students/{student}                                              allowed: a student profile page in the demo school (other schools 404)
POST    /admin/teacher/add/validationProfile                                   allowed: form validation only (no save, no mail)
POST    /admin/teacher/edit/validationProfile/{name}                           allowed: form validation only (no save, no mail)
POST    /api/password/store                                                    BLOCKED: password reset or change
POST    /api/teacher/change/password                                           BLOCKED: password reset or change
POST    /api/v2/password/change                                                BLOCKED: password reset or change
GET     /password/force-change                                                 BLOCKED: password reset or change
POST    /password/force-change                                                 BLOCKED: password reset or change
GET     /preview/force-change-password                                         allowed: read-only page; its saves are blocked or stay inside the demo school

## school delete/settings
GET     /admin/schooldetails                                                   allowed: read-only page; its saves are blocked or stay inside the demo school
GET     /admin/schooldetails/edit/{school_id}                                  allowed: read-only page; its saves are blocked or stay inside the demo school
GET     /admin/schooldetails/editdetail/{school_id}                            allowed: read-only page; its saves are blocked or stay inside the demo school
POST    /admin/schooldetails/update/validationUpdate/{school_id}               BLOCKED: school details (name, logo, domain)
POST    /admin/schooldetails/update/{school_id}                                BLOCKED: school details (name, logo, domain)
GET     /admin/settings/generalsettings                                        allowed: read-only page; its saves are blocked or stay inside the demo school
POST    /admin/settings/generalsettings                                        BLOCKED: school-wide settings
GET     /admin/settings/maintenancesettings                                    allowed: read-only page; its saves are blocked or stay inside the demo school
POST    /admin/settings/maintenancesettings                                    BLOCKED: school-wide settings
GET     /admin/settings/seodetailsettings                                      allowed: read-only page; its saves are blocked or stay inside the demo school
POST    /admin/settings/seodetailsettings                                      BLOCKED: school-wide settings

## uploads/imports/media
GET     /admin/changeavatar                                                    BLOCKED: own profile or password
POST    /admin/changeavatar                                                    BLOCKED: own profile or password
POST    /admin/classwall/post/add/attachment                                   BLOCKED: file upload
POST    /admin/classwall/post/edit/attachment/{id}                             BLOCKED: file upload
GET     /admin/display/photos/{event_id}                                       allowed: read-only (any upload request is refused by the file check)
POST    /admin/document/add/{name}                                             BLOCKED: file upload
DELETE  /admin/document/delete/{id}                                            BLOCKED: file upload
GET     /admin/document/edit/list/{id}                                         allowed: read-only (any upload request is refused by the file check)
POST    /admin/document/edit/{name}/{id}                                       BLOCKED: file upload
GET     /admin/document/get/{name}                                             allowed: read-only (any upload request is refused by the file check)
GET     /admin/exams/{exam}/marks/import                                       BLOCKED: marks file import
POST    /admin/exams/{exam}/marks/import/confirm                               BLOCKED: marks file import
POST    /admin/exams/{exam}/marks/import/preview                               BLOCKED: marks file import
GET     /admin/exams/{exam}/marks/import/result                                BLOCKED: marks file import
GET     /admin/exams/{exam}/marks/import/template                              BLOCKED: marks file import
GET     /admin/getavatar                                                       allowed: read-only (any upload request is refused by the file check)
GET     /admin/getphoto/{event_id}                                             allowed: read-only (any upload request is refused by the file check)
GET     /admin/import                                                          BLOCKED: import
GET     /admin/import/teacher                                                  BLOCKED: import
POST    /admin/importTeachers                                                  BLOCKED: import
POST    /admin/importUsers                                                     BLOCKED: import
POST    /admin/promotion/import                                                BLOCKED: import
POST    /admin/report/importHolidays                                           BLOCKED: import
GET     /admin/students/upload-template                                        allowed: read-only (any upload request is refused by the file check)
POST    /admin/teacher-links/import                                            BLOCKED: import
POST    /admin/teacher/add/validationAvatar                                    BLOCKED: file upload
POST    /admin/upload/photos/{event_id}                                        BLOCKED: file upload
POST    /api/logout/devices                                                    allowed route, but any request carrying a file is refused centrally
POST    /api/teacher/logout                                                    allowed route, but any request carrying a file is refused centrally
POST    /api/teacher/logout/devices                                            allowed route, but any request carrying a file is refused centrally
GET     /api/teacher/performance/add/media_type                                allowed: read-only (any upload request is refused by the file check)
POST    /api/v2/logout                                                         allowed route, but any request carrying a file is refused centrally
GET     /api/v2/media-files/audio/{student_id}                                 allowed: read-only (any upload request is refused by the file check)
GET     /api/v2/media-files/image/{student_id}                                 allowed: read-only (any upload request is refused by the file check)
GET     /api/v2/media-files/video/{student_id}                                 allowed: read-only (any upload request is refused by the file check)
GET     /api/v2/my-school/media-files                                          allowed: read-only (any upload request is refused by the file check)
GET     /filament/imports/{import}/failed-rows/download                        BLOCKED: site-admin or platform route
POST    /livewire/upload-file                                                  BLOCKED: file upload
POST    /logout                                                                allowed route, but any request carrying a file is refused centrally
GET     /superadmin/changeavatar                                               BLOCKED: site-admin or platform route
POST    /{slug}/admission-form/validationAvatar                                allowed route, but any request carrying a file is refused centrally
POST    /{slug}/admission-form/validationFatherAvatar                          allowed route, but any request carrying a file is refused centrally
POST    /{slug}/admission-form/validationMotherAvatar                          allowed route, but any request carrying a file is refused centrally
```
