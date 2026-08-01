<?php

namespace App\AiAgents\WhatsApp;

use App\Ai\Tools\Superadmin\ImpersonateSchoolAdminTool;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Tools\Accountant\CreateTaskTool as AccountantCreateTaskTool;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use App\AiAgents\Tools\Librarian\CreateTaskTool as LibrarianCreateTaskTool;
use App\AiAgents\Tools\Receptionist\CreateTaskTool as ReceptionistCreateTaskTool;
use App\AiAgents\Tools\Student\ManageTasksTool;
use App\AiAgents\Tools\Teacher\CreateTaskTool as TeacherCreateTaskTool;
use Laravel\Ai\Contracts\Tool;

/**
 * Structural write exclusion for the WhatsApp Toshi channel.
 *
 * Mechanism (wave 1):
 *   exclude if ConfirmsBeforeWrite AND NOT in named WRITE_ALLOWLIST
 *
 * HARD_DENY (payroll / impersonation) is checked first and always wins —
 * even if a class were somehow listed in WRITE_ALLOWLIST.
 *
 * Do NOT strip ConfirmsBeforeWrite from the tools themselves; the trait stays.
 * Detection of ConfirmsBeforeWrite is unchanged — only the allowlist AND-NOT
 * softens the denylist default for the five wave-1 task tools.
 */
class WhatsAppWriteExclusion
{
    /**
     * Hard denylist — never expose on WhatsApp even after confirmation bridge,
     * and even if somehow listed in WRITE_ALLOWLIST.
     *
     * @var list<class-string>
     */
    public const HARD_DENY = [
        ManagePayrollTool::class,
        ImpersonateSchoolAdminTool::class,
    ];

    /**
     * Named wave-1 WhatsApp write allowlist (CreateTask / ManageTasks).
     * School Admin + Parent are intentionally excluded from this wave.
     *
     * @var list<class-string>
     */
    public const WRITE_ALLOWLIST = [
        TeacherCreateTaskTool::class,
        AccountantCreateTaskTool::class,
        LibrarianCreateTaskTool::class,
        ReceptionistCreateTaskTool::class,
        ManageTasksTool::class,
    ];

    public static function isHardDenied(string $toolClass): bool
    {
        return in_array($toolClass, self::HARD_DENY, true);
    }

    public static function isAllowlisted(string $toolClass): bool
    {
        return in_array($toolClass, self::WRITE_ALLOWLIST, true);
    }

    public static function isDenied(object $tool): bool
    {
        // HARD_DENY always wins — checked before allowlist.
        if (self::isHardDenied($tool::class)) {
            return true;
        }

        $traits = class_uses_recursive($tool);

        // exclude if ConfirmsBeforeWrite AND NOT allowlisted
        if (isset($traits[ConfirmsBeforeWrite::class]) && ! self::isAllowlisted($tool::class)) {
            return true;
        }

        // Skill routers invoke nested agents that include write tools.
        if (str_contains($tool::class, '\\RouteTo') && str_ends_with($tool::class, 'SkillTool')) {
            return true;
        }

        return false;
    }

    /**
     * @param  iterable<int, Tool>  $tools
     * @return list<Tool>
     */
    public static function filterReadOnly(iterable $tools): array
    {
        $allowed = [];

        foreach ($tools as $tool) {
            if (! self::isDenied($tool)) {
                $allowed[] = $tool;
            }
        }

        return $allowed;
    }

    /**
     * Public helper for tests — whether a tool class would be invocable on WhatsApp.
     *
     * @param  class-string  $toolClass
     */
    public static function allowsClass(string $toolClass): bool
    {
        // HARD_DENY always wins — checked before allowlist.
        if (self::isHardDenied($toolClass)) {
            return false;
        }

        if (str_contains($toolClass, '\\RouteTo') && str_ends_with($toolClass, 'SkillTool')) {
            return false;
        }

        if (! class_exists($toolClass)) {
            return false;
        }

        $traits = class_uses_recursive($toolClass);

        // exclude if ConfirmsBeforeWrite AND NOT allowlisted
        if (isset($traits[ConfirmsBeforeWrite::class]) && ! self::isAllowlisted($toolClass)) {
            return false;
        }

        return true;
    }
}
