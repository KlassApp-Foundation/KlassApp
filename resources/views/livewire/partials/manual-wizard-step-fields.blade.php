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
    <p class="text-sm text-gray-600" style="color:#64748B;">
        When your content setup is complete, KlassApp assigns a free-tier plan automatically if one fits.
        Click <strong>Finish</strong> to apply that milestone.
    </p>
@endif
