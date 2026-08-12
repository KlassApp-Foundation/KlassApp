---
paths:
  - '**/*'
---

# General

## Prefer PHPStorm/Laravel Idea + Laravel Boost tools over grep/bash for codebase investigation
All coding agents must prefer structured tools over raw grep/bash for codebase exploration: get_eloquent_model for model relationships/FKs (resolves relation confusion instantly), get_routes/search_symbol for route/symbol lookups, get_file_text_by_path for file reads, run_inspections for static analysis, and Laravel Boost's search-docs for framework/doc questions. Reserve grep/bash for what these tools genuinely can't cover. Rationale: Aug 12 session spent significant time on manual grep for things these tools do natively — the teacherprofile vs teacherlink confusion, report-card route discovery in admin.php, and repeated DB schema lookups that get_eloquent_model would have resolved in one call.
