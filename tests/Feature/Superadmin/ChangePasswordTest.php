<?php

namespace Tests\Feature\Superadmin;

use App\Livewire\Superadmin\ChangePassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('usergroups')->upsert([
            ['id' => 1, 'name' => 'siteadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'coadmin', 'created_at' => now(), 'updated_at' => now()],
        ], 'id');
    }

    public function test_confirm_password_must_match_new_password_not_password(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 2,
            'password' => Hash::make('password'),
        ]);

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->set('old_password', 'password')
            ->set('new_password', 'newpass99')
            ->set('confirm_password', 'newpass99')
            ->call('submitPassword')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('newpass99', $user->password));
    }

    public function test_mismatched_confirm_password_fails_validation(): void
    {
        $user = User::factory()->create([
            'usergroup_id' => 2,
            'password' => Hash::make('password'),
        ]);

        Livewire::actingAs($user)
            ->test(ChangePassword::class)
            ->set('old_password', 'password')
            ->set('new_password', 'newpass99')
            ->set('confirm_password', 'different')
            ->call('submitPassword')
            ->assertHasErrors(['confirm_password']);

        $user->refresh();
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
