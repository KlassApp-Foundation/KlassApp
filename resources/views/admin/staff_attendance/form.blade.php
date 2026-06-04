<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-2xl font-semibold mb-4">Staff Attendance Entry</h2>

        <form action="{{ url('/admin/attendance/staff/add') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid gap-6 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" class="tw-form-control w-full" />
                    @error('date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Session</label>
                    <select name="session" class="tw-form-control w-full">
                        <option value="forenoon" {{ old('session') === 'forenoon' ? 'selected' : '' }}>Forenoon</option>
                        <option value="afternoon" {{ old('session') === 'afternoon' ? 'selected' : '' }}>Afternoon</option>
                    </select>
                    @error('session')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Staff Member</label>
                    <select name="user_id" class="tw-form-control w-full">
                        <option value="">Select staff</option>
                        @foreach($stafflist as $staff)
                            <option value="{{ $staff->id }}" {{ old('user_id') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->userprofile->firstname ?? '' }} {{ $staff->userprofile->lastname ?? '' }}
                                @if(!empty($staff->name)) - {{ $staff->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Attendance Status</label>
                    <div class="flex gap-4 mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="status" value="1" {{ old('status', '1') === '1' ? 'checked' : '' }} class="form-radio" />
                            <span class="ml-2">Present</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="status" value="0" {{ old('status') === '0' ? 'checked' : '' }} class="form-radio" />
                            <span class="ml-2">Absent</span>
                        </label>
                    </div>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Absence Reason</label>
                    <select name="reason_id" class="tw-form-control w-full">
                        <option value="">Select reason</option>
                        @foreach($absentReasonlist as $reason)
                            <option value="{{ $reason->id }}" {{ old('reason_id') == $reason->id ? 'selected' : '' }}>
                                {{ $reason->title ?? $reason->name ?? 'Unknown' }}
                            </option>
                        @endforeach
                    </select>
                    @error('reason_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Remarks</label>
                    <input type="text" name="remarks" value="{{ old('remarks') }}" class="tw-form-control w-full" placeholder="Optional remarks" />
                    @error('remarks')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ url('/admin/teachers') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary blue-bg text-white px-6 py-2 rounded">Save Attendance</button>
            </div>
        </form>
    </div>
</div>
