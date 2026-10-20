# Google connectors — setup and local smoke test

This is the setup a person does in Google Cloud and in the app environment. It does not contain client secrets. Leave the values empty in git. Do not set them on Laravel Cloud from a pull request.

KlassApp sign-in and these connectors share one OAuth client and use two redirect URIs. Sign-in stays on Socialite and `/auth/google/callback`. Each person connects their own Google account with `GOOGLE_CONNECTOR_REDIRECT_URI`. That flow does not go through Socialite. Adding a connector scope does not change the login request.

There is one connection per user, not per school. The token is encrypted, and only that user can see it or use it. A school admin chooses which connectors the school allows. That screen shows an anonymous count and does not show anyone's Google account, email, or token. There is no domain-wide delegation and no school service account.

This file is on the Sheets pull request, [#1138](https://github.com/KlassApp-Foundation/KlassApp/pull/1138) (`cursor/google-connectors-sheets-b53f`), stacked on the foundation [#1139](https://github.com/KlassApp-Foundation/KlassApp/pull/1139). Drive, Classroom, and Calendar actions are later pull requests: [#1137](https://github.com/KlassApp-Foundation/KlassApp/pull/1137), [#1140](https://github.com/KlassApp-Foundation/KlassApp/pull/1140), [#1149](https://github.com/KlassApp-Foundation/KlassApp/pull/1149). Gmail, Docs, Slides, and Tasks are not connectors.

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
| Drive | #1137 | Google Drive API | no |
| Classroom | #1140 | Google Classroom API | no |
| Calendar | #1149 | Google Calendar API | no |

Do not enable the Gmail API, Google Docs API, Google Slides API, or Google Tasks API for these connectors. Nothing in this stack calls them.

## 3. Scopes, and why

Each connector consent asks only for that connector's scopes, plus `https://www.googleapis.com/auth/userinfo.email`, with `include_granted_scopes=true`, `access_type=offline`, and `prompt=consent`. A later connector does not repeat earlier product scopes on the consent URL.

Sign-in is separate. Laravel Socialite's Google driver requests `openid`, `profile`, and `email`.

On this branch, `config/google.php` requests these product scopes:

| Connector | Scope | Google's published label |
| --- | --- | --- |
| Every connector | `https://www.googleapis.com/auth/userinfo.email` | The [OAuth scope list](https://developers.google.com/identity/protocols/oauth2/scopes) does not print Sensitive or Restricted for this URI. Read the Cloud Console label. |
| Sheets | `https://www.googleapis.com/auth/spreadsheets` | Sensitive ([Sheets scopes](https://developers.google.com/workspace/sheets/api/scopes), updated 2026-09-03) |
| Drive | `https://www.googleapis.com/auth/drive.file` | Non-sensitive ([Drive scopes](https://developers.google.com/workspace/drive/api/guides/api-specific-auth), updated 2026-09-03). Not the restricted `drive` or `drive.readonly` scope. |
| Classroom | `https://www.googleapis.com/auth/classroom.courses.readonly` | The [Classroom scope list](https://developers.google.com/workspace/classroom/guides/auth) does not print Sensitive or Restricted. Read the Cloud Console label. |
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

The last four exist in `config/services.php` and no connector calls them.

## 5. Connect, then run one action

1. Sign in as the person who will use the tool (school admin, teacher, parent, or student).
2. A school admin opens School Settings → Integrations (`/admin/settings/integrations`) and turns the connector on for the school. That page shows how many people have connected it. It does not show their names or Google accounts.
3. Open My connected tools (`/settings/connected-tools`). Choose Connect for one allowed tool and finish Google's consent screen. The card shows **Live** for that tool only after this user has granted its scopes. Another user, including a school admin at the same school, does not see the account.
4. Run the command as that user. It prints the result and does not print the token.

```
php artisan google:smoke account --user=USER_ID
php artisan google:smoke sheets --user=USER_ID --exam=EXAM_ID
php artisan google:smoke sheets --user=USER_ID --exam=EXAM_ID --spreadsheet=SPREADSHEET_ID
```

`account` reads that user's connected Google email and refreshes the token if it is due. The command refuses to run without `--user`, and it does not load any other user's connection. `sheets` without `--spreadsheet` creates a mark sheet in the marks-import template, as that user, and only if that user may already import marks for the exam. With `--spreadsheet` it reads that sheet and saves marks only through `MarksImportService`. The routes are `POST /admin/settings/google/sheets/export` and `POST /admin/settings/google/sheets/import`.

`drive` and `classroom` are in the catalog, so the command says the action is not registered yet. `calendar`, `gmail`, `docs`, `slides`, and `tasks` are not in the catalog, so the command says the connector is unknown.

A token is refreshed only when that user triggers an action. Nothing on a schedule walks every connection. A Testing-mode refresh token that has already passed seven days cannot be refreshed; that user has to connect again.

Disconnecting the last tool revokes the Google token and deletes the local row. Disconnecting one tool while another is still connected removes that tool's scopes and leaves the token in place, because Google's revoke endpoint cannot drop a single scope.

## 6. What the automated tests prove without a Cloud project

`tests/fixtures/google/fake-google-router.php` is a local HTTP server started by PHPUnit, in the same role as `e2e/whatsapp-sim`. On this branch the feature tests are `tests/Feature/Google/GoogleConnectorFoundationTest.php` and `tests/Feature/Google/GoogleSheetsConnectorTest.php`. They connect one user, create a spreadsheet on the fake server, read it back, save marks only through the marks import validation, and disconnect. They also cover an expired token, revoked access (`invalid_grant`), an API error, another user in the same school, another school, a platform admin, the school allow list, and `google:smoke --user`. They do not prove Google's consent screen, Google's scope verification, or a real Sheets API.
