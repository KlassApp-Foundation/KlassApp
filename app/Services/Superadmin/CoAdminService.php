<?php

namespace App\Services\Superadmin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Shared co-admin (usergroup_id=2) mutators for Livewire CoAdmins and Toshi tools.
 * Mirrors Batch A CoAdmins.save / delete behaviour.
 */
class CoAdminService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): User
    {
        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ])->validate();

        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'usergroup_id' => 2,
            'status' => 'active',
        ]);
    }

    /**
     * Soft-delete a co-admin. Refuses self-delete and superadmin (ug1) targets.
     *
     * @throws ValidationException
     */
    public function delete(int $id, User $actor): User
    {
        $target = User::find($id);

        if (! $target) {
            throw ValidationException::withMessages([
                'id' => 'User not found.',
            ]);
        }

        if ($target->id === $actor->id) {
            throw ValidationException::withMessages([
                'id' => 'You cannot remove yourself.',
            ]);
        }

        if ((int) $target->usergroup_id === 1) {
            throw ValidationException::withMessages([
                'id' => 'Cannot remove a super admin through this panel.',
            ]);
        }

        if ((int) $target->usergroup_id !== 2) {
            throw ValidationException::withMessages([
                'id' => 'Target is not a co-admin (usergroup_id=2).',
            ]);
        }

        $target->delete();

        return $target;
    }

    /**
     * Reset a co-admin password (Batch A CoAdmins.save edit-with-password path).
     *
     * @throws ValidationException
     */
    public function resetPassword(int $id, string $password): User
    {
        $validated = Validator::make(
            ['id' => $id, 'password' => $password],
            [
                'id' => 'required|integer|exists:users,id',
                'password' => 'required|string|min:6',
            ]
        )->validate();

        $target = User::findOrFail($validated['id']);

        if ((int) $target->usergroup_id !== 2) {
            throw ValidationException::withMessages([
                'id' => 'Target is not a co-admin (usergroup_id=2).',
            ]);
        }

        $target->update([
            'password' => Hash::make($validated['password']),
        ]);

        return $target->fresh();
    }

    /**
     * Update co-admin name/email and optionally password (Livewire edit path).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): User
    {
        $target = User::findOrFail($id);

        if ((int) $target->usergroup_id !== 2) {
            throw ValidationException::withMessages([
                'id' => 'Target is not a co-admin (usergroup_id=2).',
            ]);
        }

        $validated = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'password' => 'nullable|string|min:6',
        ])->validate();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $target->update($payload);

        return $target->fresh();
    }
}
