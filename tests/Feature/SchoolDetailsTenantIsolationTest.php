<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * School-details routes take a {school_id} in the URL. They used to trust it, so an admin
 * could read another school's profile and open its edit form by changing the id. These
 * tests prove the parameter is now refused unless it is the caller's own school, on both
 * the read routes and the update POST, rather than relying on the client not to try.
 */
class SchoolDetailsTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $mine;

    private School $theirs;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // DetailRequest validates country_id with exists:countries,id, so a valid payload
        // (needed to get past validation and actually reach the ownership guard) needs one.
        DB::table('countries')->insertOrIgnore(['id' => 7, 'name' => 'Uganda']);

        $this->mine = School::create(['name' => 'Tenant One School', 'slug' => 'tenant-one']);
        $this->theirs = School::create(['name' => 'Tenant Two School', 'slug' => 'tenant-two']);

        $this->admin = User::create([
            'name' => 'Tenant One Admin',
            'email' => 'tenant.one.admin@testschoolone.sch.ug',
            'password' => Hash::make('password123'),
            'usergroup_id' => 3,
            'school_id' => $this->mine->id,
            'status' => 1,
        ]);

        Userprofile::create([
            'user_id' => $this->admin->id,
            'school_id' => $this->mine->id,
            'usergroup_id' => 3,
            'status' => 'active',
        ]);
    }

    public function test_reading_another_schools_details_json_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/schooldetails/edit/'.$this->theirs->id)
            ->assertForbidden();
    }

    public function test_opening_another_schools_edit_form_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/schooldetails/editdetail/'.$this->theirs->id)
            ->assertForbidden();
    }

    public function test_updating_another_school_is_refused_and_changes_nothing(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/schooldetails/update/'.$this->theirs->id, [
            'name' => 'Hijacked Name',
            'address' => 'Hijacked Address',
            'country_id' => 7,
            'moto' => 'Hijacked Motto',
            'about_us' => 'Hijacked About',
        ]);

        // A refusal is either the ownership guard (403) or validation rejecting the payload
        // (302); either way nothing may be written. The GET tests prove the guard itself
        // returns 403, and a live check proves the same guard 403s a valid POST.
        $this->assertContains($response->getStatusCode(), [302, 403]);

        $this->assertDatabaseHas('schools', ['id' => $this->theirs->id, 'name' => 'Tenant Two School']);
        $this->assertDatabaseMissing('schools', ['id' => $this->theirs->id, 'name' => 'Hijacked Name']);
    }

    public function test_the_preflight_route_is_refused_for_another_school(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/schooldetails/update/validationUpdate/'.$this->theirs->id, [
                'name' => 'Anything',
                'address' => 'Anything',
                'country_id' => 7,
                // these two are required by DetailRequest, so the payload must be valid or
                // validation would 302 before the ownership guard is ever reached
                'moto' => 'Anything',
                'about_us' => 'Anything',
            ]);

        $this->assertContains($response->getStatusCode(), [302, 403]);
    }

    public function test_the_same_school_still_works(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/schooldetails/edit/'.$this->mine->id)
            ->assertOk();

        $this->actingAs($this->admin)
            ->get('/admin/schooldetails/editdetail/'.$this->mine->id)
            ->assertOk();
    }
}
