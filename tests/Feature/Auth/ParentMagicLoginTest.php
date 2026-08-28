<?php

namespace Tests\Feature\Auth;

use App\Models\School;
use App\Models\StudentParentLink;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\WhatsAppUser;
use App\Services\ParentMagicLoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ParentMagicLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->insert([
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create(['name' => 'Test School', 'email' => 'test@school.ug', 'status' => 1]);

        $this->parent = User::factory()->create([
            'school_id' => null,
            'usergroup_id' => 7,
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $this->parent->id,
            'usergroup_id' => 7,
            'school_id' => null,
            'firstname' => 'Magic',
            'lastname' => 'Parent',
            'status' => 'active',
        ]);

        $student = User::factory()->create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
        ]);

        StudentParentLink::create([
            'school_id' => $school->id,
            'parent_id' => $this->parent->id,
            'student_id' => $student->id,
            'status' => 1,
        ]);
    }

    /** @test */
    public function valid_magic_link_logs_in_parent_and_redirects_to_dashboard(): void
    {
        $url = app(ParentMagicLoginService::class)->issueLinkForPhone('+256700111222', $this->parent);

        $this->assertNotNull($url);

        $response = $this->get($url);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertSame($this->parent->id, Auth::id());
    }

    /** @test */
    public function magic_link_is_single_use(): void
    {
        $url = app(ParentMagicLoginService::class)->issueLinkForPhone('+256700111223', $this->parent);

        $this->get($url)->assertRedirect(route('parent.dashboard'));
        Auth::logout();

        $this->get($url)->assertForbidden();
    }

    /** @test */
    public function expired_magic_link_is_rejected(): void
    {
        $nonce = 'test-nonce-expired';
        $url = URL::temporarySignedRoute(
            'parent.magic-login',
            now()->addMinutes(15),
            ['user' => $this->parent->id, 'nonce' => $nonce],
        );

        $this->travel(20)->minutes();

        $this->get($url)->assertForbidden();
    }

    /** @test */
    public function tampered_signature_is_rejected(): void
    {
        $url = app(ParentMagicLoginService::class)->issueLinkForPhone('+256700111224', $this->parent);
        $tampered = preg_replace('/signature=[^&]+/', 'signature=invalid', $url);

        $this->get($tampered)->assertForbidden();
    }

    /** @test */
    public function non_parent_user_is_rejected(): void
    {
        $teacher = User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 5,
            'status' => 'active',
        ]);

        $url = URL::temporarySignedRoute(
            'parent.magic-login',
            now()->addMinutes(15),
            ['user' => $teacher->id, 'nonce' => 'teacher-nonce'],
        );

        $this->get($url)->assertForbidden();
    }

    /** @test */
    public function link_generation_is_rate_limited_per_phone(): void
    {
        $service = app(ParentMagicLoginService::class);
        $phone = '+256700111225';

        for ($i = 0; $i < ParentMagicLoginService::RATE_LIMIT_PER_HOUR; $i++) {
            $this->assertNotNull($service->issueLinkForPhone($phone, $this->parent));
        }

        $this->assertNull($service->issueLinkForPhone($phone, $this->parent));

        RateLimiter::clear(ParentMagicLoginService::rateLimitKey($phone));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
