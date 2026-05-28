# KlassApp Session Memory
# Updated: 2026-05-28
#
# NOTE: Canonical knowledge → knowledge.md (project root)
# Session start: read knowledge.md, load klassapp-knowledge skill
# Session end: append summary to knowledge.md Session Log

## Project Overview
- Multi-tenant school management system (Laravel + Blade + Tailwind + Vue)
- Target: Uganda schools
- Hosting: VPS (Hetzer), Domain: Cloudflare (ugasch.com)
- WhatsApp Business Number: +256767538805 (in config/services.php: whatsapp.business_number)

## Landing Page (COMPLETED)
- File: resources/views/landing.blade.php
- Route: GET /landing in routes/web.php
- Stack: Blade + Tailwind (CDN) + Vanilla JS
- 10 sections: Nav, Hero, Social Proof, Problem, How It Works, For Schools, Pricing, Testimonials, CTA, Footer
- Colors: navy #0D1526, blue #1E6FD9, green #22C55E, surface #F8FAFC
- Fonts: Sora (headings), DM Sans (body) from Google Fonts
- Pricing: 3 tiers (Starter free, Growth/Contact, Premium/Contact)
- Responsive: mobile/tablet/desktop breakpoints
- Uses SVG logos from public/images/
- WhatsApp CTA: floating green widget (bottom-right), hero button, nav link, CTA section, footer icon — all wa.me links
- wa.me links use str_replace('+', '', config('services.whatsapp.business_number'))

## WhatsApp Layer (COMPLETED)
- Evolution API (evoapicloud/evolution-api:latest) as WhatsApp gateway
- Native Laravel inbound routing (replacing n8n workflow)
- Route: POST /api/whatsapp/inbound (outside HMAC middleware)
- Controller: WhatsAppController@handleInbound (uses StoreWhatsAppWebhookRequest for validation)
- Inbound validation: StoreWhatsAppWebhookRequest (app/Http/Requests/WhatsApp/) — validates event=messages.upsert, remoteJid, message body, payload size, phone format
- Keywords: menu, grades, fees, attendance, events, optin, optout
- Guards: group messages ignored, own messages ignored, non-message events ignored
- Phone extraction: data_get(payload, 'data.key.remoteJid') → strip @s.whatsapp.net → normalise()
- Webhook URL: http://host.docker.internal:8000/api/whatsapp/inbound (dev)
- Docker compose: Evolution + n8n + Postgres + Redis
- Evolution instance: "klassapp" (connected)
- WhatsAppPhoneHelper: normalise(), validate() (regex: \+256(7[0578]\d{7}) — 9 digits after +256), formatMessage()
- WhatsAppPhoneHelper::validate() regex FIXED: changed \d{8} to \d{7} (was too permissive)

## WhatsApp Admin — Phone Linking (COMPLETED)
- Route: GET/POST /admin/whatsapp/phone (routes/admin.php)
- Controller: UserProfileController@phoneLink, linkWhatsApp
- View: resources/views/admin/whatsapp/phone-link.blade.php
- Sidebar: "WhatsApp Phone" under Settings in resources/views/layouts/admin/menu.blade.php
- States: linked (show phone + unlink button), unlinked (show input form + link button)
- Phone saved to users.whatsapp_phone column (already existed)

## Outbound Hooks (COMPLETED)
- Service: app/Services/OutboundWhatsAppService.php — depends on WhatsAppService (injected)
  - notifyGradesPublished(student, examId) — fetches parent phones, composes grade message per student, sends via WhatsAppService
  - notifyFeeReminder(type, schoolId) — queries parents with outstanding fees, composes reminder message
  - getParentPhones(classId) — looks up parents via pivot tables
- Event: app/Events/GradesPublished.php — $student, $examId
- Listener: app/Listeners/SendGradesToWhatsApp.php — calls OutboundWhatsAppService::notifyGradesPublished
- Command: app/Console/Commands/SendFeeReminders.php — `whatsapp:send-fee-reminders` with --type=reminder|overdue, --school-id, --dry-run
- Registration: GradesPublished → SendGradesToWhatsApp in EventServiceProvider
- Schedule: weekly reminders (Mondays), daily overdue — both withoutOverlapping() in Console/Kernel

## Premium School Pages (COMPLETED)
- 5 Blade templates in resources/views/schools/templates/ (template-1 through template-5)
- Shared partial: resources/views/schools/templates/_shared.blade.php — extracts $colors, $content, $moto, $aboutUs, auto-normalises school phone to WhatsApp link
- WhatsApp buttons: hero CTA + floating widget on all 5 templates
- Contact phone numbers rendered as clickable wa.me links with SVG icon
- Phone normalisation (in _shared): strips non-digits, prepends 256 if leading 0
- URL: GET /schools/{slug} in routes/web.php
- Controller: SchoolPageController@show
- Model: PremiumPage with fillable: school_id, template_id, is_published, etc.
- School has premiumPage() hasOne relationship
- Admin settings: /admin/settings/premium-page (Setting\PremiumPageController)

## Feature Tests (COMPLETED)
- WebhookValidationTest (tests/Feature/WhatsApp/): 7 tests — validates event rules, remoteJid, message body, extendedTextMessage via Laravel validator + rules()
- OutboundNotificationTest (tests/Feature/WhatsApp/): 4 tests — phone normalisation, validation (valid/invalid), formatMessage
- Test factories: WhatsAppUserFactory (with optedOut/unverified states), MessageDeliveryLogFactory (with inbound/failed states, $timestamps=false)
- phpunit.xml: DB_CONNECTION=sqlite, DB_DATABASE=:memory:, WhatsApp env vars added

## Open Design Daemon
- Running on port 60696
- /api/health returns {"ok":true,"version":"0.1.0"}
- /api/chat returns "unknown agent: undefined"

## Known Edge Case
- Phone number in premium template: if school phone is non-Uganda format (e.g. +254... Kenya), it passes through without validation. Should add Uganda-only validation in UserProfileController@linkWhatsApp before save: preg_match('/^2567[0578]\d{7}$/', $cleaned).

## Files Modified This Session
- app/Http/Controllers/Api/WhatsAppController.php (inbound routing + FormRequest injection)
- app/Http/Controllers/Admin/UserProfileController.php (phoneLink, linkWhatsApp methods)
- app/Models/MessageDeliveryLog.php (added $timestamps = false)
- app/Models/School.php (added premiumPage() relationship)
- app/Helpers/WhatsAppPhoneHelper.php (validate regex fix)
- app/Providers/EventServiceProvider.php (GradesPublished listener registration)
- app/Console/Kernel.php (SendFeeReminders command + weekly/daily schedule)
- routes/api.php (POST /whatsapp/inbound)
- routes/admin.php (GET/POST /admin/whatsapp/phone)
- resources/views/landing.blade.php (WhatsApp CTAs)
- resources/views/schools/templates/_shared.blade.php ($whatsappLink helper)
- resources/views/schools/templates/template-{1..5}.blade.php (WhatsApp buttons)
- resources/views/admin/whatsapp/phone-link.blade.php (NEW)
- resources/views/layouts/admin/menu.blade.php (sidebar link)
- config/services.php (WhatsApp config)
- phpunit.xml (test DB config)
