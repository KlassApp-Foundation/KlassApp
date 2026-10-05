# Screenshots: marks import (demo data only)

Taken from the app (rebased on main after #986, so the teacher template is the one from #986) running on SQLite with the Demo Junior School and Demo Senior School seeders, signed in as the demo teacher and the demo admin. Each case imports an edited copy of the downloaded template: some marks changed, one blank, one above 100, one unknown registration number and one row with no registration number.

File names: `<school>-<role>-<width>-<step>.png`, steps: 1 entry point (button), 2 upload, 3 preview (nothing saved), 4 result. Junior cases tick "overwrite"; senior cases leave it unticked, so differing marks are reported as skipped.

`metrics.json`: measured in the browser at each width. `buttonHeight` is the entry-point button (44px), and `preview`/`result` `sw` and `iw` are page scroll width and window width (equal means no sideways scroll). The "small" list in `preview` shows controls under 44px: the sidebar links (outside this change) and the 24px checkbox, which sits inside a 44px-tall label.
