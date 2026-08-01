<?php

namespace App\AiAgents\WhatsApp;

use App\Ai\Tools\Superadmin\ImpersonateSchoolAdminTool;
use App\AiAgents\Concerns\ConfirmsBeforeWrite;
use App\AiAgents\Tools\Accountant\ManagePayrollTool;
use Laravel\Ai\Contracts\Tool;

/**
 * Structural write exclusion for the WhatsApp Toshi channel (v1 read-only).
 *
 * Tools that use ConfirmsBeforeWrite, skill routers that embed writes, and
 * hard-denied surfaces (payroll / impersonation) are never exposed over WhatsApp
 * — even when the underlying web OperationsAgent registers them.
 */
class WhatsAppWriteExclusion
{
    /**
     * Hard denylist — never expose on WhatsApp even after confirmation bridge.
     *
     * @var list<class-string>
     */
    public const HARD_DENY = [
        ManagePayrollTool::class,
        ImpersonateSchoolAdminTool::class,
    ];

    public static function isDenied(object $tool): bool
    {
        if (in_array($tool::class, self::HARD_DENY, true)) {
            return true;
        }

        $traits = class_uses_recursive($tool);
        if (isset($traits[ConfirmsBeforeWrite::class])) {
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
        if (in_array($toolClass, self::HARD_DENY, true)) {
            return false;
        }

        if (str_contains($toolClass, '\\RouteTo') && str_ends_with($toolClass, 'SkillTool')) {
            return false;
        }

        if (! class_exists($toolClass)) {
            return false;
        }

        $traits = class_uses_recursive($toolClass);

        return ! isset($traits[ConfirmsBeforeWrite::class]);
    }
}
