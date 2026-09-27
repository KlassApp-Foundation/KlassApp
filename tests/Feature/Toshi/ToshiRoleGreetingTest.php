<?php

namespace Tests\Feature\Toshi;

use App\Livewire\AgentToshi;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Toshi greeting must be worded for the visitor's own context. Students and
 * parents share the view_attendance capability key, so the greeting has to pick
 * self-wording ("your attendance") or parent-wording ("your children's attendance")
 * by the role's SCOPE, never by action name alone — before this, students were
 * greeted with "Ask me about your children's attendance."
 * (Found by the public-visitor-lens re-check of PRs #811-#813.)
 */
class ToshiRoleGreetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'parent', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Greeting Test School',
            'slug' => 'greeting-test-school',
            'registration_country' => 'Uganda',
            'toshi_enabled' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private int $schoolId;

    private function makeUser(int $usergroup, string $name): User
    {
        $user = User::create([
            'usergroup_id' => $usergroup,
            'school_id' => $this->schoolId,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@greeting.test',
            'password' => bcrypt('greeting-pass'),
            'status' => 'active',
        ]);

        Userprofile::create([
            'user_id' => $user->id,
            'usergroup_id' => $usergroup,
            'school_id' => $this->schoolId,
            'firstname' => $name,
            'lastname' => 'Test',
            'status' => 'active',
        ]);

        return $user->fresh();
    }

    private function firstGreeting(User $user): string
    {
        $this->actingAs($user);
        $component = Livewire::test(AgentToshi::class);

        return $component->get('messages')[0]['text']
            ?? $component->get('messages')[0]['content']
            ?? '';
    }

    /** @test */
    public function student_is_greeted_about_their_own_records_never_children(): void
    {
        $greeting = $this->firstGreeting($this->makeUser(6, 'Solo Student'));

        $this->assertStringContainsString('your attendance', $greeting);
        $this->assertStringContainsString('your marks', $greeting);
        $this->assertStringContainsString('your homework', $greeting);
        $this->assertStringNotContainsString("children's", $greeting, 'students have no children — the greeting must not assume parent context');
    }

    /** @test */
    public function parent_is_greeted_about_their_children(): void
    {
        $greeting = $this->firstGreeting($this->makeUser(7, 'Linked Parent'));

        $this->assertStringContainsString("your children's", $greeting);
        $this->assertStringNotContainsString('your marks', $greeting, 'parents read their children\'s records, not their own marks');
    }
}
