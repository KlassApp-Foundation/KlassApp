<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $settings = [
            [
                'key'         => 'sitetitle',
                'name'        => 'Site Title',
                'description' => 'Site title shown in browser tab / title bar',
                'value'       => 'School-Plus',
                'field'       => json_encode([
                    'name'  => 'value',
                    'label' => 'Value',
                    'title' => 'Site Title',
                    'type'  => 'text',
                ]),
                'active'      => 1,
            ],

            [
                'key'         => 'sitename',
                'name'        => 'Site Name',
                'description' => 'Name used in emails, footer, copyrights, etc.',
                'value'       => 'School-Plus',
                'field'       => json_encode([
                    'name'  => 'value',
                    'label' => 'Value',
                    'title' => 'Site Name',
                    'type'  => 'text',
                ]),
                'active'      => 1,
            ],

            [
                'key'         => 'sitelogo',
                'name'        => 'Site Logo',
                'description' => 'Website logo. Recommended size: 220px × 45px',
                'value'       => 'images/logo.png',
                'field'       => json_encode([
                    'name'  => 'value',
                    'label' => 'Value',
                    'type'  => 'browse',
                ]),
                'active'      => 1,
            ],

            [
                'key'         => 'favicon',
                'name'        => 'Favicon',
                'description' => 'Website favicon (browser tab icon)',
                'value'       => 'images/favicon.png',
                'field'       => json_encode([
                    'name'  => 'value',
                    'label' => 'Value',
                    'title' => 'Site Favicon',
                    'type'  => 'browse',
                    'disk'  => 'uploads',
                ]),
                'active'      => 1,
            ],

            [
                'key'         => 'maintenance',
                'name'        => 'Maintenance Mode',
                'description' => 'Enable/disable site-wide maintenance mode',
                'value'       => '0',
                'field'       => json_encode([
                    'name'    => 'value',
                    'label'   => 'Maintenance',
                    'type'    => 'radio',
                    'options' => ['1' => 'Active', '0' => 'Inactive'],
                ]),
                'active'      => 1,
            ],

            [
                'key'         => 'login_status',
                'name'        => 'Login',
                'description' => 'Allow users to log in',
                'value'       => '1',
                'field'       => json_encode([
                    'name'    => 'value',
                    'label'   => 'User Login',
                    'type'    => 'radio',
                    'options' => ['1' => 'Active', '0' => 'Inactive'],
                ]),
                'active'      => 1,
            ],

            [
                'key'         => 'register_status',
                'name'        => 'Registration',
                'description' => 'Allow new user registrations',
                'value'       => '1',
                'field'       => json_encode([
                    'name'    => 'value',
                    'label'   => 'Register Status',
                    'type'    => 'radio',
                    'options' => ['1' => 'Active', '0' => 'Inactive'],
                ]),
                'active'      => 1,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],  // unique by key
                [
                    ...$setting,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}