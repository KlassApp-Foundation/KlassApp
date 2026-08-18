# Agent Guidance

Follow the scoped rules in [`.ai/rules/index.md`](.ai/rules/index.md) before planning or editing files.

- Prefer PHPStorm/Laravel Idea structured tools (`get_file_text_by_path`, `get_eloquent_model`, `get_routes`, `run_inspections`) for repository investigation.
- Prefer Laravel Boost tools (`application-info`, `search-docs`, `database-schema`, `database-query`) for Laravel, database, and package questions.
- Use shell search only when those structured tools cannot perform the task.
- Match existing Laravel conventions, run the narrowest relevant PHPUnit test, and use the standing branch → PR → merge → deploy flow.
- Never make raw production database writes; use tested application paths such as Artisan commands, seeders, or the UI.
