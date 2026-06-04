<div class="bg-white shadow rounded-lg p-5 flex items-center justify-between">
    <form method="GET" action="{{ url('/admin/attendance/staff/list') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700">Filter by date</label>
            <input type="date" name="date" value="{{ old('date', request('date', $date ?? date('Y-m-d'))) }}" class="tw-form-control w-full" />
        </div>

        <div class="md:col-span-1 flex items-center">
            <button type="submit" class="btn btn-primary blue-bg text-white px-6 py-2 rounded">Apply filter</button>
        </div>
    </form>
    <div class="">
        <a href="{{route("admin.attendance.staff.add")}}" class='bg-green-600 p-1 rounded text-white'>Record Attendance</a>
    </div>
</div>
