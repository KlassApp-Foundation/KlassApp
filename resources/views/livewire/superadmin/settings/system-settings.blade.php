{{-- SPDX-License-Identifier: MIT --}}
<div>
    @if (session()->has('message'))
        <div class="ds-badge ds-badge-success" style="display:block;padding:8px 12px;border-radius:8px;margin-bottom:12px;">{{ session('message') }}</div>
    @endif

    <div class="ds-page-head">
        <h1 class="ds-page-head-title">System Settings</h1>
    </div>

    <form wire:submit="save">
        <div class="ds-card">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="ds-form-label">Site Title</label>
                    <input type="text" wire:model="sitetitle" class="ds-form-input">
                </div>
                <div>
                    <label class="ds-form-label">Site Name</label>
                    <input type="text" wire:model="sitename" class="ds-form-input">
                </div>
                <div>
                    <label class="ds-form-label">Logo Path</label>
                    <input type="text" wire:model="sitelogo" class="ds-form-input">
                </div>
                <div>
                    <label class="ds-form-label">Favicon Path</label>
                    <input type="text" wire:model="favicon" class="ds-form-input">
                </div>
                <div>
                    <label class="ds-form-label">Maintenance Mode</label>
                    <select wire:model="maintenance" class="ds-form-input ds-form-select">
                        <option value="0">Off</option>
                        <option value="1">On</option>
                    </select>
                </div>
                <div>
                    <label class="ds-form-label">Login Status</label>
                    <select wire:model="login_status" class="ds-form-input ds-form-select">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
                <div>
                    <label class="ds-form-label">Registration Status</label>
                    <select wire:model="register_status" class="ds-form-input ds-form-select">
                        <option value="1">Open</option>
                        <option value="0">Closed</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="ds-btn ds-btn-primary">Save Settings</button>
            </div>
        </div>
    </form>
</div>
