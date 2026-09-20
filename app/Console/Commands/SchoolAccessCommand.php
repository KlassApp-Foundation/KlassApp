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
                            {--maintenance= : on|off (that school\'s maintenance mode)}';

    protected $description = "Set ONE school's access switches (per-school; never global)";

    public function handle(): int
    {
        $school = School::find($this->argument('school'));

        if (! $school) {
            $this->error('School not found: '.$this->argument('school'));

            return self::FAILURE;
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
