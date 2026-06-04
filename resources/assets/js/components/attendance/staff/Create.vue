<template>
    <div class="bg-white shadow px-4 py-3">
        <div>
            <!-- Success Message -->
            <div
                v-if="success != null"
                class="alert alert-success"
                id="success-alert"
            >
                {{ success }}
            </div>

            <!-- Date -->
            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div
                        class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center w-full"
                    >
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/3">
                            <label for="date" class="tw-form-label">
                                Date<span class="text-red-500">*</span>
                            </label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <input
                                type="date"
                                name="date"
                                v-model="date"
                                class="tw-form-control w-full"
                                id="date"
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
                        class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center w-full"
                    >
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/3">
                            <label for="session" class="tw-form-label">
                                Session<span class="text-red-500">*</span>
                            </label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <div class="flex">
                                <div
                                    class="w-1/2 flex items-center tw-form-control mr-1 lg:mr-4 md:mr-4"
                                >
                                    <input
                                        type="radio"
                                        v-model="session"
                                        name="session"
                                        id="forenoon"
                                        value="forenoon"
                                    />
                                    <span class="text-sm mx-1">Forenoon</span>
                                </div>
                                <div
                                    class="w-1/2 flex items-center tw-form-control"
                                >
                                    <input
                                        type="radio"
                                        v-model="session"
                                        name="session"
                                        id="afternoon"
                                        value="afternoon"
                                    />
                                    <span class="text-sm mx-1">Afternoon</span>
                                </div>
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
            <div class="my-6" id="select_student_btn">
                <a
                    href="#"
                    class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium"
                    @click="selectStudent"
                >
                    Select Staffs
                </a>
            </div>

            <!-- Staff Selection Area -->
            <div class="w-full flex flex-col lg:flex-row hidden" id="select">
                <!-- Present Staffs -->
                <div class="w-full lg:w-1/2 bg-white shadow border px-4">
                    <div class="w-full my-4">
                        <div class="flex justify-between items-center my-4">
                            <h2
                                class="font-semibold text-base text-gray-700 capitalize"
                            >
                                Staffs
                                <span class="text-xs"
                                    >(Click on checkbox to mark absent)</span
                                >
                            </h2>
                        </div>
                        <div
                            v-for="(present, index) in presents"
                            :key="index"
                            class=""
                        >
                            <div
                                class="flex items-center py-1"
                                :id="present.present_id"
                            >
                                <div class="w-6">
                                    <input
                                        type="checkbox"
                                        v-model="present.present_id"
                                        class="tw-form-control w-full"
                                        @click="
                                            absentStudent(
                                                $event,
                                                present.user,
                                                index
                                            )
                                        "
                                    />
                                </div>
                                <div class="mx-2">
                                    <p class="tw-form-label">
                                        {{ present.user_name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Absent Staffs -->
                <div
                    class="w-full lg:w-1/2 bg-white shadow border px-4 lg:ml-2 my-2 lg:my-0"
                >
                    <div class="w-full my-4">
                        <div class="flex justify-between items-center my-4">
                            <h2
                                class="font-semibold text-base text-gray-700 capitalize"
                            >
                                Absent Staffs
                            </h2>
                        </div>

                        <div
                            class="flex flex-wrap items-center justify-between py-1"
                            v-for="(absent, index) in absents"
                            :key="index"
                        >
                            <div class="flex items-center">
                                <div class="w-6">
                                    <input
                                        type="checkbox"
                                        name="user_id"
                                        v-model="absent.user_id"
                                        class="tw-form-control w-full"
                                    />
                                </div>
                                <div class="mx-2">
                                    <p class="tw-form-label">
                                        {{ absent.user_name }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <div class="mx-1">
                                    <select
                                        name="reason_id"
                                        v-model="absent.reason_id"
                                        class="tw-form-control w-full"
                                    >
                                        <option value="" disabled>
                                            Select Reason
                                        </option>
                                        <option
                                            v-for="reason in absentReasonlist"
                                            :key="reason.id"
                                            :value="reason.id"
                                        >
                                            {{ reason.title }}
                                        </option>
                                    </select>
                                    <span
                                        v-if="errors['reason_id' + index]"
                                        class="text-red-500 text-xs font-semibold"
                                    >
                                        {{ errors["reason_id" + index] }}
                                    </span>
                                </div>

                                <div class="mx-1">
                                    <input
                                        type="text"
                                        name="remarks"
                                        v-model="absent.remarks"
                                        class="tw-form-control w-full"
                                        placeholder="Remarks"
                                    />
                                    <span
                                        v-if="errors['remarks' + index]"
                                        class="text-red-500 text-xs font-semibold"
                                    >
                                        {{ errors["remarks" + index] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="flex justify-end my-6" id="save_btn">
                            <a
                                href="#"
                                class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium"
                                @click="savestaffs"
                            >
                                Save
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit / Reset Buttons -->
            <div class="my-6 hidden" id="btn_div">
                <a
                    href="#"
                    id="submit-btn"
                    class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium"
                    @click="submitForm"
                >
                    Submit
                </a>
                <a
                    href="#"
                    class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium"
                    @click="resetForm"
                >
                    Reset
                </a>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ["url", "standard", "mode"],

    data() {
        return {
            list: [],
            absentReasonlist: [],
            stafflist: [],
            date: "",
            session: "",
            presents: [],
            absents: [],
            errors: {},
            success: null,
        };
    },

    methods: {
        resetForm() {
            window.location.reload();
        },

        selectStudent() {
            $("#select").removeClass("hidden").addClass("block");
            $("#select_student_btn").addClass("hidden").removeClass("block");

            this.presents = [];
            this.absents = [];

            this.stafflist.forEach((staff) => {
                this.presents.push({
                    present_id: staff.teacher_id,
                    user_name: staff.teacher_name,
                    user: staff,
                });
            });
        },

        absentStudent(e, student, index) {
            if (!e.target.checked) {
                // Move to absent
                this.absents.push({
                    user_id: student.teacher_id,
                    user_name: student.teacher_name,
                    reason_id: "",
                    remarks: "",
                });
                $("#" + student.teacher_id).addClass("hidden");
            } else {
                // Remove from absent
                this.absents = this.absents.filter(
                    (a) => a.user_id !== student.teacher_id
                );
            }
        },

        savestaffs() {
            $("#btn_div").removeClass("hidden").addClass("block");
            $("#save_btn").addClass("hidden").removeClass("block");
        },

        submitForm() {
            this.errors = {};
            this.success = null;

            let formData = new FormData();
            formData.append("date", this.date || "");
            formData.append("session", this.session || "");

            formData.append("absentCount", this.absents.length);
            for (let i = 0; i < this.absents.length; i++) {
                formData.append("user_id" + i, this.absents[i].user_id || "");
                formData.append(
                    "reason_id" + i,
                    this.absents[i].reason_id || ""
                );
                formData.append("remarks" + i, this.absents[i].remarks || "");
            }

            formData.append("presentCount", this.presents.length);
            for (let i = 0; i < this.presents.length; i++) {
                formData.append(
                    "present_id" + i,
                    this.presents[i].present_id || ""
                );
            }

            axios
                .post("/" + this.mode + "/attendance/staff/add", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                })
                .then((response) => {
                    this.success = response.data.success;
                    console.log("teacher att res==>", response);
                })
                .catch((error) => {
                    this.errors = error.response?.data?.errors || {};
                    console.error("Response error==>", error);
                });
        },

        getData() {
            axios
                .get("/" + this.mode + "/attendance/staff/list")
                .then((response) => {
                    this.list = response.data;
                    this.setData();
                });
        },

        setData() {
            this.stafflist = this.list.stafflist || [];
            this.absentReasonlist = this.list.absentReasonlist || [];
        },
    },

    created() {
        this.getData();
    },
};
</script>
