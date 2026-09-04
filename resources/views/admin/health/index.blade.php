{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.admin.layout')

@section('content')
<div class="dashboard-shell">
    <div class="dashboard-heading">
        <div>
            <h1 class="dashboard-section-title">Health Records</h1>
            <p class="dashboard-subtitle">{{ $student->displayName ?: $student->name }} &mdash; medical profile, immunizations, and incidents.</p>
        </div>
        <a href="{{ url('/admin/students/' . $student->id) }}" class="text-sm text-blue-600 hover:underline">&larr; Back to Student</a>
    </div>

    @include('partials.message')

    {{-- Health Profile --}}
    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800">Health Profile</h2>
            <button type="button" onclick="document.getElementById('profile-form').classList.toggle('hidden')"
                    class="text-xs font-medium text-blue-600 hover:text-blue-500">
                @if($profile) Edit @else Add @endif
            </button>
        </div>

        @if($profile)
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-400 text-xs uppercase tracking-wider">Blood Type</span>
                    <p class="text-gray-700 font-medium mt-1">{{ $profile->blood_type ?: '—' }}</p>
                </div>
                <div>
                    <span class="text-gray-400 text-xs uppercase tracking-wider">Emergency Contact</span>
                    <p class="text-gray-700 font-medium mt-1">{{ $profile->emergency_contact_name ?: '—' }}</p>
                    @if($profile->emergency_contact_phone)
                        <p class="text-gray-500 text-xs">{{ $profile->emergency_contact_phone }}</p>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <span class="text-gray-400 text-xs uppercase tracking-wider">Allergies</span>
                    <p class="text-gray-700 mt-1">{{ $profile->allergies ?: 'None recorded' }}</p>
                </div>
                <div class="md:col-span-2">
                    <span class="text-gray-400 text-xs uppercase tracking-wider">Chronic Conditions</span>
                    <p class="text-gray-700 mt-1">{{ $profile->chronic_conditions ?: 'None recorded' }}</p>
                </div>
                <div class="md:col-span-2">
                    <span class="text-gray-400 text-xs uppercase tracking-wider">Medical Notes</span>
                    <p class="text-gray-700 mt-1">{{ $profile->medical_notes ?: '—' }}</p>
                </div>
            </div>
        @else
            <div class="p-5 text-center text-gray-400 text-sm">
                No health profile recorded yet.
            </div>
        @endif

        {{-- Profile form --}}
        <form method="POST" action="{{ url('/admin/student/health/profile/' . $student->id) }}" id="profile-form"
              class="hidden border-t border-gray-100 p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Blood Type</label>
                    <select name="blood_type" class="text-sm border rounded px-3 py-2 w-full">
                        <option value="">Select...</option>
                        <option value="A+" @if($profile?->blood_type === 'A+') selected @endif>A+</option>
                        <option value="A-" @if($profile?->blood_type === 'A-') selected @endif>A-</option>
                        <option value="B+" @if($profile?->blood_type === 'B+') selected @endif>B+</option>
                        <option value="B-" @if($profile?->blood_type === 'B-') selected @endif>B-</option>
                        <option value="AB+" @if($profile?->blood_type === 'AB+') selected @endif>AB+</option>
                        <option value="AB-" @if($profile?->blood_type === 'AB-') selected @endif>AB-</option>
                        <option value="O+" @if($profile?->blood_type === 'O+') selected @endif>O+</option>
                        <option value="O-" @if($profile?->blood_type === 'O-') selected @endif>O-</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="{{ $profile->emergency_contact_name ?? '' }}"
                           class="text-sm border rounded px-3 py-2 w-full" placeholder="Full name">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" value="{{ $profile->emergency_contact_phone ?? '' }}"
                           class="text-sm border rounded px-3 py-2 w-full" placeholder="+256...">
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Allergies</label>
                <textarea name="allergies" rows="2" class="text-sm border rounded px-3 py-2 w-full" placeholder="List any known allergies">{{ $profile->allergies ?? '' }}</textarea>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Chronic Conditions</label>
                <textarea name="chronic_conditions" rows="2" class="text-sm border rounded px-3 py-2 w-full" placeholder="List any chronic conditions">{{ $profile->chronic_conditions ?? '' }}</textarea>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Medical Notes</label>
                <textarea name="medical_notes" rows="2" class="text-sm border rounded px-3 py-2 w-full" placeholder="Additional notes">{{ $profile->medical_notes ?? '' }}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-2 rounded">Save Profile</button>
                <button type="button" onclick="document.getElementById('profile-form').classList.add('hidden')"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Immunizations --}}
    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800">Immunizations</h2>
            <button type="button" onclick="document.getElementById('immunization-form').classList.toggle('hidden')"
                    class="text-xs font-medium text-blue-600 hover:text-blue-500">+ Add</button>
        </div>

        @if($immunizations->count() === 0)
            <div class="p-5 text-center text-gray-400 text-sm">No immunizations recorded.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-5 py-3 font-semibold">Vaccine</th>
                            <th class="px-5 py-3 font-semibold">Date Administered</th>
                            <th class="px-5 py-3 font-semibold">Administered By</th>
                            <th class="px-5 py-3 font-semibold">Next Due</th>
                            <th class="px-5 py-3 font-semibold">Notes</th>
                            <th class="px-5 py-3 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($immunizations as $imm)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 text-gray-700 font-medium">{{ $imm->vaccine_name }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $imm->administered_date?->format('d M Y') ?: '—' }}</td>
                                <td class="px-5 py-4 text-gray-500">{{ $imm->administered_by ?: '—' }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ $imm->next_due_date?->format('d M Y') ?: '—' }}</td>
                                <td class="px-5 py-4 text-gray-500 max-w-xs truncate">{{ $imm->notes ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <form method="POST" action="{{ url('/admin/student/health/immunizations/' . $imm->id) }}"
                                          onsubmit="return confirm('Delete this immunization?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:text-red-400 text-xs">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Add immunization form --}}
        <form method="POST" action="{{ url('/admin/student/health/immunizations/' . $student->id) }}" id="immunization-form"
              class="hidden border-t border-gray-100 p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Vaccine Name</label>
                    <input type="text" name="vaccine_name" required class="text-sm border rounded px-3 py-2 w-full" placeholder="e.g. BCG, Polio">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Date Administered</label>
                    <input type="date" name="administered_date" required class="text-sm border rounded px-3 py-2 w-full">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Administered By</label>
                    <input type="text" name="administered_by" class="text-sm border rounded px-3 py-2 w-full" placeholder="Nurse / Doctor name">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Next Due Date</label>
                    <input type="date" name="next_due_date" class="text-sm border rounded px-3 py-2 w-full">
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="text-sm border rounded px-3 py-2 w-full" placeholder="Any additional information"></textarea>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-2 rounded">Add Immunization</button>
                <button type="button" onclick="document.getElementById('immunization-form').classList.add('hidden')"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Incidents --}}
    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800">Health Incidents</h2>
            <button type="button" onclick="document.getElementById('incident-form').classList.toggle('hidden')"
                    class="text-xs font-medium text-blue-600 hover:text-blue-500">+ Record Incident</button>
        </div>

        @if($incidents->count() === 0)
            <div class="p-5 text-center text-gray-400 text-sm">No incidents recorded.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-5 py-3 font-semibold">Date</th>
                            <th class="px-5 py-3 font-semibold">Severity</th>
                            <th class="px-5 py-3 font-semibold">Description</th>
                            <th class="px-5 py-3 font-semibold">Action Taken</th>
                            <th class="px-5 py-3 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($incidents as $inc)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-4 text-gray-600 whitespace-nowrap">{{ $inc->incident_date?->format('d M Y') ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($inc->severity === 'critical') bg-red-100 text-red-700
                                        @elseif($inc->severity === 'moderate') bg-amber-100 text-amber-700
                                        @else bg-gray-100 text-gray-700 @endif">
                                        {{ ucfirst($inc->severity) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 max-w-xs truncate">{{ $inc->description }}</td>
                                <td class="px-5 py-4 text-gray-500 max-w-xs truncate">{{ $inc->action_taken ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <form method="POST" action="{{ url('/admin/student/health/incidents/' . $inc->id) }}"
                                          onsubmit="return confirm('Delete this incident?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:text-red-400 text-xs">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Add incident form --}}
        <form method="POST" action="{{ url('/admin/student/health/incidents/' . $student->id) }}" id="incident-form"
              class="hidden border-t border-gray-100 p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Incident Date</label>
                    <input type="date" name="incident_date" required class="text-sm border rounded px-3 py-2 w-full">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Severity</label>
                    <select name="severity" required class="text-sm border rounded px-3 py-2 w-full">
                        <option value="minor">Minor</option>
                        <option value="moderate">Moderate</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Description</label>
                    <textarea name="description" rows="2" required class="text-sm border rounded px-3 py-2 w-full" placeholder="What happened?"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700 block mb-1">Action Taken</label>
                    <textarea name="action_taken" rows="2" class="text-sm border rounded px-3 py-2 w-full" placeholder="What was done?"></textarea>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-2 rounded">Record Incident</button>
                <button type="button" onclick="document.getElementById('incident-form').classList.add('hidden')"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
