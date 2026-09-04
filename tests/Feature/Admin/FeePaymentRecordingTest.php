<?php

namespace Tests\Feature\Admin;

use App\Models\FeePayment;
use App\Models\FeesCategories;
use App\Models\School;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeePaymentRecordingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    private FeesCategories $tuitionNursery;

    private FeesCategories $tuitionPrimary;

    protected function setUp(): void
    {
        parent::setUp();

        \DB::table('usergroups')->insert([
            ['id' => 3, 'name' => 'schooladmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'name' => 'student', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $school = School::create([
            'name' => 'Fee Payment School',
            'email' => 'fees@test.sch.ug',
            'phone' => '0700000088',
            'slug' => 'fee-payment-school',
            'status' => 1,
        ]);

        $nursery = Standard::create([
            'school_id' => $school->id,
            'name' => 'nursery',
            'order' => 1,
            'status' => '1',
        ]);
        $primary = Standard::create([
            'school_id' => $school->id,
            'name' => 'primary',
            'order' => 2,
            'status' => '1',
        ]);

        $this->tuitionNursery = FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $nursery->id,
            'name' => 'Tuition',
            'amount' => 450000,
        ]);
        $this->tuitionPrimary = FeesCategories::create([
            'school_id' => $school->id,
            'standard_id' => $primary->id,
            'name' => 'Tuition',
            'amount' => 450000,
        ]);

        $this->admin = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 3,
            'name' => 'Fee Admin',
            'email' => 'fee.admin@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $this->student = User::create([
            'school_id' => $school->id,
            'usergroup_id' => 6,
            'name' => 'Fee Student',
            'email' => 'fee.student@test.sch.ug',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\MustBeSchoolAdmin::class,
            \App\Http\Middleware\MustBeFullSchoolAdmin::class,
            \App\Http\Middleware\MustBePrivilege::class,
        ]);
    }

    public function test_store_requires_fee_category_and_sets_recorded_by(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.store'), [
                'user_id' => $this->student->id,
                'amount' => 10000,
                'payment_method' => 'cash',
            ])
            ->assertSessionHasErrors('fee_category_id');

        $this->actingAs($this->admin)
            ->post(route('admin.fee-payments.store'), [
                'user_id' => $this->student->id,
                'amount' => 10000,
                'fee_category_id' => $this->tuitionPrimary->id,
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('admin.fee-payments'));

        $payment = FeePayment::where('user_id', $this->student->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame($this->tuitionPrimary->id, (int) $payment->fee_category_id);
        $this->assertSame($this->admin->id, (int) $payment->recorded_by);
    }

    public function test_payments_index_shows_recorder_name_and_disambiguated_tuition(): void
    {
        FeePayment::create([
            'school_id' => $this->admin->school_id,
            'fee_category_id' => $this->tuitionNursery->id,
            'user_id' => $this->student->id,
            'amount' => 5000,
            'paid_on' => now()->toDateString(),
            'payment_method' => 'cash',
            'recorded_by' => $this->admin->id,
            'status' => 'paid',
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.fee-payments'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Fee Admin', $html);
        $this->assertStringContainsString('Tuition', $html);
        $this->assertStringContainsString('nursery', $html);
        $this->assertStringNotContainsString('recordedBy', $html);
    }
}
