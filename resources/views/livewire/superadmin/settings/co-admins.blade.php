{{-- SPDX-License-Identifier: MIT --}}
<div>
    @if (session()->has('message'))
        <div class="ds-badge ds-badge-success" style="display:block;padding:8px 12px;border-radius:8px;margin-bottom:12px;">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="ds-badge" style="display:block;padding:8px 12px;border-radius:8px;margin-bottom:12px;background:#FEE2E2;color:#991B1B;">
            {{ session('error') }}
        </div>
    @endif

    <div class="ds-page-head">
        <h1 class="ds-page-head-title">Co-Admins</h1>
        <button wire:click="$set('showForm', true)" class="ds-btn ds-btn-primary ds-btn-sm" {{ $showForm ? 'style=display:none' : '' }}>
            + Add Co-Admin
        </button>
    </div>

    @if($showForm)
        <div class="ds-card mb-4">
            <form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                    <div>
                        <label class="ds-form-label">Name</label>
                        <input type="text" wire:model="name" class="ds-form-input" placeholder="Full name">
                        @error('name') <span class="ds-form-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="ds-form-label">Email</label>
                        <input type="email" wire:model="email" class="ds-form-input" placeholder="email@example.com">
                        @error('email') <span class="ds-form-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="ds-form-label">Password</label>
                        <input type="password" wire:model="password" class="ds-form-input" placeholder="{{ $editingId ? 'Leave blank to keep' : 'Password' }}">
                        @error('password') <span class="ds-form-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="ds-btn ds-btn-primary">{{ $editingId ? 'Update' : 'Create' }}</button>
                    <button type="button" wire:click="$set('showForm', false)" class="ds-btn ds-btn-ghost">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="ds-table-wrap">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coAdmins as $admin)
                    <tr>
                        <td class="font-medium">{{ $admin->name }}</td>
                        <td class="text-sm text-gray-600">{{ $admin->email }}</td>
                        <td class="text-sm text-gray-500">{{ $admin->created_at->format('M d, Y') }}</td>
                        <td class="text-right">
                            <button wire:click="edit({{ $admin->id }})" class="ds-btn ds-btn-ghost ds-btn-sm">Edit</button>
                            <button wire:click="delete({{ $admin->id }})" class="ds-btn ds-btn-ghost ds-btn-sm" onclick="return confirm('Remove this co-admin?')">Remove</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-8 text-gray-400">No co-admins yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $coAdmins->links() }}</div>
</div>
