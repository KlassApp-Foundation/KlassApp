# Staging verify — docs tree route (#665)

- **Merge SHA**: `3c3d442ff0f1ee1351e071e8410a45ed63bf0e5e`
- **Staging deploy**: `depl-a2c36189-21dc-42c1-ad54-d8f0453ce03a` → `deployment.succeeded`
- **URL**: `https://klassapp-staging-7mpoqg.laravel.cloud`

## HTTP

| Path | Status | Content-Type |
|---|---|---|
| `/docs/shared/docsify-klassapp.css` | 200 | `text/css; charset=utf-8` |
| `/docs/community/` | 200 | `text/html; charset=utf-8` |
| `/docs/dev/` | 200 | `text/html; charset=utf-8` |
| `/docs/` | 200 | `text/html; charset=utf-8` |
| `/docs/roadmap.md` | 200 | `text/markdown; charset=utf-8` |
| `/docs/architecture.md` | 200 | `text/markdown; charset=utf-8` |
| `/docs/evidence/...` | 404 | (denied) |
| `/docs/toshi-whatsapp-channel-audit.md` | 404 | (denied) |
| `/docs/legacy-portal-idor.md` | 404 | (denied) |

## Playwright (live staging)

Docsify pages: community, dev, hub, hub `#/roadmap` — `--d-canvas` `#fafaf5`, `body` bg `rgb(250, 250, 245)`, theme CSS 200, no CSS 404 console errors. See `CHECKS.json` + screenshots.
