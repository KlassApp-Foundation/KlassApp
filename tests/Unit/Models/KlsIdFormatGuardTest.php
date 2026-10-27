<?php

/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 *
 * Tests for the KLS ID format validation guards on both
 * StudentAcademic.klassapp_student_id and User.registration_number.
 *
 * The KLS ID (KLS plus 7 to 16 digits) is the sole parent-to-child
 * linking identifier for WhatsApp, so format integrity is critical.
 */

namespace Tests\Unit\Models;

use App\Models\StudentAcademic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KlsIdFormatGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Minimum seed for FK constraints
        DB::table('usergroups')->insert(['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('schools')->insert(['id' => 1, 'name' => 'Test School', 'slug' => 'test-school', 'created_at' => now(), 'updated_at' => now()]);
    }

    // ──────────────────────────────────────────────
    // StudentAcademic.klassapp_student_id guards
    // ──────────────────────────────────────────────

    public function test_student_academic_accepts_valid_kls_id(): void
    {
        $sa = StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => 'KLS0010427',
        ]);

        $this->assertEquals('KLS0010427', $sa->fresh()->klassapp_student_id);
    }

    public function test_student_academic_accepts_lowercase_kls_id(): void
    {
        $sa = StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => 'kls0010427',
        ]);

        $this->assertEquals('kls0010427', $sa->fresh()->klassapp_student_id);
    }

    public function test_student_academic_accepts_null_kls_id(): void
    {
        $sa = StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => null,
        ]);

        $this->assertNull($sa->fresh()->klassapp_student_id);
    }

    public function test_student_academic_rejects_malformed_kls_id_too_short(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('klassapp_student_id must match format');

        StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => 'KLS12',
        ]);
    }

    public function test_student_academic_rejects_malformed_kls_id_wrong_prefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('klassapp_student_id must match format');

        StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => 'ABC0010427',
        ]);
    }

    public function test_student_academic_accepts_kls_id_past_9999_students(): void
    {
        $sa = StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => 'KLS00110000',
        ]);

        $this->assertSame('KLS00110000', $sa->fresh()->klassapp_student_id);
    }

    public function test_student_academic_rejects_malformed_kls_id_too_many_digits(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('klassapp_student_id must match format');

        StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => 'KLS'.str_repeat('1', 17),
        ]);
    }

    public function test_student_academic_rejects_legacy_numeric_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('klassapp_student_id must match format');

        StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => '290582',
        ]);
    }

    public function test_student_academic_rejects_kls_id_on_update(): void
    {
        $sa = StudentAcademic::create([
            'school_id' => 1,
            'academic_year_id' => 1,
            'user_id' => User::factory()->create(['school_id' => 1, 'usergroup_id' => 6])->id,
            'standardLink_id' => 1,
            'klassapp_student_id' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $sa->update(['klassapp_student_id' => 'BAD_FORMAT']);
    }

    // ──────────────────────────────────────────────
    // User.registration_number guards
    // ──────────────────────────────────────────────

    public function test_user_accepts_valid_kls_registration_number(): void
    {
        $user = User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 6,
            'registration_number' => 'KLS0010427',
        ]);

        $this->assertEquals('KLS0010427', $user->fresh()->registration_number);
    }

    public function test_user_accepts_null_registration_number(): void
    {
        $user = User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 6,
            'registration_number' => null,
        ]);

        $this->assertNull($user->fresh()->registration_number);
    }

    public function test_user_accepts_empty_registration_number(): void
    {
        $user = User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 6,
            'registration_number' => '',
        ]);

        $this->assertEquals('', $user->fresh()->registration_number);
    }

    public function test_user_rejects_malformed_registration_number(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('registration_number must match format');

        User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 6,
            'registration_number' => '290582',
        ]);
    }

    public function test_user_rejects_malformed_registration_number_on_update(): void
    {
        $user = User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 6,
            'registration_number' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $user->update(['registration_number' => 'ABC123']);
    }

    // ──────────────────────────────────────────────
    // Generator still works with guards in place
    // ──────────────────────────────────────────────

    public function test_student_id_generator_output_passes_guard(): void
    {
        $user = User::factory()->create([
            'school_id' => 7,
            'usergroup_id' => 6,
            'registration_number' => null,
        ]);

        // nextForStudent generates and persists — must pass both model guards
        $id = \App\Services\StudentIdGeneratorService::nextForStudent($user);

        $this->assertSame('KLS-7-1', $id);
        $this->assertSame('KLS-7-1', $user->fresh()->registration_number);
    }

    public function test_existing_seven_digit_id_is_not_rewritten_when_the_sequence_passes_9999(): void
    {
        $user = User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 6,
            'registration_number' => 'KLS0010001',
        ]);

        DB::table('student_id_sequences')->insert([
            'school_id' => 1,
            'next_seq' => 10000,
        ]);

        $next = \App\Services\StudentIdGeneratorService::next(1);
        $kept = \App\Services\StudentIdGeneratorService::ensureForStudent($user->fresh());

        $this->assertSame('KLS-1-10000', $next);
        $this->assertSame('KLS0010001', $kept);
        $this->assertSame('KLS0010001', $user->fresh()->registration_number);

        $wide = User::factory()->create([
            'school_id' => 1,
            'usergroup_id' => 6,
            'registration_number' => $next,
        ]);
        $this->assertSame('KLS-1-10000', $wide->fresh()->registration_number);
    }
}
