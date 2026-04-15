<template>
    <div class="bg-white shadow px-4">
        <!-- Success Message -->
        <div v-if="success" class="alert alert-success" id="success-alert">
            {{ success }}
        </div>

        <div
            class="grid place-items-center bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100"
        >
            <!-- Board of Education -->
            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="mb-2">
                        <label for="board" class="tw-form-label">
                            Board / Curriculum
                            <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <select
                        v-model="board"
                        name="board"
                        id="board"
                        class="w-full px-5 py-4 text-base border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white"
                        @change="onBoardChange"
                    >
                        <option value="" disabled>
                            Select Board / Curriculum
                        </option>
                        <option
                            v-for="b in boardlist"
                            :key="b.id"
                            :value="b.id"
                        >
                            {{ b.name }}
                        </option>
                    </select>
                    <span
                        v-if="errors.board"
                        class="text-red-500 text-xs font-semibold"
                    >
                        {{ errors.board[0] }}
                    </span>
                </div>
            </div>

            <!-- Education Level (Only show after board is selected) -->
            <div class="flex flex-col lg:flex-row" v-if="board">
                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700">
                            Highest Level
                            <span class="text-red-500">*</span>
                        </label>
                    </div>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <label class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="radio"
                                value="nursery"
                                v-model="standards"
                                class="mr-2"
                            />
                            <span class="text-sm">Nursery / Kindergarten</span>
                        </label>

                        <label class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="radio"
                                value="primary"
                                v-model="standards"
                                class="mr-2"
                            />
                            <span class="text-sm">Primary</span>
                        </label>

                        <label class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="radio"
                                value="O-Level"
                                v-model="standards"
                                class="mr-2"
                            />
                            <span class="text-sm">Secondary (O-Level)</span>
                        </label>

                        <label class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="radio"
                                value="A-Level"
                                v-model="standards"
                                class="mr-2"
                            />
                            <span class="text-sm">Advanced (A-Level)</span>
                        </label>

                        <label class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="radio"
                                value="international"
                                v-model="standards"
                                class="mr-2"
                            />
                            <span class="text-sm">International</span>
                        </label>
                    </div>
                    <span
                        v-if="errors.standards"
                        class="text-red-500 text-xs font-semibold"
                    >
                        {{ errors.standards[0] }}
                    </span>
                </div>
            </div>

            <div class="py-6">
                <a
                    href="#"
                    class="btn btn-primary submit-btn px-8 py-3"
                    @click.prevent="addStandardLink"
                >
                    Save School Board & Level
                </a>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ["url", "academic_year_id"],

    data() {
        return {
            board: "",
            standards: "",
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
            errors: {},
            success: null,
        };
    },

    methods: {
        onBoardChange() {
            // Optional: Reset standards when board changes
            // this.standards = '';
        },

        addStandardLink() {
            this.errors = {};
            this.success = null;

            if (!this.board) {
                this.errors.board = ["Board is required"];
                return;
            }
            if (!this.standards) {
                this.errors.standards = ["Highest level offered is required"];
                return;
            }

            let formData = new FormData();
            formData.append("board", this.board);
            formData.append("standards", this.standards);

            axios
                .post("/admin/standard/create", formData, {
                    headers: { "Content-Type": "multipart/form-data" },
                })
                .then((response) => {
                    this.success =
                        response.data.success ||
                        "Board and level saved successfully!";
                    // Optionally reload or redirect
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                })
                .catch((error) => {
                    this.errors = error.response?.data?.errors || {};
                    console.error(error);
                });
        },

        getData() {
            if (!this.academic_year_id) {
                alert("Please add an Academic Year first!");
                window.location.href = "/admin/academic/add";
            }
        },
    },

    created() {
        this.getData();
    },
};
</script>
