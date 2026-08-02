# Toshi Deputy Admin (ug4) — Part B implementation notes

> Branch: `feature/toshi-deputy-admin-role`  
> Date: 2026-08-02  
> Scope: web Toshi for SchoolSubadmin / Deputy Admin only. WhatsApp for ug4 explicitly out of scope.

---

## Role facts

| Fact | Value |
|---|---|
| Usergroup | **4** (`schoolsubadmin`) |
| Panel | Same admin UI as ug3 except Settings (owner-level) blocked via `MustBeFullSchoolAdmin` |
| Toshi agent | `DeputyAdminOperationsAgent` |
| Gate | `toshi-deputy-action` (ug4 + `school_id`; ug1 impersonating ug4) |
| Availability | Unchanged — school `toshi_enabled` via `ToshiAvailabilityGate` |
| Blade allowlist | `[1, 3, 4, 5, 6, 8, 10, 11]` in `layouts/app.blade.php` |

---

## AddCoAdminTool exclusion — why (explicit)

`AddCoAdminTool` is **not** one of the Settings fields (school name / logo / plan / academic-year). It is still excluded because creating ug3 co-admins is **owner-level governance**. Product rule: “deputy shouldn’t make owner-level changes.” Documented on:

1. Class docblock of `App\AiAgents\DeputyAdminOperationsAgent`
2. This file
3. `docs/toshi-role-parity-audit.md` (Deputy section)

`SetCurriculumTool` is excluded as academic-year / curriculum Settings config.

---

## Authorization model

| Layer | Behaviour |
|---|---|
| `toshi-school-action` | **Unchanged** — ug3 only (or ug1→ug3 impersonation) |
| `toshi-deputy-action` | **New** — ug4 + school_id (or ug1→ug4 impersonation) |
| Shared tools `authorizeOrMessage()` | Dual-allow school **OR** deputy |
| `AddCoAdminTool` / `SetCurriculumTool` | `authorizeSchoolAdminOrMessage()` — ug3-only fail-closed |
| Structural isolation | `DeputyAdminOperationsAgent::tools()` never registers the two owner tools |

---

## Tool surface

Same concrete tools as `ToshiOrchestrator` → six skills, minus `AddCoAdminTool` and `SetCurriculumTool` (22 tools, flattened on the ops agent).

ug3 still reaches:

- `AddCoAdminTool` via `TeacherSkill` / confirm map `toolAddCoAdmin`
- `SetCurriculumTool` via AgentToshi `TOOL_CLASS_MAP['toolSetCurriculum']` (orphan — not on skill agents)

---

## WhatsApp (verify only — no ug4 channel)

`WhatsAppWriteExclusion` is **agent-agnostic**: it filters by `ConfirmsBeforeWrite` trait, `RouteTo*SkillTool` class-name pattern, and a hard denylist (`ManagePayrollTool`, `ImpersonateSchoolAdminTool`). It does **not** branch on agent class or usergroup.

Evidence:

- `app/AiAgents/WhatsApp/WhatsAppWriteExclusion.php` — `isDenied()` / `allowsClass()` inspect traits + class names only
- `WhatsAppToshiChannelService::isAvailableFor()` already fail-closes ug4 (`usergroup_id === 4` → false)
- Therefore if WhatsApp were ever extended to ug4, DeputyAdmin write tools that use `ConfirmsBeforeWrite` would be stripped automatically by the same exclusion path

---

## Tests

`tests/Feature/Toshi/DeputyAdmin/DeputyAdminOperationsToolsTest.php`
