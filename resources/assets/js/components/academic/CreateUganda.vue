<template>
    <div class="bg-white shadow px-4">
        <!-- Success Message -->
        <div
            v-if="success != null"
            class="alert alert-success"
            id="success-alert"
        >
            {{ success }}
        </div>

        <!-- Level / Class Selection -->
        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="mb-2">
                    <label for="standard_id" class="tw-form-label">
                        Level / Standard <span class="text-red-500">*</span>
                    </label>
                </div>
                <select
                    v-model="standard_id"
                    name="standard_id"
                    id="standard_id"
                    class="tw-form-control w-full"
                    @change="addRow()"
                >
                    <option value="" disabled>Select Level / Standard</option>
                    <option
                        v-for="level in standardlist"
                        :key="level.id"
                        :value="level.id"
                    >
                        {{ level.name }}
                    </option>
                </select>
                <span
                    v-if="errors.standard_id"
                    class="text-red-500 text-xs font-semibold"
                >
                    {{ errors.standard_id[0] }}
                </span>
            </div>
        </div>

        <!-- Section  @UG is class -->
        <div class="flex-col lg:flex-row">
            <!-- Fixed: add "flex" -->
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="mb-2">
                    <label for="section_id" class="tw-form-label">
                        Class <span class="text-red-500">*</span>
                    </label>
                </div>
                <div class="flex flex-col lg:flex-row md:flex-row">
                    <div class="w-full lg:w-8/12 md:w-8/12">
                        <select
                            v-model="section_id"
                            name="section_id"
                            id="section_id"
                            class="tw-form-control w-full"
                            @change="addRow()"
                        >
                            <option value="" disabled>Select Class</option>
                            <option
                                v-for="section in sectionlist"
                                :key="section.id"
                                :value="section.id"
                            >
                                {{ section.name }}
                            </option>
                        </select>
                        <span
                            v-if="errors.section_id"
                            class="text-red-500 text-xs font-semibold"
                        >
                            {{ errors.section_id[0] }}
                        </span>
                    </div>
                    <div class="w-full lg:w-4/12 md:w-4/12 hidden">
                        <div class="lg:mx-3 md:mx-3">
                            <a
                                href="#"
                                class="bg-blue-500 rounded text-sm text-white px-2 py-1 whitespace-nowrap"
                                @click="showModal('section')"
                            >
                                Add New Class
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Class Teacher -->
        <div class="tw-form-group">
            <div class="flex flex-col lg:flex-row">
                <div class="w-full lg:w-1/3">
                    <div class="mb-2">
                        <label for="class_teacher_id" class="tw-form-label">
                            Class Teacher <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <select
                        class="tw-form-control w-full"
                        id="class_teacher_id"
                        v-model="class_teacher_id"
                        name="class_teacher_id"
                    >
                        <option value="" disabled>Select Class Teacher</option>
                        <option
                            v-for="teacher in teacherlist"
                            :key="teacher.id"
                            :value="teacher.id"
                        >
                            {{ teacher.fullname }}
                        </option>
                    </select>
                    <span
                        v-if="errors.class_teacher_id"
                        class="text-red-500 text-xs font-semibold"
                    >
                        {{ errors.class_teacher_id[0] }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Subjects Section -->
        <!-- <div class="tw-form-group">
            <div class="flex flex-col lg:flex-row items-center">
                <div class="w-full lg:w-1/3 md:w-1/3">
                    <label class="tw-form-label">
                        Subjects <span class="text-red-500">*</span>
                    </label>
                </div>
                <div class="lg:mx-3 md:mx-0">
                    <a
                        href="#"
                        class="bg-blue-500 rounded text-sm text-white px-2 py-1 whitespace-nowrap"
                        @click="showModal('subject')"
                    >
                        Add New Subject
                    </a>
                </div>
            </div>
        </div> -->

        <!-- Subject-Teacher Assignment Table -->
        <div class="tw-form-group" v-if="standard_id && section_id">
            <table
                class="w-full lg:w-3/4 md:w-3/4 border"
                v-if="inputs.length > 0"
            >
                <thead class="bg-gray-400">
                    <tr class="border-b">
                        <th class="tw-form-label py-2">
                            Subject <span class="text-red-500">*</span>
                        </th>
                        <th class="tw-form-label py-2">
                            Teacher <span class="text-red-500">*</span>
                        </th>
                        <th class="w-12"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        class="border-b"
                        v-for="(input, index) in inputs"
                        :key="index"
                    >
                        <td class="py-3 px-2">
                            <select
                                class="tw-form-control w-full"
                                v-model="input.subject_id"
                                name="subject_id[]"
                            >
                                <option value="" disabled>
                                    Select Subject
                                </option>
                                <option
                                    v-for="subject in (subjectlist[
                                        standard_id
                                    ] &&
                                        subjectlist[standard_id][section_id]) ||
                                    []"
                                    :key="subject.id"
                                    :value="subject.id"
                                >
                                    {{ subject.name }}
                                </option>
                            </select>
                            <span
                                v-if="errors['subject_id' + index]"
                                class="text-red-500 text-xs font-semibold"
                            >
                                {{ errors["subject_id" + index][0] }}
                            </span>
                        </td>

                        <td class="py-3 px-2">
                            <select
                                class="tw-form-control w-full"
                                v-model="input.teacher_id"
                                name="teacher_id[]"
                            >
                                <option value="" disabled>
                                    Select Teacher
                                </option>
                                <option
                                    v-for="teacher in teacherlist"
                                    :key="teacher.id"
                                    :value="teacher.id"
                                >
                                    {{ teacher.fullname }}
                                </option>
                            </select>
                            <span
                                v-if="errors['teacher_id' + index]"
                                class="text-red-500 text-xs font-semibold"
                            >
                                {{ errors["teacher_id" + index][0] }}
                            </span>
                        </td>

                        <td class="py-3 px-2 text-center">
                            <a
                                href="#"
                                class="btn-times"
                                @click="deleteRow(index, input)"
                                title="Remove"
                            >
                                <!-- Delete Icon -->
                                <svg
                                    version="1.1"
                                    id="Capa_1"
                                    xmlns="http://www.w3.org/2000/svg"
                                    xmlns:xlink="http://www.w3.org/1999/xlink"
                                    x="0px"
                                    y="0px"
                                    viewBox="0 0 512 512"
                                    class="w-5 h-5 fill-current text-red-600"
                                >
                                    <path
                                        d="M17.379,76.867v40.104h41.789L92.32,493.706C93.229,504.059,101.899,512,112.292,512h286.74c10.394,0,19.07-7.947,19.972-18.301l33.153-376.728h42.464V76.867H17.379z M380.665,471.896H130.654L99.426,116.971h312.474L380.665,471.896z"
                                    />
                                    <path
                                        d="M321.504,0H190.496c-18.428,0-33.42,14.992-33.42,33.42v63.499h40.104V40.104h117.64v56.815h40.104V33.42C354.924,14.992,339.932,0,321.504,0z"
                                    />
                                </svg>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p v-else class="text-gray-500 text-sm py-4">
                No subjects found for this level and class. Please add subjects
                first.
            </p>
        </div>

        <!-- Submit Button -->
        <div class="py-6">
            <a
                href="#"
                class="btn btn-primary submit-btn px-8 py-3"
                @click.prevent="addStandardLink"
            >
                Save Class Details
            </a>
        </div>

        <!-- Add Section Modal =====hidden=====-->
        <div v-if="show === 'section'" class="modal modal-mask hidden">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full max-w-md px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>Add New Class</h2>
                        <button
                            class="modal-default-button text-2xl py-1"
                            @click="closeModal"
                        >
                            ×
                        </button>
                    </div>
                    <div class="modal-body">
                        <label class="tw-form-label"
                            >Class Name
                            <span class="text-red-500">*</span></label
                        >
                        <!-- <input
                            type="text"
                            v-model="section"
                            class="tw-form-control w-full"
                            placeholder="e.g. Baby class, Primary 1, Senior 1"
                        /> -->
                        <select
                            v-model="section"
                            class="tw-form-control w-full"
                        >
                            <option value="_______" disabled>
                                Select Class
                            </option>
                            <option
                                v-for="(element, index) in allclasses"
                                :key="index"
                                :value="element"
                            >
                                {{ element }}
                            </option>
                        </select>
                        <span
                            v-if="errors.section"
                            class="text-red-500 text-xs font-semibold"
                        >
                            {{ errors.section[0] }}
                        </span>
                    </div>
                    <div class="my-6">
                        <a
                            href="#"
                            class="btn btn-submit blue-bg text-white rounded px-6 py-2"
                            @click="addSection"
                        >
                            Add Class
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Subject Modal -->
        <!-- <div v-if="show === 'subject'" class="hidden modal modal-mask">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full max-w-2xl px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>Add New Subject(s)</h2>
                        <button
                            class="modal-default-button text-2xl py-1"
                            @click="closeModal"
                        >
                            ×
                        </button>
                    </div>

                    <div class="modal-body">
                        <table class="w-full border">
                            <thead class="bg-gray-300">
                                <tr>
                                    <th class="tw-form-label px-3 py-2">
                                        Subject Name
                                        <span class="text-red-500">*</span>
                                    </th>
                                    <th class="tw-form-label px-3 py-2">
                                        Code
                                    </th>
                                    <th class="tw-form-label px-3 py-2">
                                        Type <span class="text-red-500">*</span>
                                    </th>
                                    <th class="w-20"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(sub, k) in subjectoptions"
                                    :key="k"
                                    class="border-b"
                                >
                                    <td class="p-2">
                                        <input
                                            type="text"
                                            v-model="sub.subject_name"
                                            class="tw-form-control w-full"
                                            placeholder="Subject Name"
                                        />
                                    </td>
                                    <td class="p-2">
                                        <input
                                            type="text"
                                            v-model="sub.subject_code"
                                            class="tw-form-control w-full"
                                            placeholder="Code"
                                        />
                                    </td>
                                    <td class="p-2">
                                        <select
                                            v-model="sub.subject_type"
                                            class="tw-form-control w-full"
                                        >
                                            <option value="" disabled>
                                                Select Type
                                            </option>
                                            <option value="core">Core</option>
                                            <option value="elective">
                                                Elective
                                            </option>
                                        </select>
                                    </td>
                                    <td class="p-2 text-center">
                                        <button
                                            class="text-xl text-red-600 hover:text-red-800"
                                            @click.prevent="removeoption(k)"
                                            v-if="
                                                subjectoptions.length > 1 ||
                                                k > 0
                                            "
                                        >
                                            −
                                        </button>
                                        <button
                                            v-if="
                                                k === subjectoptions.length - 1
                                            "
                                            class="text-xl text-green-600 hover:text-green-800 ml-2"
                                            @click.prevent="addoption"
                                        >
                                            +
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="my-6 flex gap-3">
                        <a
                            href="#"
                            class="btn btn-submit blue-bg text-white rounded px-6 py-2"
                            @click="addSubject"
                        >
                            Save Subjects
                        </a>
                        <a
                            href="#"
                            class="btn btn-secondary"
                            @click="closeModal"
                            >Cancel</a
                        >
                    </div>
                </div>
            </div>
        </div> -->
    </div>
