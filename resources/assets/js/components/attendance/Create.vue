<template>
    <div class="bg-white shadow px-4 py-3">
        <div>
            <!-- Success Message -->
            <div v-if="success" class="alert alert-success" id="success-alert">
                {{ success }}
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="block">
                    <span
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400"
                        >Class</span
                    >
                    <select
                        class="tw-form-control w-full"
                        v-model="standardLink_id"
                        @change="onClassChange"
                    >
                        <option value="" disabled>Select class</option>
                        <option
                            v-for="std in standardlist"
                            :key="std.id"
                            :value="std.id"
                        >
                            {{ std.standard_name }} - {{ std.section_name
                            }}<template v-if="std.stream_name">
                                / {{ std.stream_name }}</template
                            >
                        </option>
                    </select>
                    <span
                        v-if="errors?.standardLink_id"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.standardLink_id[0] }}</span
                    >
                </label>
                <label class="block">
                    <span
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400"
                        >Date</span
                    >
                    <input
                        type="date"
                        v-model="localDate"
                        class="tw-form-control w-full"
                    />
                    <span
                        v-if="errors.date"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.date[0] }}</span
                    >
                </label>
                <div class="block">
                    <span
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-400"
                        >Session</span
                    >
                    <div
                        class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                    >
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700"
                        >
                            <input
                                type="radio"
                                v-model="session"
                                value="forenoon"
                                class="mr-1"
                            />
                            Forenoon
                        </label>
                        <label
                            class="flex items-center gap-2 text-sm text-slate-700"
                        >
                            <input
                                type="radio"
                                v-model="session"
                                value="afternoon"
                                class="mr-1"
                            />
                            Afternoon
                        </label>
                    </div>
                    <span
                        v-if="errors.session"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.session[0] }}</span
                    >
                </div>
            </div>

            <!-- Select Students Button -->
            <div class="my-6" v-if="!studentsLoaded">
                <button
                    @click="selectStudents"
                    class="ds-btn ds-btn-primary text-sm"
                >
                    Select Students
                </button>
            </div>

            <!-- Students Section -->
            <div
                v-if="studentsLoaded"
                class="w-full flex flex-col lg:flex-row gap-4"
            >
                <!-- Present Students -->
                <div class="w-full lg:w-1/2 bg-white shadow border px-4 py-4">
                    <h2 class="font-semibold text-base text-gray-700 mb-4">
                        Present Students
                        <span class="text-xs text-gray-500"
                            >({{ presentStudents.length }})</span
                        >
                    </h2>
                    <div
                        v-for="student in presentStudents"
                        :key="student.user_id"
                        class="flex items-center py-2 border-b last:border-0"
                    >
                        <input
                            type="checkbox"
                            v-model="student.isPresent"
                            class="w-5 h-5 mr-3 accent-green-600"
                        />
                        <span class="tw-form-label">{{
                            student.user_name
                        }}</span>
                    </div>
                </div>

                <!-- Absent Students -->
                <div class="w-full lg:w-1/2 bg-white shadow border px-4 py-4">
                    <h2 class="font-semibold text-base text-gray-700 mb-4">
                        Absent Students
                        <span class="text-xs text-gray-500"
                            >({{ absentStudents.length }})</span
                        >
                    </h2>
                    <div
                        v-for="student in absentStudents"
                        :key="student.user_id"
                        class="flex items-center justify-between py-3 border-b last:border-0"
                    >
                        <div class="flex items-center flex-1">
                            <!-- Fixed: Checkbox appears CHECKED when student is Absent -->
                            <input
                                type="checkbox"
                                :checked="!student.isPresent"
                                @change="toggleAttendance(student)"
                                class="w-5 h-5 mr-3 accent-red-600"
                            />
                            <span class="tw-form-label">{{
                                student.user_name
                            }}</span>
                        </div>
                        <div class="flex flex-col md:flex-row gap-1 md:gap-3">
                            <select
                                v-model="student.reason_id"
                                class="tw-form-control"
                            >
                                <option value="" disabled>Select Reason</option>
                                <option
                                    v-for="reason in absentReasonlist"
                                    :key="reason.id"
                                    :value="reason.id"
                                >
                                    {{ reason.title }}
                                </option>
                            </select>
                            <input
                                type="text"
                                v-model="student.remarks"
                                class="tw-form-control"
                                placeholder="Remarks"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit / Reset -->
            <div v-if="showSubmit" class="my-6 flex gap-3">
                <button
                    @click="submitForm"
                    class="ds-btn ds-btn-primary text-sm"
                >
                    Submit Attendance
                </button>
                <button
                    @click="resetForm"
                    class="ds-btn ds-btn-secondary text-sm"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ["url", "standard", "mode", "date"],

    data() {
        return {
            standardlist: [],
            studentlist: [],
            absentReasonlist: [],
            standardLink_id: "",
            localDate: this.date || "",
            session: "",
            allStudents: [],
            studentsLoaded: false,
            showSubmit: false,
            errors: {},
            success: null,
        };
    },

    computed: {
        selectedStream() {
            return (
                this.standardlist.find(
                    (item) => String(item.id) === String(this.standardLink_id)
                )?.stream_name || ""
            );
        },
        presentStudents() {
            return this.allStudents.filter((s) => s.isPresent);
        },
        absentStudents() {
            return this.allStudents.filter((s) => !s.isPresent);
        },
    },

    methods: {
        async getData() {
            try {
                const { data } = await axios.get(
                    `/${this.mode}/attendance/list`
                );
                this.studentlist = data.studentlist || [];
                this.absentReasonlist = data.absentReasonlist || [];
                this.standardlist = data.standardlist || [];
                console.log("student data=>", data);
                console.log("student list==>", data?.studentlist);
                console.log("standardlist ==>", data?.standardlist);
            } catch (error) {
                console.error("Failed to load data", error);
            }
        },

        selectStudents() {
            if (!this.standardLink_id) {
                alert("Please select a class first");
                return;
            }

            const studentsForClass =
                this.studentlist[this.standardLink_id] || [];

            this.allStudents = studentsForClass.map((s) => ({
                user_id: s.user_id,
                user_name: s.name,
                isPresent: true, // Default to Present
                reason_id: "",
                remarks: "",
            }));

            this.studentsLoaded = true;
            this.showSubmit = true; // Show submit button after loading
        },

        // New method for Absent list toggle
        toggleAttendance(student) {
            student.isPresent = !student.isPresent;
        },

        async submitForm() {
            this.errors = {};
            this.success = null;

            const formData = new FormData();
            formData.append("standardLink_id", this.standardLink_id);
            formData.append("date", this.localDate);
            formData.append("session", this.session);

            const absentList = this.absentStudents;
            formData.append("absentCount", absentList.length);

            absentList.forEach((student, i) => {
                formData.append(`user_id${i}`, student.user_id);
                formData.append(`reason_id${i}`, student.reason_id || "");
                formData.append(`remarks${i}`, student.remarks || "");
            });

            const presentList = this.presentStudents;
            formData.append("presentCount", presentList.length);

            presentList.forEach((student, i) => {
                formData.append(`present_id${i}`, student.user_id);
            });

            try {
                const res = await axios.post(
                    `/${this.mode}/attendance/add`,
                    formData,
                    { headers: { "Content-Type": "multipart/form-data" } }
                );
                this.success = res.data.success;
            } catch (error) {
                const data = error.response?.data;
                if (data?.errors) {
                    this.errors = data.errors;
                } else if (data?.error) {
                    alert(data.error);
                } else if (data?.message) {
                    alert(data.message);
                } else {
                    console.error(error);
                }
            }
        },

        resetForm() {
            window.location.reload();
        },

        onClassChange() {
            this.studentsLoaded = false;
            this.allStudents = [];
            this.showSubmit = false;
        },
    },

    created() {
        this.getData();
    },
};
</script>
