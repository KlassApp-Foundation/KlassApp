# Bugfix evidence — AY description + WhatsApp duplicate

## Bug 1 — Academic year description mismatch

| Shot | File | Meaning |
|---|---|---|
| Before | `01-before-stuck-empty-state.png` | Walkthrough on `origin/main` @ `d61ca86`: wizard finished with description `AY 2026`, but `SiteHelper::getAcademicYear()` still returned null → empty-state demo, 0 KPIs |
| Reference | `02-reference-after-manual-description-rename.png` | Same walkthrough after manually renaming description back to `"Current Academic Year"` → 7 KPIs (proves the magic-string root cause) |
| After fix | `03-after-fix-custom-ay-description-kpis.png` | This branch: school whose current AY has description **`AY 2026 Custom Label`** (`status=1`) → **7 KPI cards**, no empty-state demo |

## Bug 2 — WhatsApp duplicate phone

Covered by `tests/Feature/Onboarding/WizardWhatsAppDuplicatePhoneTest.php` (asserts friendly validation message; asserts response HTML has no `SQLSTATE` / connection dump).
