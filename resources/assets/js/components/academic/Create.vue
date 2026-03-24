<template>
    <div class="bg-white shadow px-4">
        <!-- Standard -->
        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <label class="tw-form-label"
                    >Standard<span class="text-red-500">*</span></label
                >

                <select
                    v-model="standard_id"
                    class="tw-form-control w-full"
                    @change="getStandard"
                >
                    <option value="" disabled>Select Standard</option>
                    <option
                        v-for="standard in standardlist"
                        :key="standard.id"
                        :value="standard.id"
                    >
                        {{ convertInteger(standard.name) }}
                    </option>
                </select>

                <span v-if="errors.standard_id" class="text-red-500 text-xs">
                    {{ errors.standard_id[0] }}
                </span>
            </div>
        </div>

        <!-- Section -->
        <div class="flex flex-col lg:flex-row mt-4">
            <div class="tw-form-group w-full lg:w-1/3">
                <label class="tw-form-label"
                    >Section<span class="text-red-500">*</span></label
                >

                <select v-model="section_id" class="tw-form-control w-full">
                    <option value="" disabled>Select Section</option>
                    <option
                        v-for="section in sectionlist"
                        :key="section.id"
                        :value="section.id"
                    >
                        {{ section.name }}
                    </option>
                </select>

                <span v-if="errors.section_id" class="text-red-500 text-xs">
                    {{ errors.section_id[0] }}
                </span>
            </div>

            <!-- ADD SUBJECT BUTTON -->
            <div
                class="w-full lg:w-1/3 flex items-end"
                v-if="standard_id !== '' && section_id !== ''"
            >
                <a
                    href="#"
                    class="bg-blue-500 text-white px-3 py-1 rounded ml-4"
                    @click.prevent="showModal('subject')"
                >
                    Add New Subject
                </a>
            </div>
        </div>

        <!-- SUBJECT LIST -->
        <div v-if="standard_id && section_id" class="mt-4">
            <div
                v-for="subject in subjectlist[standard_id]?.[section_id] || []"
                :key="subject.id"
                class="flex items-center"
            >
                <input
                    type="checkbox"
                    :value="subject.id"
                    @change="addRow(subject.id)"
                />
                <span class="ml-2">{{ subject.name }}</span>
            </div>

            <!-- Add Row Button -->
            <a
                href="#"
                class="bg-green-500 text-white px-3 py-1 rounded mt-2 inline-block"
                @click.prevent="addRow()"
            >
                Add Row
            </a>
        </div>

        <!-- SUBJECT MODAL -->
        <div v-if="show === 'subject'" class="modal modal-mask">
            <div class="modal-wrapper">
                <div class="modal-container">
                    <h2>Add Subject</h2>

                    <input
                        type="text"
                        v-model="subject"
                        placeholder="Subject Name"
                        class="tw-form-control w-full mt-2"
                    />

                    <input
                        type="text"
                        v-model="code"
                        placeholder="Subject Code"
                        class="tw-form-control w-full mt-2"
                    />

                    <select v-model="type" class="tw-form-control w-full mt-2">
                        <option value="" disabled>Select Type</option>
                        <option value="core">Core</option>
                        <option value="elective">Elective</option>
                    </select>

                    <button
                        class="bg-blue-500 text-white px-4 py-1 mt-4"
                        @click="addSubject"
                    >
                        Submit
                    </button>

                    <button class="ml-2" @click="closeModal">Close</button>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div v-if="inputs.length > 0" class="mt-6">
            <table class="w-full border">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Type</th>
                        <th>Delete</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(input, index) in inputs" :key="index">
                        <td>
                            <select v-model="input.subject_id">
                                <option disabled>Select</option>
                                <option
                                    v-for="s in subjectlist[standard_id][
                                        section_id
                                    ]"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{ s.name }}
                                </option>
                            </select>
                        </td>

                        <td>
                            <select v-model="input.teacher_id">
                                <option disabled>Select</option>
                                <option
                                    v-for="t in teacherlist"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.fullname }}
                                </option>
                            </select>
                        </td>

                        <td>
                            <input v-model="input.subject_type" />
                        </td>

                        <td>
                            <button @click="deleteRow(index)">X</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            standard_id: "",
            section_id: "",
            subject: "",
            code: "",
            type: "",
            show: 0,
            inputs: [],
            subjectlist: [],
            sectionlist: [],
            standardlist: [],
            teacherlist: [],
            errors: [],
        };
    },

    methods: {
        showModal(name) {
            this.show = name;
        },

        closeModal() {
            this.show = 0;
        },

        addRow(id) {
            this.inputs.push({
                subject_id: id || "",
                teacher_id: "",
                subject_type: "",
            });
        },

        deleteRow(index) {
            this.inputs.splice(index, 1);
        },

        getStandard() {
            axios
                .get("/admin/getStandard?standard_id=" + this.standard_id)
                .then((res) => {
                    this.standard_name = res.data.name;
                });
        },

        addSubject() {
            let formData = new FormData();
            formData.append("subject_standard_id", this.standard_id);
            formData.append("subject_section_id", this.section_id);
            formData.append("subject", this.subject);
            formData.append("code", this.code);
            formData.append("type", this.type);

            axios.post("/admin/subjects/add", formData).then(() => {
                this.closeModal();
            });
        },
    },

    created() {
        axios.get("/admin/standardLink/list").then((res) => {
            this.standardlist = res.data.standardlist;
            this.sectionlist = res.data.sectionlist;
            this.subjectlist = res.data.subjectlist;
            this.teacherlist = res.data.teacherlist;
        });
    },
};
</script>
