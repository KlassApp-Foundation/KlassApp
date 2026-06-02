<template>
    <div class="bg-white shadow px-4 py-3">
        <div>
            <!-- Success Message -->
            <div v-if="success" class="alert alert-success" id="success-alert">
                {{ success }}
            </div>

            <!-- Date -->
            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div
                        class="flex flex-col lg:flex-row lg:items-center w-full"
                    >
                        <div class="mb-2 w-full lg:w-1/4">
                            <label for="date" class="tw-form-label"
                                >Date <span class="text-red-500">*</span></label
                            >
                        </div>
                        <div class="mb-2 w-full lg:w-3/4">
                            <input
                                type="date"
                                v-model="date"
                                class="tw-form-control w-full"
                            />
                            <span
                                v-if="errors.date"
                                class="text-red-500 text-xs font-semibold"
                            >
                                {{ errors.date[0] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Session -->
            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div
                        class="flex flex-col lg:flex-row lg:items-center w-full"
                    >
                        <div class="mb-2 w-full lg:w-1/4">
                            <label class="tw-form-label"
                                >Session
                                <span class="text-red-500">*</span></label
                            >
                        </div>
                        <div class="mb-2 w-full lg:w-3/4">
                            <div class="flex gap-6">
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="session"
                                        value="forenoon"
                                        class="mr-2"
                                    />
                                    <span>Forenoon</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        type="radio"
                                        v-model="session"
                                        value="afternoon"
                                        class="mr-2"
                                    />
                                    <span>Afternoon</span>
                                </label>
                            </div>
                            <span
                                v-if="errors.session"
                                class="text-red-500 text-xs font-semibold"
                            >
                                {{ errors.session[0] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Select Staff Button -->
            <div class="my-6" v-if="!staffsLoaded">
                <button
                    @click="selectStaffs"
                    class="btn btn-submit blue-bg text-white rounded px-3 py-1 text-sm font-medium"
                >
                    Select Staffs
                </button>
            </div>

            <!-- Staff Lists -->
            <div v-if="staffsLoaded" class="flex flex-col lg:flex-row gap-4">
                <!-- Present Staff -->
                <div class="w-full lg:w-1/2 bg-white shadow border px-4 py-4">
                    <h2 class="font-semibold text-base text-gray-700 mb-4">
                        Present Staffs
                        <span class="text-xs text-gray-500"
                            >({{ presents.length }})</span
                        >
                    </h2>
                    <div
                        v-for="staff in presents"
                        :key="staff.user_id"
                        class="flex items-center py-2 border-b"
                    >
                        <input
                            type="checkbox"
                            :checked="true"
                            @change="markAbsent(staff)"
                            class="w-5 h-5 mr-3 accent-green-600"
                        />
                        <span class="tw-form-label">{{ staff.user_name }}</span>
                    </div>
                </div>

                <!-- Absent Staff -->
                <div class="w-full lg:w-1/2 bg-white shadow border px-4 py-4">
                    <h2 class="font-semibold text-base text-gray-700 mb-4">
                        Absent Staffs
                        <span class="text-xs text-gray-500"
                            >({{ absents.length }})</span
                        >
                    </h2>
                    <div
                        v-for="(staff, index) in absents"
                        :key="staff.user_id"
                        class="flex flex-col lg:flex-row gap-3 py-3 border-b"
                    >
                        <div class="flex items-center flex-1">
                            <input
                                type="checkbox"
                                :checked="true"
                                @change="markPresent(staff, index)"
                                class="w-5 h-5 mr-3 accent-red-600"
                            />
                            <span class="tw-form-label">{{
                                staff.user_name
                            }}</span>
                        </div>
                        <div class="flex gap-2">
                            <select
                                v-model="staff.reason_id"
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
                                v-model="staff.remarks"
                                class="tw-form-control"
                                placeholder="Remarks"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div v-if="staffsLoaded" class="my-6 flex gap-3">
                <button
                    @click="submitForm"
                    class="btn btn-submit blue-bg text-white rounded px-3 py-1 text-sm font-medium"
                >
                    Submit Attendance
                </button>
                <button
                    @click="resetForm"
                    class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 text-sm font-medium"
                >
                    Reset
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ["url", "mode"],

    data() {
        return {
            stafflist: [],
            absentReasonlist: [],
            presents: [],
            absents: [],
            date: "",
            session: "",
            staffsLoaded: false,
            errors: {},
            success: null,
        };
    },

    methods: {
        async getData() {
            try {
                const { data } = await axios.get(
                    `/${this.mode}/attendance/staff/list`
                );
                this.stafflist = data.stafflist || [];
                this.absentReasonlist = data.absentReasonlist || [];
            } catch (error) {
                console.error(error);
            }
        },

        selectStaffs() {
            if (!this.date || !this.session) {
                alert("Please select Date and Session first!");
                return;
            }

            this.presents = this.stafflist.map((staff) => ({
                user_id: staff.teacher_id,
                user_name: staff.teacher_name,
                reason_id: "",
                remarks: "",
            }));

            this.absents = [];
            this.staffsLoaded = true;
        },

        markAbsent(staff) {
            const index = this.presents.findIndex(
                (s) => s.user_id === staff.user_id
            );
            if (index !== -1) {
                this.presents.splice(index, 1);
                this.absents.push({
                    user_id: staff.user_id,
                    user_name: staff.user_name,
                    reason_id: "",
                    remarks: "",
                });
            }
        },

        markPresent(staff, index) {
            this.absents.splice(index, 1);
            this.presents.push({
                user_id: staff.user_id,
                user_name: staff.user_name,
                reason_id: "",
                remarks: "",
            });
        },

        async submitForm() {
            this.errors = {};
            this.success = null;

            const formData = new FormData();
            formData.append("date", this.date);
            formData.append("session", this.session);

            // Absents
            formData.append("absentCount", this.absents.length);
            this.absents.forEach((staff, i) => {
                formData.append(`user_id${i}`, staff.user_id);
                formData.append(`reason_id${i}`, staff.reason_id || "");
                formData.append(`remarks${i}`, staff.remarks || "");
            });

            // Presents
            formData.append("presentCount", this.presents.length);
            this.presents.forEach((staff, i) => {
                formData.append(`present_id${i}`, staff.user_id);
            });

            try {
                const res = await axios.post(
                    `/${this.mode}/attendance/staff/add`,
                    formData,
                    { headers: { "Content-Type": "multipart/form-data" } }
                );
                this.success =
                    res.data.success || "Attendance submitted successfully!";
            } catch (error) {
                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else {
                    console.error(error);
                }
            }
        },

        resetForm() {
            window.location.reload();
        },
    },

    created() {
        this.getData();
    },
};
</script>
