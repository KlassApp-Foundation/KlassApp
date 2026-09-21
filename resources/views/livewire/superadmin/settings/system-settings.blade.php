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
                    <label class="ds-form-label">Default maintenance mode</label>
                    <select wire:model="maintenance" class="ds-form-input ds-form-select">
                        <option value="0">Off</option>
                        <option value="1">On</option>
                    </select>
                    <p style="margin-top:6px;font-size:12px;line-height:1.45;color:#6b7280">Used only for schools that have no maintenance setting of their own. A school's own setting always wins — set it per school with <code>php artisan school:access &lt;id&gt; --maintenance=on|off</code>.</p>
                </div>
                <div>
                    <label class="ds-form-label">Default login status</label>
                    <select wire:model="login_status" class="ds-form-input ds-form-select">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                    <p style="margin-top:6px;font-size:12px;line-height:1.45;color:#6b7280">Used only for schools that have no login setting of their own (this includes newly created schools). It does not switch off any school that already has its own setting. Set one school with <code>php artisan school:access &lt;id&gt; --login=on|off</code>.</p>
                </div>
                <div>
                    <label class="ds-form-label">Registration status</label>
                    <select wire:model="register_status" class="ds-form-input ds-form-select">
                        <option value="1">Open</option>
                        <option value="0">Closed</option>
                    </select>
                    <p style="margin-top:6px;font-size:12px;line-height:1.45;color:#6b7280"><strong>Platform-wide</strong> (not per school): controls public self-registration for the whole platform.</p>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="ds-btn ds-btn-primary">Save Settings</button>
            </div>
        </div>
    </form>

    @php
        $onOff = function ($value) {
            return ($value === null || $value === '') ? null : ((int) $value === 1);
        };
    @endphp
    <div class="ds-card" style="margin-top:16px">
        <h2 class="ds-card-title">Per-school access (what each school actually uses)</h2>
        <p style="margin-top:4px;margin-bottom:12px;font-size:13px;line-height:1.5;color:#6b7280">
            Each school's effective switch. <strong>Own setting</strong> overrides the platform defaults above;
            <strong>inherits default</strong> means the school has no setting of its own yet.
        </p>
        <div class="ds-table-wrap">
            <table class="ds-table ds-table-striped">
                <thead>
                    <tr>
                        <th>School</th>
                        <th>Login switch</th>
                        <th>Maintenance mode</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schoolAccess as $row)
                        @php
                            $loginOn = $onOff($row['effective_login']);
                            $maintenanceOn = $onOff($row['effective_maintenance']);
                        @endphp
                        <tr>
                            <td>{{ $row['name'] }} <span style="color:#9ca3af;font-size:12px">#{{ $row['id'] }}</span></td>
                            <td>
                                {{ $loginOn === null ? 'Enabled (no value set)' : ($loginOn ? 'Enabled' : 'Disabled') }}
                                <span style="color:#9ca3af;font-size:12px">· {{ ($row['own_login'] === null || $row['own_login'] === '') ? 'inherits default' : 'own setting' }}</span>
                            </td>
                            <td>
                                {{ $maintenanceOn === null ? 'Off (no value set)' : ($maintenanceOn ? 'On' : 'Off') }}
                                <span style="color:#9ca3af;font-size:12px">· {{ ($row['own_maintenance'] === null || $row['own_maintenance'] === '') ? 'inherits default' : 'own setting' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No schools yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
