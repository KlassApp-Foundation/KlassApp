<?php

namespace Tests\Feature\Admission;

use App\Models\School;
use App\Models\Standard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public admission class list must return real class names. The legacy
 * integerToRoman transform turned every class name into an empty string.
 */
class StandardListNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_list_returns_raw_class_names(): void
    {
        $school = School::create([
            'name' => 'Standard Names School',
            'slug' => 'standard-names',
            'email' => 'school@standardnames.test',
            'curriculum' => 'uneb',
            'registration_country' => 'Uganda',
            'status' => 'active',
        ]);
        $slug = $school->refresh()->slug;

        Standard::create(['school_id' => $school->id, 'name' => 'S.1', 'order' => 1]);
        Standard::create(['school_id' => $school->id, 'name' => 'Baby Class', 'order' => 2]);

        $response = $this->get('/'.$slug.'/standardlist');

        $response->assertOk();
        $names = collect($response->json('standardlist'))->pluck('name')->all();

        $this->assertContains('S.1', $names);
        $this->assertContains('Baby Class', $names);
        $this->assertNotContains('', $names);
    }
}