</template>

<script>
export default {
    props: ["url"],

    data() {
        return {
            allclasses: [
                "Baby Class",
                "Middle Class",
                "Top Class",
                "Primary 1",
                "Primary 2",
                "Primary 3",
                "Primary 4",
                "Primary 5",
                "Primary 6",
                "Primary 7",
                "Senior 1",
                "Senior 2",
                "Senior 3",
                "Senior 4",
                "Senior 5",
                "Senior 6",
            ],
            academic_year_id: "",
            standard_id: "",
            section_id: "",
            class_teacher_id: "",
            section: "",
            standardlist: [],
            sectionlist: [],
            subjectlist: {},
            teacherlist: [],
            inputs: [],
            subjectoptions: [
                { subject_name: "", subject_code: "", subject_type: "core" },
            ],
            show: "",
            errors: {},
            success: null,
        };
    },

    methods: {
        getData() {
            axios
                .get("/admin/standardLink/list")
                .then((response) => {
                    this.list = response.data;
                    this.setData();
                })
                .catch(() => {
                    alert("Failed to load data");
                });
        },

        setData() {
            if (Object.keys(this.list || {}).length > 0) {
                this.academic_year_id = this.list.academic_year_id;
                this.standardlist = this.list.standardlist || [];
                this.sectionlist = this.list.sectionlist || [];
                this.subjectlist = this.list.subjectlist || {};
                this.teacherlist = this.list.teacherlist || [];
            }
        },

        addRow() {
            this.inputs = [];

            const subjects =
                this.subjectlist[this.standard_id]?.[this.section_id] || [];

            if (subjects.length === 0) {
                // alert("Please add subjects for this level and section first.");
                return;
            }

            subjects.forEach((subject) => {
                this.inputs.push({
                    subject_id: subject.id,
                    teacher_id: "",
                });
            });
        },

        addStandardLink() {
            this.errors = {};
            this.success = null;

            let formData = new FormData();
            formData.append("standard_id", this.standard_id);
            formData.append("section_id", this.section_id);
            formData.append("class_teacher_id", this.class_teacher_id);
            formData.append("count", this.inputs.length);

            this.inputs.forEach((input, i) => {
                formData.append(`subject_id${i}`, input.subject_id || "");
                formData.append(`teacher_id${i}`, input.teacher_id || "");
            });

            axios
                .post("/admin/standardLink/add", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                })
                .then((response) => {
                    this.success = response.data.success;
                    setTimeout(() => window.location.reload(), 800);
                })
                .catch((error) => {
                    this.errors = error.response?.data?.errors || {};
                });
        },

        addSection() {
            this.errors = {};
            let formData = new FormData();
            formData.append("section", this.section);

            axios
                .post("/admin/section/add", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                })
                .then(() => {
                    this.success = "Section added successfully";
                    this.closeModal();
                    this.getData(); // refresh lists
                })
                .catch((error) => {
                    this.errors = error.response?.data?.errors || {};
                });
        },

        addSubject() {
            this.errors = {};
            let formData = new FormData();
            formData.append("subject_standard_id", this.standard_id);
            formData.append("subject_section_id", this.section_id);
            formData.append("subjectscount", this.subjectoptions.length);

            this.subjectoptions.forEach((sub, i) => {
                formData.append(`subject_name${i}`, sub.subject_name || "");
                formData.append(`subject_code${i}`, sub.subject_code || "");
                formData.append(`subject_type${i}`, sub.subject_type || "");
            });

            axios
                .post("/admin/subjects/create", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                })
                .then(() => {
                    this.success = "Subjects added successfully";
                    this.closeModal();
                    this.getData(); // refresh subject list
                    this.addRow(); // refresh inputs
                })
                .catch((error) => {
                    this.errors = error.response?.data?.errors || {};
                });
        },

        showModal(name) {
            this.show = name;
        },

        closeModal() {
            this.show = "";
            this.section = "";
            this.errors = {};
        },

        addoption() {
            this.subjectoptions.push({
                subject_name: "",
                subject_code: "",
                subject_type: "core",
            });
        },

        removeoption(index) {
            if (this.subjectoptions.length > 1) {
                this.subjectoptions.splice(index, 1);
            }
        },

        deleteRow(index, input) {
            if (!input.subject_id) {
                this.inputs.splice(index, 1);
                return;
            }

            if (confirm("Remove this subject and teacher assignment?")) {
                this.inputs.splice(index, 1);
                // Optional: delete subject from DB if needed
                // axios.get("/admin/subject/delete/" + input.subject_id);
            }
        },
    },

    created() {
        this.getData();
    },
};
</script>

<style scoped>
/* You can keep your existing modal styles here if needed */
.modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: table;
}

.modal-wrapper {
    display: table-cell;
    vertical-align: middle;
}

.modal-container {
    margin: 0 auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    max-width: 700px;
    max-height: 90vh;
    overflow: auto;
}
</style>
