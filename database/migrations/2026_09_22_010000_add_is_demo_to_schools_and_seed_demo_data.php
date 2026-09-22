<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * The public demo pages were serving whichever active schools came first, with real staff
 * emails (which are their login identifiers) and phone numbers. This adds a deliberate
 * flag for schools intended for public showcase and seeds two synthetic ones, so the demo
 * no longer depends on, or exposes, real tenants.
 *
 * All seeded data is invented: synthetic names, a reserved email domain, no real contact
 * details. Nothing here is a real school, person or credential.
 */
return new class extends Migration
{
    private const DOMAIN = 'demo.klassapp.test';

    public function up(): void
    {
        if (! Schema::hasColumn('schools', 'is_demo')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('is_test');
            });
        }

        $schools = [
            ['name' => 'Lakeview Junior School', 'slug' => 'demo-lakeview-junior', 'motto' => 'Learn, grow, belong', 'year' => '2026'],
            ['name' => 'Model Hill Secondary School', 'slug' => 'demo-model-hill', 'motto' => 'Knowledge and character', 'year' => '2026'],
        ];

        foreach ($schools as $s) {
            // Idempotent per school, so a partial run (or a retry) resumes instead of
            // skipping everything or duplicating it.
            $existing = DB::table('schools')->where('slug', $s['slug'])->first();
            if ($existing) {
                $schoolId = $existing->id;
                DB::table('schools')->where('id', $schoolId)->update(['is_demo' => true, 'is_test' => true]);
            } else {
            $schoolId = DB::table('schools')->insertGetId([
                'name' => $s['name'],
                'motto' => $s['motto'],
                'slug' => $s['slug'],
                // Synthetic contact details on a reserved domain. These columns are required,
                // and they must never resemble a real school's contact information.
                'email' => $s['slug'].'@'.self::DOMAIN,
                'phone' => '070'.str_pad((string) (crc32($s['slug']) % 10000000), 7, '0', STR_PAD_LEFT),
                'is_demo' => true,
                // Also is_test: demo schools are not customers, so they must stay out of the
                // Superadmin platform metrics and the recently-joined feed from the start.
                'is_test' => true,
                'status' => 1,
                'registration_country' => 'Uganda',
                'curriculum' => 'uneb',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            }

            $yearId = DB::table('academic_years')->where('school_id', $schoolId)->where('name', $s['year'])->value('id')
                ?: DB::table('academic_years')->insertGetId([
                    'school_id' => $schoolId,
                    'name' => $s['year'],
                    'status' => 1,
                    'description' => 'Current Academic Year',
                    // start_date and end_date are NOT NULL. Omitting them let this seed pass on
                    // non-strict MySQL but broke every fresh migrate, and therefore the entire
                    // SQLite RefreshDatabase suite. Found and fixed 2026-09-22.
                    'start_date' => now()->startOfYear(),
                    'end_date' => now()->endOfYear(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $people = [
                ['usergroup_id' => 3, 'first' => 'Sandra', 'last' => 'Admin', 'seed' => 'admin'],
                ['usergroup_id' => 5, 'first' => 'Peter', 'last' => 'Okello', 'seed' => 'principal', 'designation' => 'principal'],
                ['usergroup_id' => 5, 'first' => 'Grace', 'last' => 'Nabirye', 'seed' => 'teacher1'],
                ['usergroup_id' => 5, 'first' => 'Samuel', 'last' => 'Kigozi', 'seed' => 'teacher2'],
                ['usergroup_id' => 5, 'first' => 'Rita', 'last' => 'Auma', 'seed' => 'teacher3'],
                ['usergroup_id' => 6, 'first' => 'Daniel', 'last' => 'Ssentongo', 'seed' => 'student1'],
                ['usergroup_id' => 6, 'first' => 'Martha', 'last' => 'Achieng', 'seed' => 'student2'],
                ['usergroup_id' => 7, 'first' => 'Joseph', 'last' => 'Wandera', 'seed' => 'parent1'],
                ['usergroup_id' => 7, 'first' => 'Esther', 'last' => 'Nansubuga', 'seed' => 'parent2'],
                ['usergroup_id' => 8, 'first' => 'Alice', 'last' => 'Kirabo', 'seed' => 'librarian'],
                ['usergroup_id' => 10, 'first' => 'Brian', 'last' => 'Tumusiime', 'seed' => 'receptionist'],
                ['usergroup_id' => 11, 'first' => 'Sarah', 'last' => 'Namuli', 'seed' => 'accountant'],
            ];

            foreach ($people as $p) {
                $email = $p['seed'].'.'.$s['slug'].'@'.self::DOMAIN;
                if (DB::table('users')->where('email', $email)->exists()) {
                    continue; // resume-safe
                }
                $userId = DB::table('users')->insertGetId([
                    'name' => $p['first'].' '.$p['last'],
                    'email' => $email,
                    'password' => Hash::make(str()->random(32)),
                    'usergroup_id' => $p['usergroup_id'],
                    'school_id' => $schoolId,
                    // users.status is an enum, not a boolean. Passing 1 passed only on
                    // non-strict MySQL and failed the CHECK constraint elsewhere.
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('userprofiles')->insert([
                    'user_id' => $userId,
                    'school_id' => $schoolId,
                    'usergroup_id' => $p['usergroup_id'],
                    'firstname' => $p['first'],
                    'lastname' => $p['last'],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if (($p['designation'] ?? null) === 'principal') {
                    // Best effort and isolated: the principal tab is a nice-to-have for the
                    // tutorial showcase, and it must not be able to abort the seed.
                    try {
                        $model = 'App\\Models\\TeacherProfile';
                        if (class_exists($model)) {
                            $profile = new $model;
                            $table = $profile->getTable();
                            \Illuminate\Support\Facades\DB::table($table)->insert([
                                'user_id' => $userId,
                                'school_id' => $schoolId,
                                'academic_year_id' => $yearId,
                                'designation' => 'principal',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::info('demo seed: principal profile skipped: '.$e->getMessage());
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('schools')->where('is_demo', true)->pluck('id')->all();
        if ($ids) {
            $userIds = DB::table('users')->whereIn('school_id', $ids)->pluck('id')->all();
            DB::table('teacherprofiles')->whereIn('school_id', $ids)->delete();
            DB::table('userprofiles')->whereIn('user_id', $userIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
            DB::table('academic_years')->whereIn('school_id', $ids)->delete();
            DB::table('schools')->whereIn('id', $ids)->delete();
        }
        if (Schema::hasColumn('schools', 'is_demo')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }
    }
};
