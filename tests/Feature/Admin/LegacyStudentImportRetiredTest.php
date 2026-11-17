<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\MustBePrivilege;
use App\Http\Middleware\MustBeSchoolAdmin;
use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * T-118: the legacy student import wrote outside OnboardingEngine, and could die
 * mid-file. Its addresses now open the engine-backed Import students page.
 */
class LegacyStudentImportRetiredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([MustBePrivilege::class, MustBeSchoolAdmin::class]);

        DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    #[Test]
    public function the_legacy_student_import_addresses_open_the_engine_backed_page(): void
    {
        [$admin] = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/import')
            ->assertRedirect('/admin/students/import');

        $this->actingAs($admin)
            ->post('/admin/importUsers')
            ->assertRedirect('/admin/students/import');
    }

    #[Test]
    public function the_users_import_cannot_die_mid_file(): void
    {
        $source = file_get_contents(app_path('Imports/UsersImport.php'));

        $this->assertStringNotContainsString('dd(', $source, 'UsersImport must not dump and die on a failed row');
    }

    /** @return array{0: User, 1: School} */
    private function admin(): array
    {
        $school = School::create([
            'name' => 'Legacy Import School',
            'email' => 'legacy@example.test',
            'phone' => '0700222333',
            'slug' => 'legacy-import-school',
            'status' => 1,
            'toshi_enabled' => 0,
        ]);

        $admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Legacy Admin',
            'email' => 'legacy-admin@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified' => 1,
        ]);

        Userprofile::create([
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'usergroup_id' => 3,
            'firstname' => 'Legacy',
            'lastname' => 'Admin',
            'status' => 'active',
        ]);

        return [$admin, $school];
    }
}
