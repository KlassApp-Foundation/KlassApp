# KlassApp · Task D v2: one VitePress site for all docs, plus brand materials (2026-09-29)

- **Designed from:** `main @ 9f3297546d8c`.
- **HANDOFF.md:** five phased PRs: P1 scaffold and theme, P2 Help, P3 Community migration and redirects, P4 GitBook migration and retirement, P5 search and checks.
- **MIGRATION.md:** every Docsify and GitBook page, with its new URL, what merges or is dropped, and the redirects needed.
- **vitepress/docs-site/:** config, theme tokens, 13 Vue components, and page templates for 6 Help and 6 Community page types. **Never installed or built.**
- **CANVA-SPECS.md:** social templates and the pitch deck.
- **design-system/:** the updated design system and the Task D concepts. The social media kit is `concepts/social/kit.html`: 6 content types × 8 formats, 60 artboards at real size.

**Concept:** `design-system/concepts/docs/site.html` is the VitePress-style shell: the docs home with two doors, a Help/Community switch, a search dialog opened with Ctrl K that covers both sections, 6 Help templates and 6 Community templates. `index.html` shows every page at 1280 and 375.

**Not browser-measured:** contrast ratios (calculated from hex), print output, Canva, and the VitePress build and performance budgets.

**Update 2026-10-02:** HANDOFF P6 adds **Blog and Events** (concept `design-system/concepts/docs/blog/index.html`; Vue components `PreviewLabel`, `PostList`, `PostMeta`, `EventList`, `EventTime`, `AddToCalendar`; first post "KlassApp opens to founding schools"). The voice guide and readme carry the agreed positioning.
