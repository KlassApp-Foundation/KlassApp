# Google connectors — setup and local smoke test

This is the setup a person does in Google Cloud and in the app environment. It does not contain client secrets. Leave the values empty in git. Do not set them on Laravel Cloud from a pull request.

KlassApp sign-in and the school connectors share one OAuth client and use two redirect URIs. Sign-in stays on Socialite and `/auth/google/callback`. School tools use `GOOGLE_CONNECTOR_REDIRECT_URI` and do not go through Socialite. Adding a connector scope does not change the login request.

This file is on the Classroom pull request, [#1140](https://github.com/KlassApp-Foundation/KlassApp/pull/1140) (`cursor/google-connectors-classroom-b53f`), stacked on Drive [#1137](https://github.com/KlassApp-Foundation/KlassApp/pull/1137), Sheets [#1138](https://github.com/KlassApp-Foundation/KlassApp/pull/1138), and foundation [#1139](https://github.com/KlassApp-Foundation/KlassApp/pull/1139). Calendar is [#1149](https://github.com/KlassApp-Foundation/KlassApp/pull/1149). Gmail, Docs, Slides, and Tasks are not connectors.

## 1. Google Cloud project

1. Open Google Cloud Console and create a project.
2. OAuth consent screen. User type: **External**. These schools are not one Google Workspace, so Internal would only allow users inside a single organisation.
   - App name, user support email, and a developer contact email.
   - Publishing status: **Testing**. Do not publish the app to production as part of this setup.
   - Add every person who will click Connect as a test user (maximum 100).
   - While the app stays in Testing, Google's verification review is not required. A test user still sees a warning before consent. Because the connector asks for scopes beyond `openid`, `profile`, and `email`, that consent and its refresh token expire seven days after consent. The code requests `access_type=offline`, so it stores a refresh token, and that token expires with the consent. Reconnect after seven days, or publish only after the verification in section 3.
3. Create an OAuth client: Credentials → Create credentials → OAuth client ID → **Web application**. Not a desktop client and not a service account. The Calendar call makes the signed-in user the owner of the new calendar; a service account would own it instead.
4. Authorized redirect URIs, byte for byte. The connector URI is not built from `APP_URL`. `GoogleOAuthService` sends the raw `GOOGLE_CONNECTOR_REDIRECT_URI` value as `redirect_uri`. The route is `GET /admin/settings/google/callback` (`RouteServiceProvider` prefix `admin`, plus `routes/setting.php`).
   - Sign-in, for `APP_URL=https://klassapp.xyz` and `APP_ENV` other than `local`: `https://klassapp.xyz/auth/google/callback`. The code builds that as `rtrim(APP_URL, '/') + '/auth/google/callback'` in `config/services.php`. `routes/web.php` serves `GET /auth/google/callback`.
   - School connectors, production: `https://klassapp.xyz/admin/settings/google/callback`.
   - Local connector example, when the app is on localhost: `http://localhost/admin/settings/google/callback`. If `APP_URL` is a different local host, the path is still `/admin/settings/google/callback` on that host, and the env value must match the authorized URI exactly.
5. Copy the client id and client secret into the environment only. Never commit them.

## 2. APIs to enable

Enable an API on this project before you call it.

| Connector | Pull request | API | Called on this branch |
| --- | --- | --- | --- |
| Account check | #1139 | none (userinfo) | yes |
| Sheets | #1138 | Google Sheets API | yes |
| Drive | #1137 | Google Drive API | yes |
| Classroom | #1140 | Google Classroom API | yes |
| Calendar | #1149 | Google Calendar API | no |

Do not enable the Gmail API, Google Docs API, Google Slides API, or Google Tasks API for these connectors. Nothing in this stack calls them.

## 3. Scopes, and why

Each connector consent asks only for that connector's scopes, plus `https://www.googleapis.com/auth/userinfo.email`, with `include_granted_scopes=true`, `access_type=offline`, and `prompt=consent`. A later connector does not repeat earlier product scopes on the consent URL.

Sign-in is separate. Laravel Socialite's Google driver requests `openid`, `profile`, and `email`.

On this branch, `config/google.php` requests these product scopes:

| Connector | Scope | Google's published label |
| --- | --- | --- |
| Sheets | `https://www.googleapis.com/auth/spreadsheets` | Sensitive ([Sheets scopes](https://developers.google.com/workspace/sheets/api/scopes)) |
| Drive | `https://www.googleapis.com/auth/drive.file` | Non-sensitive. This is not the restricted `drive` or `drive.readonly` scope. |
| Classroom | `https://www.googleapis.com/auth/classroom.courses.readonly` | Read the label in the Cloud Console when you add it. The public scope list does not print Sensitive or Restricted for this URI. |
| Classroom | `https://www.googleapis.com/auth/classroom.rosters.readonly` | Same. Read the console label. |
| Classroom | `https://www.googleapis.com/auth/classroom.coursework.students.readonly` | Same. Read the console label. |

`https://www.googleapis.com/auth/calendar.app.created` is added in #1149. It is not in this branch's catalog. Do not add `calendar`, `calendar.events`, `gmail.send`, `documents`, `presentations`, or `tasks`. The code does not request them.

Add a scope on the consent screen before you connect that tool. Google shows sensitive and restricted scopes with a marker in the console. Verification is not required while Publishing status is Testing. Before you change the app to In production, submit Google's sensitive-scope verification for every scope the console marks sensitive, and a security assessment for any scope it marks restricted. `spreadsheets` is documented as Sensitive, so a public production app needs that verification. These tests have never been run against Google, so the console label is the check.

## 4. Environment variables

Set these in the environment. Do not commit the filled values.

| Name | Production value |
| --- | --- |
| `GOOGLE_CLIENT_ID` | The web client id from section 1. Shared with sign-in. |
| `GOOGLE_CLIENT_SECRET` | The web client secret. |
| `GOOGLE_CONNECTOR_REDIRECT_URI` | `https://klassapp.xyz/admin/settings/google/callback` |
| `APP_URL` | `https://klassapp.xyz` (already the site URL). Sign-in redirect is derived from this. |

Do not set `GOOGLE_REDIRECT_URI` in production. The code uses it only when `APP_ENV` is `local`. Locally it overrides the sign-in callback, not the connector callback.

Leave these unset. Each already defaults to Google's host. Setting one would point the app at a different host.

`GOOGLE_OAUTH_AUTHORIZE_URL`, `GOOGLE_OAUTH_TOKEN_URL`, `GOOGLE_OAUTH_REVOKE_URL`, `GOOGLE_USERINFO_URL`, `GOOGLE_SHEETS_API_BASE_URL`, `GOOGLE_DRIVE_API_BASE_URL`, `GOOGLE_CLASSROOM_API_BASE_URL`, `GOOGLE_CALENDAR_API_BASE_URL`, `GOOGLE_GMAIL_API_BASE_URL`, `GOOGLE_DOCS_API_BASE_URL`, `GOOGLE_SLIDES_API_BASE_URL`, `GOOGLE_TASKS_API_BASE_URL`.

The last four exist in `config/services.php` and no connector calls them. `GOOGLE_DRIVE_MCP_URL`, `GOOGLE_DRIVE_MCP_CLIENT_ID`, and `GOOGLE_DRIVE_MCP_CLIENT_SECRET` belong to a separate Toshi Drive MCP entry. This connector does not read them. Leave that entry disabled.

`TOSHI_GOOGLE_CLASSROOM_ENABLED` defaults to false. Leave it unset for the settings sync and for `google:smoke classroom`. It only turns on the separate Toshi Classroom MCP tools. There is no second Classroom OAuth client.

## 5. Connect, then run one action

1. Sign in as a school admin.
2. Open School Settings → Integrations (`/admin/settings/integrations`).
3. On Connected tools, choose Connect for one tool and finish Google's consent screen. The card shows **Live** for that tool only after this school has granted its scopes. Another school does not see it.
4. Run the command. It prints the result and does not print the token.

```
php artisan google:smoke account --school=SCHOOL_ID
php artisan google:smoke sheets --school=SCHOOL_ID --exam=EXAM_ID
php artisan google:smoke sheets --school=SCHOOL_ID --exam=EXAM_ID --spreadsheet=SPREADSHEET_ID
php artisan google:smoke drive --school=SCHOOL_ID --exam=EXAM_ID --learner=STUDENT_USER_ID
php artisan google:smoke classroom --school=SCHOOL_ID
```

`account` reads the connected Google email and refreshes the token if it is due. `sheets` without `--spreadsheet` creates a mark sheet in the marks-import template. With `--spreadsheet` it reads that sheet and saves marks only through `MarksImportService`. `drive` generates that learner's report card through `StudentReportCardService::generatePdf()` and uploads the PDF under `KlassApp / {term} / {class}` with the `drive.file` scope. `classroom` reads active courses and student names. A course is linked only when one active class in the school has that exact name. A student is linked only when one active student in that class has that exact name. Everyone else is reported as needing review. The command does not create students. There is no button for that sync on the integrations page. The route is `POST /admin/settings/google/classroom/sync`.

The command's signature also names `calendar`, `gmail`, `docs`, `slides`, and `tasks`. They are not in the catalog, so the command says the connector is unknown. `--section` is accepted and not read. Classroom uses `--school` only.

`php artisan google:refresh-tokens` refreshes tokens that expire within 30 minutes. The scheduler runs it every fifteen minutes. A Testing-mode refresh token that has already passed seven days cannot be refreshed; the school has to connect again.

Disconnect on the card revokes the Google token and clears the stored credentials when it is the last connected tool. Disconnecting one tool while another is still connected removes that tool's scopes from KlassApp and leaves the shared token in place, because Google's revoke endpoint cannot drop a single scope.

## 6. What the automated tests prove without a Cloud project

`tests/fixtures/google/fake-google-router.php` is a local HTTP server started by PHPUnit, in the same role as `e2e/whatsapp-sim`. On this branch the feature tests against that server are `tests/Feature/Google/GoogleConnectorFoundationTest.php`, `tests/Feature/Google/GoogleSheetsConnectorTest.php`, `tests/Feature/Google/GoogleDriveConnectorTest.php`, and `tests/Feature/Google/GoogleClassroomConnectorTest.php`. The Classroom test connects, reads courses and students from the fake server, matches exact unique names, creates nobody, and disconnects. It also covers an expired token (refresh), revoked access (`invalid_grant`), and an API error.

`tests/Feature/Toshi/GoogleClassroomSkillTest.php` does not use that fake server. It uses `SpikeGoogleClassroomMockServer`, a local MCP fixture. The real Classroom MCP tools are a second path and are off unless `TOSHI_GOOGLE_CLASSROOM_ENABLED` is set.

None of these tests prove Google's consent screen, Google's scope verification, or a real Classroom API.
