<?php

namespace Tests\Unit\Support;

use App\Helpers\WhatsAppPhoneHelper;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsAppUser;
use App\Support\PhoneCountry;
use Illuminate\Support\Facades\Validator;
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

    public function test_a_number_with_a_country_code_is_parsed_as_typed_whatever_the_school_country(): void
    {
        // The school is in Kenya, the number is American: the code wins.
        $this->assertSame('+14155552671', PhoneCountry::normalise('+1 415 555 2671', 'Kenya'));
        $this->assertSame('+14155552671', PhoneCountry::normalise('+1 415 555 2671', null));
        // A wrong-looking number with a code is still not silently re-homed.
        $this->assertStringStartsWith('+1', (string) PhoneCountry::normalise('+1 415 555 2671', 'Uganda'));
    }

    public function test_a_national_number_uses_the_schools_chosen_country(): void
    {
        $this->assertSame('+254712345678', PhoneCountry::normalise('0712 345 678', 'Kenya'));
        $this->assertSame('+254712345678', PhoneCountry::normalise('712345678', 'Kenya'));
        $this->assertSame('+256701234567', PhoneCountry::normalise('0701 234 567', 'Uganda'));
    }

    public function test_uganda_is_the_fallback_only_when_no_country_is_known(): void
    {
        $this->assertSame('+256701234567', PhoneCountry::normalise('0701 234 567', null));
        $this->assertSame('+256701234567', PhoneCountry::normalise('0701 234 567', ''));
        $this->assertSame('+256701234567', PhoneCountry::normalise('0701 234 567', 'Nowhere Land'));
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

    public function test_a_kenyan_number_does_not_match_a_ugandan_local_number_at_another_school(): void
    {
        DB::table('usergroups')->insert([
            'id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $uganda = School::create([
            'name' => 'Uganda Phone School',
            'email' => 'ug-phone@school.test',
            'phone' => '0700111222',
            'slug' => 'ug-phone-school',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);

        $kenya = School::create([
            'name' => 'Kenya Phone School',
            'email' => 'ke-phone@school.test',
            'phone' => '0700111333',
            'slug' => 'ke-phone-school',
            'status' => 1,
            'registration_country' => 'Kenya',
            'toshi_enabled' => 0,
        ]);

        $ugandan = User::create([
            'school_id' => $uganda->id,
            'usergroup_id' => 7,
            'name' => 'Ugandan Parent',
            'email' => 'ug-parent@school.test',
            'mobile_no' => '0712345678',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $ugandan->forceFill(['mobile_no' => '0712345678'])->saveQuietly();

        $kenyan = User::create([
            'school_id' => $kenya->id,
            'usergroup_id' => 7,
            'name' => 'Kenyan Parent',
            'email' => 'ke-parent@school.test',
            'mobile_no' => '0712345678',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $kenyan->forceFill(['mobile_no' => '0712345678'])->saveQuietly();

        $matched = User::matchingPhone('+254712345678', 'Uganda')->orderBy('id')->get();

        $this->assertFalse(
            $matched->contains('id', $ugandan->id),
            'A Kenyan +254 number must not match a Ugandan user stored as 0712345678.'
        );
        $this->assertTrue($matched->contains('id', $kenyan->id));
        $this->assertNotContains('0712345678', PhoneCountry::keys('+254712345678', 'Uganda'));
        $this->assertNotContains('712345678', PhoneCountry::keys('+254712345678', 'Uganda'));

        $ugandanOnly = User::matchingPhone('+256712345678', 'Uganda')->orderBy('id')->get();
        $this->assertTrue($ugandanOnly->contains('id', $ugandan->id));
        $this->assertFalse($ugandanOnly->contains('id', $kenyan->id));

        $this->assertSame($ugandan->id, User::findBySubmittedPhone('0712345678', 'Uganda', 7)?->id);
        $this->assertSame($kenyan->id, User::findBySubmittedPhone('+254712345678', 'Kenya', 7)?->id);
        $this->assertNull(User::matchingPhone('+254712345678', 'Uganda', (int) $uganda->id)->first());
        $this->assertSame($kenyan->id, User::matchingPhone('+254712345678', 'Kenya', (int) $kenya->id)->first()?->id);
    }

    public function test_a_lowercase_country_code_is_not_read_as_uganda(): void
    {
        DB::table('usergroups')->insert([
            'id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame('NG', PhoneCountry::region('ng'));
        $this->assertSame('CD', PhoneCountry::region('cd'));
        $this->assertSame('+234', PhoneCountry::dial('ng'));
        $this->assertSame('+243', PhoneCountry::dial('cd'));
        $this->assertSame('+2348031234567', PhoneCountry::normalise('08031234567', 'ng'));
        $this->assertSame('+243812345678', PhoneCountry::normalise('0812345678', 'cd'));
        $this->assertSame('+254712345678', PhoneCountry::normalise('+254712345678', 'ng'));

        $nigeria = School::create([
            'name' => 'Nigeria Phone School',
            'email' => 'ng-phone@school.test',
            'phone' => '08031234567',
            'slug' => 'ng-phone-school',
            'status' => 1,
            'registration_country' => 'ng',
            'toshi_enabled' => 0,
        ]);

        $parent = User::create([
            'school_id' => $nigeria->id,
            'usergroup_id' => 7,
            'name' => 'Nigerian Parent',
            'email' => 'ng-parent@school.test',
            'mobile_no' => '08031234567',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->assertSame('+2348031234567', $parent->fresh()->mobile_no);
    }

    public function test_unreadable_input_is_not_stored_as_a_plus_number(): void
    {
        $this->assertNull(PhoneCountry::normalise('12345', 'Uganda'));
        $this->assertNull(WhatsAppPhoneHelper::normalise('12345'));
        $this->assertSame([], PhoneCountry::keys('12345', 'Uganda'));
        $this->assertNull(User::findBySubmittedPhone('12345', 'Uganda', 7));
    }

    public function test_phone_fields_reject_values_longer_than_20_characters(): void
    {
        $long = '070123456789012345678';
        $this->assertGreaterThan(PhoneCountry::MAX_INPUT_LENGTH, strlen($long));
        $this->assertNull(PhoneCountry::normalise($long, 'Uganda'));

        $validator = Validator::make(
            ['mobile_no' => $long, 'father_mobile_no' => $long, 'contact_no' => $long],
            [
                'mobile_no' => 'required|string|max:20|international_phone',
                'father_mobile_no' => 'required|string|max:20|international_phone',
                'contact_no' => 'required|string|max:20|international_phone',
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('mobile_no', $validator->errors()->toArray());
        $this->assertArrayHasKey('father_mobile_no', $validator->errors()->toArray());
        $this->assertArrayHasKey('contact_no', $validator->errors()->toArray());

        $ok = Validator::make(
            ['mobile_no' => '0701234567'],
            ['mobile_no' => 'required|string|max:20|international_phone'],
        );
        $this->assertTrue($ok->passes());
    }

    public function test_whatsapp_lookup_does_not_cross_countries(): void
    {
        DB::table('usergroups')->insert([
            'id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $uganda = School::create([
            'name' => 'Uganda WA School',
            'email' => 'ug-wa@school.test',
            'phone' => '0700111222',
            'slug' => 'ug-wa-school',
            'status' => 1,
            'registration_country' => 'Uganda',
            'toshi_enabled' => 0,
        ]);

        $parent = User::create([
            'school_id' => $uganda->id,
            'usergroup_id' => 7,
            'name' => 'WA Parent',
            'email' => 'wa-parent@school.test',
            'mobile_no' => '0701234567',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $parent->forceFill(['mobile_no' => '0712345678'])->saveQuietly();

        $row = WhatsAppUser::create([
            'phone' => '0712345678',
            'user_id' => $parent->id,
            'school_id' => $uganda->id,
            'opted_in' => true,
        ]);

        $this->assertNull(WhatsAppUser::findByPhone('+254712345678'));
        $this->assertSame($row->id, WhatsAppUser::findByPhone('+256712345678')?->id);
        $this->assertSame($row->id, WhatsAppUser::findByPhone('0712345678')?->id);
        $this->assertNull(WhatsAppUser::findByPhone('+254712345678', (int) $uganda->id));
    }
}
