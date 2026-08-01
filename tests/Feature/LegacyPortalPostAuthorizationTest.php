<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Post;
use App\Models\Standard;
use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LegacyPortalPostAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private int $schoolA;

    private int $schoolB;

    private int $academicYearA;

    private int $academicYearB;

    private User $siteAdmin;

    private User $adminA;

    private User $adminB;

    private User $authorA;

    private User $peerA;

    private Post $postA;

    private Post $postB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        require_once __DIR__.'/../Support/activity_stub.php';

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'SiteAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'SchoolAdmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolA = DB::table('schools')->insertGetId([
            'name' => 'Post School A',
            'slug' => 'post-school-a',
            'email' => 'post-a@test.sch.ug',
            'phone' => '+256700000311',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schoolB = DB::table('schools')->insertGetId([
            'name' => 'Post School B',
            'slug' => 'post-school-b',
            'email' => 'post-b@test.sch.ug',
            'phone' => '+256700000322',
            'status' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearA = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolA,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearB = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolB,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$this->schoolA, $this->schoolB] as $sid) {
            Standard::create([
                'school_id' => $sid,
                'name' => 'Primary 1',
                'order' => 1,
                'status' => 1,
            ]);
        }

        $this->siteAdmin = User::factory()->create([
            'school_id' => $this->schoolA,
            'usergroup_id' => 1,
            'email' => 'siteadmin.post@test.sch.ug',
            'name' => 'Site Admin',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->siteAdmin->id,
            'school_id' => $this->schoolA,
            'usergroup_id' => 1,
            'firstname' => 'Site',
            'lastname' => 'Admin',
        ]);

        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA,
            'usergroup_id' => 3,
            'email' => 'admin.a.post@test.sch.ug',
            'name' => 'Admin A',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->adminA->id,
            'school_id' => $this->schoolA,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => 'A',
        ]);

        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB,
            'usergroup_id' => 3,
            'email' => 'admin.b.post@test.sch.ug',
            'name' => 'Admin B',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->adminB->id,
            'school_id' => $this->schoolB,
            'usergroup_id' => 3,
            'firstname' => 'Admin',
            'lastname' => 'B',
        ]);

        $this->authorA = User::factory()->create([
            'school_id' => $this->schoolA,
            'usergroup_id' => 5,
            'email' => 'author.a.post@test.sch.ug',
            'name' => 'Author Teacher',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->authorA->id,
            'school_id' => $this->schoolA,
            'usergroup_id' => 5,
            'firstname' => 'Author',
            'lastname' => 'Teacher',
        ]);

        $this->peerA = User::factory()->create([
            'school_id' => $this->schoolA,
            'usergroup_id' => 5,
            'email' => 'peer.a.post@test.sch.ug',
            'name' => 'Peer Teacher',
            'status' => 'active',
        ]);
        Userprofile::factory()->create([
            'user_id' => $this->peerA->id,
            'school_id' => $this->schoolA,
            'usergroup_id' => 5,
            'firstname' => 'Peer',
            'lastname' => 'Teacher',
        ]);

        $this->postA = $this->makePost(
            schoolId: $this->schoolA,
            academicYearId: $this->academicYearA,
            createdBy: $this->authorA->id,
            description: 'School A post',
        );

        $this->postB = $this->makePost(
            schoolId: $this->schoolB,
            academicYearId: $this->academicYearB,
            createdBy: $this->adminB->id,
            description: 'School B post',
        );
    }

    public function test_author_can_manage_own_post(): void
    {
        $this->actingAs($this->authorA);

        $this->assertTrue(Gate::allows('post', $this->postA));
        $this->assertTrue(Gate::allows('post-destroy', $this->postA));

        $this->get('/teacher/classwall/post/show/'.$this->postA->id)
            ->assertOk();

        $this->get('/teacher/classwall/post/delete/'.$this->postA->id)
            ->assertOk()
            ->assertJsonStructure(['success']);

        $this->assertSoftDeleted('posts', [
            'id' => $this->postA->id,
        ]);
    }

    public function test_peer_author_blocked_from_destroying_another_authors_post(): void
    {
        $this->actingAs($this->peerA);

        $this->assertTrue(Gate::allows('post', $this->postA));
        $this->assertTrue(Gate::denies('post-destroy', $this->postA));

        $this->get('/teacher/classwall/post/delete/'.$this->postA->id)
            ->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'id' => $this->postA->id,
            'deleted_at' => null,
        ]);
    }

    public function test_ug3_can_delete_any_post_in_own_school(): void
    {
        $this->actingAs($this->adminA);

        $this->assertTrue(Gate::allows('post-destroy', $this->postA));

        $this->get('/admin/classwall/post/delete/'.$this->postA->id)
            ->assertOk()
            ->assertJsonStructure(['success']);

        $this->assertSoftDeleted('posts', [
            'id' => $this->postA->id,
        ]);
    }

    public function test_ug3_cannot_delete_post_in_another_school(): void
    {
        $this->actingAs($this->adminA);

        $this->assertTrue(Gate::denies('post-destroy', $this->postB));

        $this->get('/admin/classwall/post/delete/'.$this->postB->id)
            ->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'id' => $this->postB->id,
            'deleted_at' => null,
        ]);
    }

    public function test_ug1_can_destroy_cross_school_post_via_admin(): void
    {
        // MustBeSchoolAdmin allows ug1 onto /admin/*; MustBeTeacher redirects ug1 away.
        $this->actingAs($this->siteAdmin);

        $this->assertTrue(Gate::allows('post-destroy', $this->postA));
        $this->assertTrue(Gate::allows('post-destroy', $this->postB));

        $this->get('/admin/classwall/post/delete/'.$this->postB->id)
            ->assertOk()
            ->assertJsonStructure(['success']);

        $this->assertSoftDeleted('posts', [
            'id' => $this->postB->id,
        ]);
    }

    public function test_show_denies_cross_school_post(): void
    {
        $this->actingAs($this->adminA);
        $this->assertTrue(Gate::allows('post', $this->postA));
        $this->assertTrue(Gate::denies('post', $this->postB));

        $this->get('/admin/classwall/post/show/'.$this->postB->id)
            ->assertForbidden();

        $this->actingAs($this->authorA);
        $this->get('/teacher/classwall/post/show/'.$this->postB->id)
            ->assertForbidden();
    }

    public function test_show_allows_same_school_post(): void
    {
        $this->actingAs($this->adminA);

        $this->get('/admin/classwall/post/show/'.$this->postA->id)
            ->assertOk();
    }

    private function makePost(
        int $schoolId,
        int $academicYearId,
        int $createdBy,
        string $description,
    ): Post {
        $post = new Post([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'entity_id' => $createdBy,
            'entity_name' => 'App\Models\User',
            'description' => $description,
            'attachment_file' => null,
            'visibility' => 'all_class',
            'visible_for' => null,
            'post_created_at' => now(),
            'is_posted' => 1,
            'posted_at' => now(),
            'status' => 'posted',
        ]);
        $post->created_by = $createdBy;
        $post->save();

        return $post->fresh();
    }
}
