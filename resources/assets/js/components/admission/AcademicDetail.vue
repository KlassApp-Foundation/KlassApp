<template>
    <div
        class="bg-white shadow px-4 py-3"
        v-bind:class="[this.profile_tab == 3 ? 'block' : 'hidden']"
    >
        <div>
            <fieldset class="shadow">
                <h6 class="text-sm font-bold mb-3">Previous School Marks</h6>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full">
                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2"
                                        >English</label
                                    >
                                    <input
                                        type="text"
                                        name="english"
                                        v-model="english"
                                        placeholder="English"
                                        class="tw-form-control w-1/2 mx-4 my-1 py-2"
                                    />
                                </div>
                                <span
                                    v-if="errors.english"
                                    class="text-red-500 text-xs font-semibold"
                                    >{{ errors.english[0] }}</span
                                >
                            </div>
                            <!-- UGANDAN FIELDS SPOT: school-specific fields land here (owner will define) -->
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2"
                                        >Maths</label
                                    >
                                    <input
                                        type="text"
                                        name="maths"
                                        v-model="maths"
                                        placeholder="Maths"
                                        class="tw-form-control w-1/2 mx-4 my-1 py-2"
                                    />
                                </div>
                                <span
                                    v-if="errors.maths"
                                    class="text-red-500 text-xs font-semibold"
                                    >{{ errors.maths[0] }}</span
                                >
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2"
                                        >Science</label
                                    >
                                    <input
                                        type="text"
                                        name="science"
                                        v-model="science"
                                        placeholder="Science"
                                        class="tw-form-control w-1/2 mx-4 my-1 py-2"
                                    />
                                </div>
                                <span
                                    v-if="errors.science"
                                    class="text-red-500 text-xs font-semibold"
                                    >{{ errors.science[0] }}</span
                                >
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2"
                                        >Social Studies</label
                                    >
                                    <input
                                        type="text"
                                        name="social"
                                        v-model="social"
                                        placeholder="Social Studies"
                                        class="tw-form-control w-1/2 mx-4 my-1 py-2"
                                    />
                                </div>
                                <span
                                    v-if="errors.social"
                                    class="text-red-500 text-xs font-semibold"
                                    >{{ errors.social[0] }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="school_last_studied" class="tw-form-label">Previous school <span v-if="prevRequired" class="text-red-500">*</span><span v-else class="text-gray-500">(optional for nursery and P.1)</span></label>
                            <input type="text" name="school_last_studied" v-model="school_last_studied" placeholder="Previous school" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.school_last_studied" class="text-red-500 text-xs font-semibold">{{ errors.school_last_studied[0] }}</span>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="last_class_completed" class="tw-form-label">Last class completed <span v-if="prevRequired" class="text-red-500">*</span></label>
                            <input type="text" name="last_class_completed" v-model="last_class_completed" placeholder="Last class completed" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.last_class_completed" class="text-red-500 text-xs font-semibold">{{ errors.last_class_completed[0] }}</span>
                    </div>
                </div>

                <div v-if="isS1" class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="ple_index_number" class="tw-form-label">PLE index number<span class="text-red-500">*</span></label>
                            <input type="text" name="ple_index_number" v-model="ple_index_number" placeholder="PLE index number" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.ple_index_number" class="text-red-500 text-xs font-semibold">{{ errors.ple_index_number[0] }}</span>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="ple_aggregate" class="tw-form-label">PLE aggregate<span class="text-red-500">*</span></label>
                            <input type="text" name="ple_aggregate" v-model="ple_aggregate" placeholder="PLE aggregate" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.ple_aggregate" class="text-red-500 text-xs font-semibold">{{ errors.ple_aggregate[0] }}</span>
                    </div>
                </div>

                <div v-if="isS5" class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="uce_index_number" class="tw-form-label">UCE index number<span class="text-red-500">*</span></label>
                            <input type="text" name="uce_index_number" v-model="uce_index_number" placeholder="UCE index number" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.uce_index_number" class="text-red-500 text-xs font-semibold">{{ errors.uce_index_number[0] }}</span>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="uce_results_summary" class="tw-form-label">UCE results summary<span class="text-red-500">*</span></label>
                            <input type="text" name="uce_results_summary" v-model="uce_results_summary" placeholder="UCE results summary" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.uce_results_summary" class="text-red-500 text-xs font-semibold">{{ errors.uce_results_summary[0] }}</span>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">
                    <div class="w-full my-1">
                        <h6 class="text-sm font-bold mb-3">
                            Examination Board<span class="text-red-500">*</span>
                        </h6>
                        <ul class="list-reset leading-loose flex items-center">
                            <li v-for="board in boardlist">
                                <input
                                    type="radio"
                                    v-model="board_of_education"
                                    name="board_of_education"
                                    :value="board.id"
                                />
                                <span class="text-sm mr-6">{{
                                    board.name
                                }}</span>
                            </li>
                        </ul>
                        <span
                            v-if="errors.board_of_education"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.board_of_education[0] }}</span
                        >
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full my-1">
                        <h6 class="text-sm font-bold mb-3">
                            Choice of Language<span class="text-red-500"
                                >*</span
                            >
                        </h6>
                        <ul class="list-reset leading-loose flex items-center">
                            <li v-for="language in languagelist">
                                <input
                                    type="radio"
                                    v-model="choice_of_language"
                                    name="choice_of_language"
                                    :value="language.id"
                                />
                                <span class="text-sm mr-6">{{
                                    language.name
                                }}</span>
                            </li>
                        </ul>
                        <span
                            v-if="errors.choice_of_language"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.choice_of_language[0] }}</span
                        >
                    </div>
                </div>
                <div class="my-1">
                    <h6 class="text-sm font-bold mb-3">
                        Group Selection<span
                            class="text-red-500 whitespace-nowrap"
                            >*Only for UNEB candidate classes (e.g. S.4, S.6)</span
                        >
                    </h6>
                    <ul class="list-reset leading-loose">
                        <li v-for="group in grouplist">
                            <input
                                type="radio"
                                v-model="group_selection"
                                name="group_selection"
                                :value="group.id"
                                @click="selectGroup(group.id)"
                            />
                            <span class="text-sm mx-2">{{ group.name }}</span>
                        </li>
                    </ul>
                    <span
                        v-if="errors.group_selection"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.group_selection[0] }}</span
                    >
                </div>

                <div class="flex-col lg:flex-row hidden">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label
                                for="board_registration_number"
                                class="tw-form-label"
                                ><h6 class="text-sm font-bold mb-3">
                                    Board Registration Number<span
                                        class="text-red-500 whitespace-nowrap"
                                        >*Only for UNEB candidate classes (e.g. S.4, S.6)</span
                                    >
                                </h6></label
                            >
                            <input
                                type="text"
                                v-model="board_registration_number"
                                name="board_registration_number"
                                placeholder="Board Registration Number"
                                class="tw-form-control w-full my-1 py-2"
                            />
                        </div>
                        <span
                            v-if="errors.board_registration_number"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.board_registration_number[0] }}</span
                        >
                    </div>
                </div>

                <a
                    href="#"
                    dusk="submit-btn"
                    class="btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1"
                    @click="previousForm('2')"
                    >Previous</a
                >
                <a
                    href="#"
                    dusk="submit-btn"
                    class="btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1"
                    @click="submitForm('4')"
                    >Next</a
                >
            </fieldset>
        </div>
    </div>
