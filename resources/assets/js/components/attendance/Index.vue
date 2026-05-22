<template>
    <div class="bg-white shadow rounded-lg p-6">
        <h1 class="text-2xl font-semibold mb-6">Student Attendance Records</h1>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div>
                <label class="block text-sm font-medium mb-1"
                    >Select Class</label
                >
                <select
                    v-model="standardLink_id"
                    class="tw-form-control w-full"
                    @change="fetchAttendance"
                >
                    <option value="">All Classes</option>
                    <option
                        v-for="std in standardlist"
                        :key="std.id"
                        :value="std.id"
                    >
                        {{ std.standard_name }} {{ std.section_name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Date</label>
                <input
                    type="date"
                    v-model="filterDate"
                    class="tw-form-control w-full"
                    @change="fetchAttendance"
                />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Session</label>
                <select
                    v-model="filterSession"
                    class="tw-form-control w-full"
                    @change="fetchAttendance"
                >
                    <option value="">All Sessions</option>
                    <option value="forenoon">Forenoon</option>
                    <option value="afternoon">Afternoon</option>
                </select>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-3 text-left">Student Name</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-left">Reason</th>
                        <th class="px-4 py-3 text-left">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="student in filteredStudents"
                        :key="student.user_id"
                        class="border-b hover:bg-gray-50"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ student.name }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                :class="
                                    student.status === 'present'
                                        ? 'text-green-600'
                                        : 'text-red-600'
                                "
                                class="font-semibold"
                            >
                                {{ student.status.toUpperCase() }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ student.reason || "-" }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ student.remarks || "-" }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="filteredStudents.length === 0"
            class="text-center py-10 text-gray-500"
        >
            No attendance records found.
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            standardlist: [],
            studentlist: {},
            attendanceRecords: [], // We'll store fetched attendance here
            standardLink_id: "",
            filterDate: "",
            filterSession: "",
        };
    },

    computed: {
        filteredStudents() {
            // You can enhance this logic later
            return this.attendanceRecords;
        },
    },

    methods: {
        async fetchAttendance() {
            let url = `/${this.mode}/attendance/get-records`; // We'll create this route

            if (this.standardLink_id)
                url += `?standardLink_id=${this.standardLink_id}`;
            if (this.filterDate) url += `&date=${this.filterDate}`;
            if (this.filterSession) url += `&session=${this.filterSession}`;

            try {
                const res = await axios.get(url);
                this.attendanceRecords = res.data;
            } catch (e) {
                console.error(e);
            }
        },

        getData() {
            axios.get(`/${this.mode}/attendance/list`).then((response) => {
                this.standardlist = response.data.standardlist;
                this.studentlist = response.data.studentlist;
            });
        },
    },

    created() {
        this.getData();
        this.filterDate = new Date().toISOString().slice(0, 10); // Today
        this.fetchAttendance();
    },
};
</script>
