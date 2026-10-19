# Google connectors — local smoke test

This is for a person setting up a Google Cloud project on their own machine. It does not change staging or production, and it does not contain client secrets. Leave the values empty in git. Put the real values only in the local `.env`.

KlassApp sign-in and the school connectors share one OAuth client and use two redirect URIs. Sign-in stays on `/auth/google/callback`. School tools use `GOOGLE_CONNECTOR_REDIRECT_URI`. The connector code does not go through the sign-in Socialite driver, so adding a connector scope does not change the login request.

## 1. Google Cloud project

1. Open Google Cloud Console and create a project for this local trial.
2. Configure the OAuth consent screen.
   - User type: External, while the app is still in Testing.
   - App name, user support email, and a developer contact email.
   - Add yourself as a test user. Publishing the app is not part of this check.
3. Create an OAuth client: Credentials → Create credentials → OAuth client ID → Web application.
4. Authorized redirect URIs (both, byte for byte):
   - Sign-in: `{APP_URL}/auth/google/callback` (for local, `http://localhost/auth/google/callback` unless `APP_URL` is something else).
   - School connectors: the value you will put in `GOOGLE_CONNECTOR_REDIRECT_URI`. Locally that is `http://localhost/admin/settings/google/callback`.
5. Copy the client id and client secret into the local `.env` only.

## 2. APIs to enable

Enable these on the same project before you exercise that connector:

| Connector | API |
| --- | --- |
| Sheets | Google Sheets API |
| Drive | Google Drive API |
| Classroom | Google Classroom API |
| Calendar | Google Calendar API |
| Gmail | Gmail API |
| Docs | Google Docs API |
| Slides | Google Slides API |
| Tasks | Google Tasks API |

The account check (`google:smoke account`) uses the OAuth userinfo endpoint. It does not need a separate API enabled beyond the consent screen.

## 3. Scopes, and why

Each connector asks only for its own scopes, plus `https://www.googleapis.com/auth/userinfo.email` so the school can see which Google account is connected. `include_granted_scopes=true` keeps scopes already granted. A later connector does not repeat the earlier product scopes on the consent URL.

| Connector | Scopes | Why |
| --- | --- | --- |
| Sheets | `https://www.googleapis.com/auth/spreadsheets` | Create a mark sheet and read it back. |
| Drive | `https://www.googleapis.com/auth/drive.file` | File report-card PDFs this app creates. This scope does not read the rest of the school's Drive. |
| Classroom | `classroom.courses.readonly`, `classroom.rosters.readonly`, `classroom.coursework.students.readonly` | Read classes, students, and coursework. No guardian scope and no profile-email scope. |
| Calendar | `https://www.googleapis.com/auth/calendar.events` | Write term dates and school events. |
| Gmail | `https://www.googleapis.com/auth/gmail.send` | Send one message the admin asked for. Restricted scope: Google verification is required before a public app can use it. |
| Docs | `https://www.googleapis.com/auth/documents` | Create a document from a class list. |
| Slides | `https://www.googleapis.com/auth/presentations` | Create a term presentation. |
| Tasks | `https://www.googleapis.com/auth/tasks` | Create a task for a marks deadline. |

Add the scope on the consent screen before you connect that tool. While the app is in Testing, only listed test users can consent.

## 4. Environment variables (local only)

In `.env` (never commit the filled file):

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_CONNECTOR_REDIRECT_URI=http://localhost/admin/settings/google/callback
```

Leave the id and secret blank in `.env.example`. Do not set these on Laravel Cloud from this work.

Sign-in keeps using `APP_URL` + `/auth/google/callback`. `GOOGLE_REDIRECT_URI` is an existing local-only override for that sign-in callback. It is not the connector callback.

## 5. Connect, then run one action

1. Sign in as a school admin on your local app.
2. Open School Settings → Integrations.
3. On Connected tools, choose Connect for one tool and finish Google's consent screen.
4. The card shows **Live** for that tool only. Another school does not see it.
5. Run the command for that connector. It prints the result and does not print the token.

```
php artisan google:smoke account --school=SCHOOL_ID
php artisan google:smoke sheets --school=SCHOOL_ID --exam=EXAM_ID
php artisan google:smoke sheets --school=SCHOOL_ID --exam=EXAM_ID --spreadsheet=SPREADSHEET_ID
php artisan google:smoke drive --school=SCHOOL_ID --exam=EXAM_ID --learner=STUDENT_USER_ID
php artisan google:smoke classroom --school=SCHOOL_ID
```

`account` reads the connected Google email (and refreshes the token if it is due). `sheets` without `--spreadsheet` creates a mark sheet in the marks-import template. With `--spreadsheet` it reads that sheet and saves marks only through the existing marks import validation. `drive` generates that learner's report card through `StudentReportCardService::generatePdf()` and uploads the PDF under `KlassApp / {term} / {class}` with the `drive.file` scope. `classroom` reads active courses and student names. A course is linked only when one active class in the school has that exact name. A student is linked only when one active student in that class has that exact name. Everyone else is reported as needing review. The command does not create students and does not print tokens. Calendar, Gmail, Docs, Slides, and Tasks are not built yet. Until then the command refuses them rather than calling Google.

Disconnect on the card revokes the Google token and deletes the stored tokens when it is the last connected tool. Disconnecting one tool while another is still connected removes that tool's scopes from KlassApp and leaves the shared token in place, because Google's revoke endpoint cannot drop a single scope. Disconnect the last tool, or remove KlassApp's access in the Google account, to revoke the token.

## 6. What the automated tests prove without a Cloud project

`tests/fixtures/google/fake-google-router.php` is a local HTTP server, started by the PHPUnit tests, in the same role as `e2e/whatsapp-sim`. It handles the OAuth authorize, token, refresh, and revoke calls, and each connector's API as that connector is added. The feature tests connect, perform the action, read the result back from the fake server, and disconnect. They also cover an expired token (refresh), revoked access (`invalid_grant`), and an API error. They do not prove Google's production behaviour. That is what this smoke command is for, on a machine that has the client id and secret.
