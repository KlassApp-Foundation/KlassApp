<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * SiteAdmin (platform) path for the per-school access switches.
 * Rule #1: production data changes go through a committed Artisan command, and
 * rule #13: it targets ONE explicit school — never a blanket platform write.
 */
namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;

class SchoolAccessCommand extends Command
{
    protected $signature = 'school:access
                            {school : The school id to target}
                            {--login= : on|off (that school\'s login switch)}
                            {--maintenance= : on|off (that school\'s maintenance mode)}
                            {--attendance-scope= : class_teacher_only|classes_i_teach|school_wide}
                            {--teacher-receptionist= : on|off (teachers may write reception-desk records)}';

    protected $description = "Set ONE school's access switches (per-school; never global)";

    public function handle(): int
    {
        $school = School::find($this->argument('school'));

        if (! $school) {
            $this->error('School not found: '.$this->argument('school'));

            return self::FAILURE;
        }

        $reception = $this->option('teacher-receptionist');

        if ($reception !== null) {
            if (! array_key_exists((string) $reception, ['on' => '1', 'off' => '0', '1' => '1', '0' => '0'])) {
                $this->error('--teacher-receptionist must be on|off');

                return self::FAILURE;
            }

            $value = ['on' => '1', 'off' => '0', '1' => '1', '0' => '0'][(string) $reception];
            $school->setDetailValue(\App\Helpers\SiteHelper::TEACHER_RECEPTIONIST_ACCESS_KEY, $value);
            \App\Helpers\SiteHelper::forgetTeacherReceptionistAccess((int) $school->id);
            $this->info("school {$school->id} ({$school->name}): teacher_receptionist_access = {$value}");
        }

        $scope = $this->option('attendance-scope');

        if ($scope !== null) {
            $scope = (string) $scope;

            if (! in_array($scope, \App\Helpers\SiteHelper::ATTENDANCE_SCOPES, true)) {
                $this->error('--attendance-scope must be one of: '.implode('|', \App\Helpers\SiteHelper::ATTENDANCE_SCOPES));

                return self::FAILURE;
            }

            $school->setDetailValue(\App\Helpers\SiteHelper::ATTENDANCE_SCOPE_KEY, $scope);
            \App\Helpers\SiteHelper::forgetAttendanceScope((int) $school->id);
            $this->info("school {$school->id} ({$school->name}): attendance_scope = {$scope}");
        }

        $map = ['on' => '1', 'off' => '0', '1' => '1', '0' => '0'];

        foreach (['login' => 'login_status', 'maintenance' => 'maintenance'] as $option => $metaKey) {
            $value = $this->option($option);

            if ($value === null) {
                continue;
            }

            if (! array_key_exists((string) $value, $map)) {
                $this->error("--{$option} must be on|off");

                return self::FAILURE;
            }

            $school->setDetailValue($metaKey, $map[(string) $value]);
            $this->info("school {$school->id} ({$school->name}): {$metaKey} = {$map[(string) $value]}");
        }

        return self::SUCCESS;
    }
}