</template>

<script>
import { bus } from "../../event-bus";
import PortalVue from "portal-vue";
export default {
    props: ["url", "slug"],
    data() {
        return {
            profile_tab: "",
            half_yearly_mark_details: "",
            english: "",
            maths: "",
            science: "",
            social: "",
            school_last_studied: "",
            last_class_completed: "",
            ple_index_number: "",
            ple_aggregate: "",
            uce_index_number: "",
            uce_results_summary: "",
            standardlist: [],
            board_of_education: "",
            board_registration_number: "",
            choice_of_language: "",
            group_selection: "",
            standard_id: "",
            boardlist: [
                {
                    id: "uneb",
                    name: "UNEB (Uganda National Examinations Board)",
                },
                { id: "cambridge", name: "Cambridge International (CIE)" },
                { id: "ib", name: "International Baccalaureate (IB)" },
                { id: "montessori", name: "Montessori" },
                { id: "other", name: "Other / Custom Curriculum" },
            ],
            languagelist: [
                { id: "tamil", name: "Tamil" },
                { id: "hindi", name: "Hindi" },
                { id: "sanskrit", name: "Sanskrit" },
                { id: "french", name: "French" },
            ],
            grouplist: [
                {
                    id: "group1",
                    name: "Group 1    :   Maths, Physics, Chemistry &Computer Science",
                },
                {
                    id: "group2",
                    name: "Group 2    :   Maths, Physics, Chemistry & Biology",
                },
                {
                    id: "group3",
                    name: "Group 3    :   Physics, Chemistry, Biology & Computer Science",
                },
                {
                    id: "group4",
                    name: "Group 4    :   Commerce, Accountancy, Economics & Business Maths",
                },
                {
                    id: "group5",
                    name: "Group 5    :   Commerce, Accountancy, Economics & Computer Science",
                },
            ],
            errors: [],
            success: null,
        };
    },

    computed: {
        standardName() {
            const match = this.standardlist.find(
                (s) => String(s.id) === String(this.standard_id)
            );
            return match
                ? String(match.name).toUpperCase().replace(/\s+/g, " ").trim()
                : "";
        },
        isS1() {
            return /^(S\.?\s?1|SENIOR\s?1|SENIOR ONE)$/.test(this.standardName);
        },
        isS5() {
            return /^(S\.?\s?5|SENIOR\s?5|SENIOR FIVE)$/.test(this.standardName);
        },
        prevRequired() {
            const name = this.standardName;
            if (!name) {
                return true;
            }
            const nursery = ["BABY CLASS", "MIDDLE CLASS", "TOP CLASS", "NURSERY"].includes(name);
            const p1 = /^P\.?\s?1$/.test(name);
            return !nursery && !p1;
        },
    },

    methods: {
        submitForm(val) {
            this.errors = [];
            this.success = null;

            let formData = new FormData();

            formData.append("english", this.english);
            formData.append("maths", this.maths);
            formData.append("science", this.science);
            formData.append("social", this.social);
            formData.append("board_of_education", this.board_of_education);
            formData.append("choice_of_language", this.choice_of_language);
            formData.append("group_selection", this.group_selection);
            formData.append(
                "board_registration_number",
                this.board_registration_number
            );
            formData.append("standard_id", this.standard_id);
            formData.append("school_last_studied", this.school_last_studied);
            formData.append("last_class_completed", this.last_class_completed);
            formData.append("ple_index_number", this.ple_index_number);
            formData.append("ple_aggregate", this.ple_aggregate);
            formData.append("uce_index_number", this.uce_index_number);
            formData.append("uce_results_summary", this.uce_results_summary);

            axios
                .post(
                    this.url +
                        "/" +
                        this.slug +
                        "/admission-form/validationAcademicDetail",
                    formData,
                    { headers: { "Content-Type": "multipart/form-data" } }
                )
                .then((response) => {
                    this.setProfileTab(val);
                })
                .catch((error) => {
                    this.errors = error.response.data.errors;
                });
        },

        previousForm(val) {
            this.setProfileTab(val);
        },

        setProfileTab(val) {
            this.profile_tab = val;
            bus.$emit("dataAdmissionTab", this.profile_tab);
            bus.$emit("standardVal", this.standard_id);
        },

        selectGroup(val) {
            if (this.group_selection == val) {
                this.group_selection = false;
            } else {
                this.group_selection = val;
            }
        },
    },

    created() {
        axios.get(this.url + "/" + this.slug + "/standardlist").then((response) => {
            this.standardlist = response.data.standardlist;
        });

        bus.$on("dataAdmissionTab", (data) => {
            if (data != "") {
                this.profile_tab = data;
            }
        });
        bus.$on("standardVal", (data) => {
            if (data != "") {
                this.standard_id = data;
            }
        });
    },
};
</script>
