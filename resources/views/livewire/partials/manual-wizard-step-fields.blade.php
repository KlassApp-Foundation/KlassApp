{{-- SPDX-License-Identifier: MIT --}}
@php $stepKey = $stepKey ?? ''; @endphp

@if($stepKey === 'school_name')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-school-name">School name<span class="text-red-500">*</span></label>
        <input id="wizard-school-name" type="text" class="ds-form-input w-full" wire:model="schoolName" placeholder="e.g. Sunrise Academy" />
    </div>

@elseif($stepKey === 'curriculum')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-curriculum">Board / Curriculum<span class="text-red-500">*</span></label>
        <select id="wizard-curriculum" class="ds-form-input ds-form-select w-full" wire:model="curriculum">
            <option value="uneb">UNEB (Uganda National Examinations Board)</option>
            <option value="cambridge">Cambridge</option>
            <option value="montessori">Montessori</option>
            <option value="other">Other</option>
        </select>
    </div>

@elseif($stepKey === 'country')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-country">Country<span class="text-red-500">*</span></label>
        <select id="wizard-country" class="ds-form-input ds-form-select w-full" wire:model="countryName">
            @foreach($countries as $country)
                <option value="{{ $country->name }}">{{ $country->name }}</option>
            @endforeach
            @if($countries->isEmpty())
                <option value="Uganda">Uganda</option>
                <option value="Kenya">Kenya</option>
                <option value="Tanzania">Tanzania</option>
            @endif
        </select>
        <p class="text-xs text-gray-500 mt-1">Saves both country and Toshi registration country.</p>
    </div>

@elseif($stepKey === 'emis')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-emis">EMIS / Ministry code<span class="text-red-500">*</span></label>
        <input id="wizard-emis" type="text" class="ds-form-input w-full" wire:model="ministryCode" placeholder="e.g. EMIS-1001" />
    </div>

@elseif($stepKey === 'uneb_center')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-uneb">UNEB centre number</label>
        <input id="wizard-uneb" type="text" class="ds-form-input w-full" wire:model="unebCenterNumber" placeholder="Optional — leave blank to skip" />
        <p class="text-xs text-gray-500 mt-1">Optional for UNEB schools. Leave blank if you do not have one yet.</p>
    </div>

@elseif($stepKey === 'academic_year')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-ay-desc">Description</label>
        <input id="wizard-ay-desc" type="text" class="ds-form-input w-full" wire:model="academicYearDescription" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-ay-start">Starts on<span class="text-red-500">*</span></label>
            <input id="wizard-ay-start" type="date" class="ds-form-input w-full" wire:model="academicYearStart" />
        </div>
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-ay-end">Ends on<span class="text-red-500">*</span></label>
            <input id="wizard-ay-end" type="date" class="ds-form-input w-full" wire:model="academicYearEnd" />
        </div>
    </div>

@elseif($stepKey === 'standards')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-class">First class / stream<span class="text-red-500">*</span></label>
        <input id="wizard-class" type="text" class="ds-form-input w-full" wire:model="className" placeholder="e.g. P1" />
    </div>

@elseif($stepKey === 'subjects')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-subject">First subject<span class="text-red-500">*</span></label>
        <input id="wizard-subject" type="text" class="ds-form-input w-full" wire:model="subjectName" placeholder="e.g. Mathematics" />
    </div>

@elseif($stepKey === 'teachers')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-teacher-name">Teacher name<span class="text-red-500">*</span></label>
        <input id="wizard-teacher-name" type="text" class="ds-form-input w-full" wire:model="teacherName" />
    </div>
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-teacher-email">Email<span class="text-red-500">*</span></label>
        <input id="wizard-teacher-email" type="email" class="ds-form-input w-full" wire:model="teacherEmail" />
    </div>
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-teacher-phone">Phone</label>
        <input id="wizard-teacher-phone" type="text" class="ds-form-input w-full" wire:model="teacherPhone" placeholder="+2567…" />
    </div>

