---
paths:
  - app/Helpers/SiteHelper.php
---

# Helpers

## Current academic year uses status=1, not description text
SiteHelper::getAcademicYear() resolves the current year by academic_years.status = 1 (with a legacy description fallback). Description is free text from the wizard/admin form — never filter on the magic string "Current Academic Year" as the primary signal. Cache key academic_year_for_school_{id} must be forgotten on AcademicYear create/update/delete (AcademicYearObserver + wizard saveAcademicYear).
