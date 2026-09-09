<?php

namespace Tests\Feature\Superadmin;

use App\Livewire\Superadmin\Academics\UserprofileForm;
use App\Models\City;
use App\Models\Country;
use App\Models\School;
use App\Models\State;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Superadmin userprofile Livewire — UI-only strip of legacy demographics
 * (same Track A/B decisions as Admin/Teacher surfaces).
 */
class UserprofileLegacyFieldsStripTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[DataProvider('surfacesWithoutLegacyLabels')]
    public function surface_does_not_expose_legacy_labels(string $relativePath, array $forbidden): void
    {
        $path = resource_path($relativePath);
        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertNotFalse($source);

        foreach ($forbidden as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $source,
                "{$relativePath} must not contain legacy label '{$needle}'"
            );
        }
    }

    public static function surfacesWithoutLegacyLabels(): array
    {
        $labels = [
            'Blood Group',
            'Birth Place',
            'Native Place',
            'Mother Tongue',
            'Caste',
            'Aadhar Number',
            'Aadhaar Number',
        ];

        return [
            'form' => ['views/livewire/superadmin/academics/userprofile-form.blade.php', $labels],
            'detail' => ['views/livewire/superadmin/academics/userprofile-detail.blade.php', $labels],
        ];
    }

    #[Test]
    public function form_php_no_longer_references_stripped_columns_and_dob_is_optional(): void
    {
        $source = file_get_contents(app_path('Livewire/Superadmin/Academics/UserprofileForm.php'));
        $this->assertNotFalse($source);

        foreach (['blood_group', 'birth_place', 'native_place', 'mother_tongue', 'caste', 'aadhar_number'] as $col) {
            $this->assertStringNotContainsString($col, $source);
        }

        $this->assertStringContainsString('date_of_birth', $source);
        $this->assertDoesNotMatchRegularExpression(
            '/#\[Rule\([\'"]required[\'"]\)\]\s*\n\s*public \$dob/',
            $source
        );
    }

    #[Test]
    public function create_userprofile_succeeds_without_date_of_birth(): void
    {
        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $school = School::create([
            'name' => 'SA Profile School',
            'email' => 'sa.profile@t.sch.ug',
            'phone' => '0700000473',
            'slug' => 'sa-profile-'.uniqid(),
            'status' => 1,
        ]);

        $country = Country::create([
            'name' => 'Uganda',
            'short_name' => 'UG',
            'iso_code' => 'UG',
            'tel_prefix' => '+256',
            'status' => 1,
            'order' => 1,
        ]);

        $state = State::create([
            'country_id' => $country->id,
            'name' => 'Central',
            'status' => 1,
        ]);

        $city = City::create([
            'country_id' => $country->id,
            'state_id' => $state->id,
            'name' => 'Kampala',
            'status' => 1,
        ]);

        $user = User::factory()->create([
            'usergroup_id' => 6,
            'school_id' => $school->id,
            'name' => 'sa.profile.student',
            'registration_number' => 'KLS0000473',
        ]);

        // Create route passes user id; mount only hydrates when id matches a userprofile PK.
        // Empty mount id + explicit userId mirrors create after segment is set.
        Livewire::test(UserprofileForm::class, ['id' => ''])
            ->set('segment', 'create')
            ->set('userId', $user->id)
            ->set('school', $school->id)
            ->set('usergroup', 6)
            ->set('firstname', 'No')
            ->set('lastname', 'Dob')
            ->set('gender', 'male')
            ->set('dob', '')
            ->set('address', 'Kampala')
            ->set('city', $city->id)
            ->set('country', $country->id)
            ->set('pincode', '00000')
            ->set('registration_number', 'KLS0000473')
            ->set('LIN', 'LIN0001')
            ->set('joining_date', '2026-01-15')
            ->set('status', 'active')
            ->call('submitUserprofile')
            ->assertHasNoErrors();

        $profile = Userprofile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertNull($profile->date_of_birth);
        $this->assertSame('NO', $profile->firstname);
        $this->assertNull($profile->blood_group);
        $this->assertNull($profile->aadhar_number);
    }
}