@elseif($stepKey === 'terms')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-term-name">Term name<span class="text-red-500">*</span></label>
        <input id="wizard-term-name" type="text" class="ds-form-input w-full" wire:model="termName" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-term-start">Starts on<span class="text-red-500">*</span></label>
            <input id="wizard-term-start" type="date" class="ds-form-input w-full" wire:model="termStartsOn" />
        </div>
        <div class="ds-form-group">
            <label class="ds-form-label" for="wizard-term-end">Ends on<span class="text-red-500">*</span></label>
            <input id="wizard-term-end" type="date" class="ds-form-input w-full" wire:model="termEndsOn" />
        </div>
    </div>

@elseif($stepKey === 'fees')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-fee-name">Fee name<span class="text-red-500">*</span></label>
        <input id="wizard-fee-name" type="text" class="ds-form-input w-full" wire:model="feeName" />
    </div>
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-fee-amount">Amount (UGX)<span class="text-red-500">*</span></label>
        <input id="wizard-fee-amount" type="number" min="1" class="ds-form-input w-full" wire:model="feeAmount" />
    </div>

@elseif($stepKey === 'whatsapp_verify')
    <div class="ds-form-group">
        <label class="ds-form-label" for="wizard-wa">Your WhatsApp number<span class="text-red-500">*</span></label>
        <input id="wizard-wa" type="text" class="ds-form-input w-full" wire:model="whatsappPhone" placeholder="+2567…" />
        <p class="text-xs text-gray-500 mt-1">Links your admin account the same way the WhatsApp phone page does.</p>
    </div>

@elseif($stepKey === 'plan_selection')
    <p class="text-sm text-gray-600 mb-3" style="color:#64748B;">
        Schools are free to start — pick a plan so capacity limits are clear. No payment is required now.
    </p>
    <div class="manual-wizard-plan-grid" data-testid="wizard-plan-cards" role="radiogroup" aria-label="Plan selection">
        @forelse(($plans ?? []) as $plan)
            @php
                $isSelected = (int) ($selectedPlanId ?? 0) === (int) $plan->id;
                $isFreemium = strcasecmp((string) $plan->name, 'Freemium') === 0
                    || strcasecmp((string) ($plan->display_name ?? ''), 'Freemium') === 0
                    || (int) $plan->amount === 0;
            @endphp
            <button type="button"
                    class="manual-wizard-plan-card {{ $isSelected ? 'is-selected' : '' }}"
                    wire:click="selectPlan({{ $plan->id }})"
                    role="radio"
                    aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                    data-testid="wizard-plan-{{ $plan->id }}"
                    data-plan-name="{{ $plan->name }}">
                <span class="manual-wizard-plan-name">{{ $plan->display_name ?: ucfirst($plan->name) }}</span>
                <span class="manual-wizard-plan-price">
                    @if(!empty($plan->is_custom_pricing))
                        Contact us
                    @elseif((int) $plan->amount > 0)
                        ${{ number_format((float) $plan->amount) }} / {{ $plan->cycle }} days
                    @else
                        Free
                    @endif
                </span>
                @if($isFreemium)
                    <span class="manual-wizard-plan-hint">Recommended to start</span>
                @endif
            </button>
        @empty
            <p class="text-sm text-red-600" role="alert">No plans are available yet. Contact support.</p>
        @endforelse
    </div>

@elseif($stepKey === 'review')
    <div class="manual-wizard-review" data-testid="wizard-review">
        <p class="manual-wizard-review-intro">
            Confirm everything looks right. Use Edit to jump back to a step — your later answers stay saved.
        </p>
        <div class="manual-wizard-review-card" data-testid="wizard-review-card">
            <div class="manual-wizard-review-header">Review &amp; confirm</div>
            <div class="manual-wizard-review-body">
                @forelse(($reviewSummary ?? []) as $row)
                    <div class="manual-wizard-review-row" data-testid="wizard-review-{{ $row['key'] }}">
                        <div class="manual-wizard-review-row-main">
                            <span class="manual-wizard-review-icon" aria-hidden="true">{{ $row['icon'] }}</span>
                            <div>
                                <div class="manual-wizard-review-label">{{ $row['label'] }}</div>
                                <div class="manual-wizard-review-value">{{ $row['value'] }}</div>
                            </div>
                        </div>
                        <button type="button"
                                class="manual-wizard-review-edit"
                                wire:click="editSection('{{ $row['key'] }}')"
                                data-testid="wizard-edit-{{ $row['key'] }}">
                            Edit
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Nothing to review yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif
