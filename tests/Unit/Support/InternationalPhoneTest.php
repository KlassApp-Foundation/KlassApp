<?php

namespace Tests\Unit\Support;

use App\Models\School;
use App\Models\User;
use App\Support\PhoneCountry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InternationalPhoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_us_number_is_accepted_when_the_school_country_is_uganda(): void
    {
        $this->assertSame('+14155552671', PhoneCountry::normalise('+1 415 555 2671', 'Uganda'));
        $this->assertSame('+256701234567', PhoneCountry::normalise('0701 234 567', 'Uganda'));
        $this->assertSame('+256', PhoneCountry::dial('Uganda'));
        $this->assertNull(PhoneCountry::normalise('12345', 'Uganda'));
    }

    public function test_lookup_keys_still_match_a_stored_local_number(): void
    {
        DB::table('usergroups')->insert([
            'id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $school = School::create([
            'name' => 'Phone School',
            'email' => 'phone@school.test',
            'phone' => '0700111222',
            'slug' => 'phone-school',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);

        $parent = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => 'Local Parent',
            'email' => 'local-parent@school.test',
            'mobile_no' => '0701234567',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $parent->forceFill(['mobile_no' => '0701234567'])->saveQuietly();

        $this->assertNotNull(User::matchingPhone('+256701234567', 'Uganda')->first());
        $this->assertNotNull(User::matchingPhone('0701234567', 'Uganda')->first());

        $fresh = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 7,
            'name' => 'US Parent',
            'email' => 'us-parent@school.test',
            'mobile_no' => '+1 415 555 2671',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->assertSame('+14155552671', $fresh->fresh()->mobile_no);
    }
}
