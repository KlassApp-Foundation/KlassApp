<?php

namespace Tests\Feature\Toshi\Librarian;

use App\AiAgents\LibrarianOperationsAgent;
use App\AiAgents\Tools\AddCoAdminTool;
use App\AiAgents\Tools\Librarian\CreateTaskTool;
use App\AiAgents\Tools\Librarian\ManageBookCategoriesTool;
use App\AiAgents\Tools\Librarian\ManageBooksTool;
use App\AiAgents\Tools\Librarian\ManageLendingTool;
use App\AiAgents\Tools\Librarian\ViewDashboardTool;
use App\AiAgents\Tools\Librarian\ViewLibraryCardsTool;
use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookLending;
use App\Models\LibraryCard;
use App\Models\Task;
use App\Models\User;
use App\Services\ToshiActionService;
use App\Services\ToshiAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Tools\Request;
use Livewire\Livewire;
use Tests\TestCase;

class LibrarianOperationsToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;

    private User $student;

    private int $schoolId;

    private int $academicYearId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'teacher', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'name' => 'librarian', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'name' => 'accountant', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');

        $this->schoolId = DB::table('schools')->insertGetId([
            'name' => 'Librarian Toshi School',
            'slug' => 'librarian-toshi',
            'email' => 'librarian-toshi@test.sch.ug',
            'phone' => '+256700000008',
            'status' => 1,
            'toshi_enabled' => 1,
            'registration_country' => 'Uganda',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->academicYearId = DB::table('academic_years')->insertGetId([
            'school_id' => $this->schoolId,
            'name' => '2026',
            'description' => 'Current Academic Year',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->librarian = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 8,
            'email' => 'librarian.toshi@test.sch.ug',
            'name' => 'Toshi Librarian',
        ]);

        $this->student = User::factory()->create([
            'school_id' => $this->schoolId,
            'usergroup_id' => 6,
            'email' => 'student.lib@test.sch.ug',
            'name' => 'Borrower Student',
        ]);
    }

    public function test_librarian_agent_tools_exclude_admin_tools(): void
    {
        $agent = new LibrarianOperationsAgent;
        $classes = collect($agent->tools())->map(fn ($t) => $t::class)->all();

        $this->assertNotContains(AddCoAdminTool::class, $classes);
        // No admin card-management Toshi tool exists in the stack; assert view-only card tool is present instead.
        $this->assertContains(ViewLibraryCardsTool::class, $classes);
        $this->assertContains(ManageBooksTool::class, $classes);
        $this->assertContains(ManageBookCategoriesTool::class, $classes);
        $this->assertContains(ManageLendingTool::class, $classes);
        $this->assertContains(ViewDashboardTool::class, $classes);
        $this->assertContains(CreateTaskTool::class, $classes);
        $this->assertCount(7, $classes);
    }

    public function test_gates_isolate_librarian_from_school_teacher_accountant(): void
    {
        $this->actingAs($this->librarian);

        $this->assertTrue(Gate::inspect('toshi-school-action', $this->librarian)->denied());
        $this->assertTrue(Gate::inspect('toshi-teacher-action', $this->librarian)->denied());
        $this->assertTrue(Gate::inspect('toshi-accountant-action', $this->librarian)->denied());
        $this->assertTrue(Gate::inspect('toshi-librarian-action', $this->librarian)->allowed());
    }

    public function test_role_capabilities_use_view_library_cards(): void
    {
        $caps = ToshiActionService::getRoleCapabilities(8);
        $this->assertContains('view_library_cards', $caps['actions']);
        $this->assertNotContains('manage_library_cards', $caps['actions']);
    }

    public function test_school_admin_tool_denies_librarian_via_authorize(): void
    {
        $this->actingAs($this->librarian);
        ToshiActionService::$bypassConfirm = true;

        $result = (new AddCoAdminTool)->handle(new Request([
            'name' => 'Should Fail',
            'email' => 'coadmin.fail@test.sch.ug',
        ]));

        $this->assertStringStartsWith('❌', $result);
        ToshiActionService::$bypassConfirm = false;
    }

    public function test_view_library_cards_lookup_by_student_and_card_no(): void
    {
        $this->actingAs($this->librarian);

        LibraryCard::create([
            'school_id' => $this->schoolId,
            'user_id' => $this->student->id,
            'library_card_no' => 88001,
            'book_limit' => 3,
            'status' => 1,
            'expiry_date' => '2027-12-31',
        ]);

        $byStudent = (new ViewLibraryCardsTool)->handle(new Request([
            'student_id' => $this->student->id,
        ]));
        $this->assertStringStartsWith('✅', $byStudent);
        $this->assertStringContainsString('88001', $byStudent);

        $byCard = (new ViewLibraryCardsTool)->handle(new Request([
            'library_card_no' => '88001',
        ]));
        $this->assertStringStartsWith('✅', $byCard);
        $this->assertStringContainsString('Borrower Student', $byCard);
    }

    public function test_manage_books_and_categories_require_confirmation_then_write(): void
    {
        $this->actingAs($this->librarian);
        ToshiActionService::$bypassConfirm = false;
        ToshiActionService::$pendingConfirmPayload = null;

        $categoryId = BookCategory::create([
            'school_id' => $this->schoolId,
            'category' => 'Fiction',
        ])->id;

        $pending = (new ManageBooksTool)->handle(new Request([
            'title' => 'Things Fall Apart',
            'category_id' => $categoryId,
            'book_code' => 'BK-8801',
            'author' => 'Chinua Achebe',
            'quantity' => 2,
        ]));
        $decoded = json_decode($pending, true);
        $this->assertTrue($decoded['__tier2_confirm'] ?? false);
        $this->assertSame('toolLibrarianManageBooks', $decoded['tool']);
        $this->assertFalse(Book::where('book_code', 'BK-8801')->exists());

        ToshiActionService::$bypassConfirm = true;
        $ok = (new ManageBooksTool)->handle(new Request([
            'title' => 'Things Fall Apart',
            'category_id' => $categoryId,
            'book_code' => 'BK-8801',
            'author' => 'Chinua Achebe',
            'quantity' => 2,
        ]));
        $this->assertStringStartsWith('✅', $ok);
        $this->assertTrue(Book::where('school_id', $this->schoolId)->where('book_code', 'BK-8801')->exists());

        $catPending = (new ManageBookCategoriesTool)->handle(new Request([
            'category' => 'Science',
        ]));
        // bypass still true — writes immediately
        $this->assertStringStartsWith('✅', $catPending);
        $this->assertTrue(BookCategory::where('school_id', $this->schoolId)->where('category', 'Science')->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_manage_lending_happy_path(): void
    {
        $this->actingAs($this->librarian);
        ToshiActionService::$bypassConfirm = true;

        $categoryId = BookCategory::create([
            'school_id' => $this->schoolId,
            'category' => 'General',
        ])->id;

        Book::create([
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'category_id' => $categoryId,
            'title' => 'Lend Me',
            'book_code' => 'BK-LEND-1',
            'quantity' => 1,
        ]);

        LibraryCard::create([
            'school_id' => $this->schoolId,
            'user_id' => $this->student->id,
            'library_card_no' => 88002,
            'book_limit' => 5,
            'status' => 1,
            'expiry_date' => '2027-12-31',
        ]);

        $result = (new ManageLendingTool)->handle(new Request([
            'library_card_no' => '88002',
            'book_code_no' => 'BK-LEND-1',
            'issue_date' => '2026-08-01',
        ]));

        $this->assertStringStartsWith('✅', $result);
        $this->assertTrue(BookLending::where('library_card_no', 88002)->where('book_code_no', 'BK-LEND-1')->where('status', 'pending')->exists());

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_create_task_and_view_dashboard(): void
    {
        $this->actingAs($this->librarian);
        ToshiActionService::$bypassConfirm = true;

        $task = (new CreateTaskTool)->handle(new Request([
            'title' => 'Shelf inventory',
            'task_date' => '2026-08-10',
        ]));
        $this->assertStringStartsWith('✅', $task);
        $this->assertTrue(Task::where('user_id', $this->librarian->id)->where('title', 'Shelf inventory')->exists());

        $dash = (new ViewDashboardTool)->handle(new Request([]));
        $this->assertStringStartsWith('✅', $dash);

        ToshiActionService::$bypassConfirm = false;
    }

    public function test_unconfirmed_or_read_only_audit_leaves_approver_id_null(): void
    {
        $this->actingAs($this->librarian);

        $log = ToshiAuditService::logExecution(
            user: $this->librarian,
            school: $this->librarian->school,
            toolName: 'ViewLibraryCardsTool',
            arguments: ['student_id' => $this->student->id],
            result: '✅ ok',
            actingUser: $this->librarian,
            approver: null,
        );

        $this->assertSame($this->librarian->id, $log->properties['acting_user_id']);
        $this->assertArrayHasKey('approver_id', $log->properties);
        $this->assertNull($log->properties['approver_id']);
        $this->assertSame($this->librarian->id, $log->causer_id);
        $this->assertTrue(ActivityLog::where('log_name', 'toshi')->where('causer_id', $this->librarian->id)->exists());
    }

    public function test_tier2_confirm_yes_sets_approver_id_to_confirming_librarian(): void
    {
        $this->actingAs($this->librarian);

        Livewire::test(\App\Livewire\AgentToshi::class)
            ->set('schoolId', $this->schoolId)
            ->set('pendingToolConfirm', [
                'tool' => 'toolLibrarianCreateTask',
                'args' => [
                    'title' => 'Confirm me',
                    'task_date' => '2026-08-12',
                ],
            ])
            ->call('confirmYes');

        $log = ActivityLog::where('log_name', 'toshi')
            ->where('school_id', $this->schoolId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Toshi audit log should exist after Tier-2 confirmYes');
        $this->assertSame('toolLibrarianCreateTask', $log->properties['tool']);
        $this->assertSame($this->librarian->id, $log->properties['acting_user_id']);
        $this->assertSame($this->librarian->id, $log->properties['approver_id']);
        $this->assertTrue(Task::where('user_id', $this->librarian->id)->where('title', 'Confirm me')->exists());
    }

    public function test_librarian_cards_route_is_view_only(): void
    {
        $this->actingAs($this->librarian);

        LibraryCard::create([
            'school_id' => $this->schoolId,
            'user_id' => $this->student->id,
            'library_card_no' => 88003,
            'book_limit' => 2,
            'status' => 1,
            'expiry_date' => '2027-06-30',
        ]);

        $response = $this->get(route('library.cards', ['user_id' => $this->student->id]));
        $response->assertOk();
        $response->assertSee('88003');
        $response->assertSee('read-only');
    }
}
