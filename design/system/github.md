repo: KlassApp-Foundation/KlassApp
branch: main

## Last sync
date: 2026-09-28T18:24:20Z
commit: dbe68419faba

### Updated in this project
- Form label 0.82 → 0.85rem mirrored into styles/classes.css and the buttons/forms review page
- Added --d-avatar-ring token (new upstream, used by x-profile-photo's circle)
- Charts review marked shipped (colours, value labels, required empty-message, fallback palette)

## Sync history
- 2026-09-27T03:20:52Z — component review pages; success button retired; SoftDeletes + chart-palette investigations (no commit recorded)

## Screen map
| Project screen / file | Repo source |
|---|---|
| tokens/*.css | resources/assets/design-system/tokens/{colors,fonts,radii-shadows,spacing,typography}.css, public/css/dashboard-refresh.css :root |
| styles/classes.css | public/css/dashboard-refresh.css |
| ui_kits/toshi (panel, pill, dock) | packages/toshi-ui/resources/css/toshi-ui.css, resources/views/layouts/partials/toshi-embed.blade.php, toshi-prepaint.blade.php |
| Dashboard chrome, sidebar, sidebar footer | public/css/dashboard-refresh.css, resources/views/layouts/admin/sidebar.blade.php, layouts/partials/sidebar-footer.blade.php |
| App shell | resources/views/layouts/app.blade.php |
| Mobile nav behaviour | public/js/custom.js (#mobile-menu-trigger delegated handler) |
| guidelines/component-review-*.html | public/css/dashboard-refresh.css, resources/views/components/{chart,table,profile-photo}.blade.php, app/Livewire/Superadmin/*, resources/views/admin/dashboard/dashboard.blade.php |
| concepts/toshi-tower, toshi-orbit, landing-* | resources/views/landing-v2.blade.php, partials/landing-toshi-tower.blade.php, resources/css/landing-preview.css |
