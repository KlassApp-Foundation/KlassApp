<?php

namespace App\Services;

use App\Models\StandardLink;
use App\Models\User;

/**
 * Kept so the wizard, Toshi, and the invite form keep one call.
 * The assignment rules live in ClassTeacherAssignmentService.
 */
class ClassTeacherInviteService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, teacher_id?: int}
     */
    public static function invite(User $inviter, StandardLink $standardLink, array $data): array
    {
        return app(ClassTeacherAssignmentService::class)->assign($inviter, $standardLink, $data);
    }
}
