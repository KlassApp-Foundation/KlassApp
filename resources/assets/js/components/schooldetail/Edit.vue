<template>
    <div class="">
        <div class="bg-white shadow px-4">
            <div
                v-if="this.success != null"
                class="alert alert-success"
                id="success-alert"
            >
                {{ this.success }}
            </div>

            <!-- School Logo -->
            <div class="py-3">
                <div class="flex items-center">
                    <img
                        :src="school_logo_display"
                        class="img-responsive w-20 h-20 rounded-full"
                    />
                    <div class="mx-2">
                        <label
                            class="tw-label cursor-pointer text-xs text-gray-600"
                        >
                            Change School Logo
                            <input
                                type="file"
                                size="60"
                                name="school_logo"
                                id="school_logo"
                                @change="OnFileSelected"
                            />
                            <span
                                v-if="errors.school_logo"
                                class="text-red-500 text-xs font-semibold"
                                >{{ errors.school_logo[0] }}</span
                            >
                        </label>
                    </div>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="name" class="tw-form-label"
                                >School Name<span class="text-red-500"
                                    >*</span
                                ></label
                            >
                        </div>
                        <div class="w-full lg:w-3/4 my-2">
                            <input
                                type="text"
                                name="name"
                                v-model="name"
                                id="name"
                                class="tw-form-control w-full"
                                placeholder="Enter School Name"
                            />
                        </div>
                        <span
                            v-if="errors.name"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.name[0] }}</span
                        >
                    </div>
                </div>
                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="moto" class="tw-form-label"
                                >School Moto<span class="text-red-500"
                                    >*</span
                                ></label
                            >
                        </div>
                        <div class="w-full lg:w-3/4 my-2">
                            <input
                                type="text"
                                name="moto"
                                v-model="moto"
                                id="moto"
                                class="tw-form-control w-full"
                                placeholder="Enter School Moto"
                            />
                        </div>
                        <span
                            v-if="errors.moto"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.moto[0] }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- ==================== ADMISSION SECTION COMMENTED OUT ==================== -->
            <!-- 
            <div class="flex flex-col lg:flex-row">
                ... (admission fields)
            </div>
            -->

            <!-- Establishment & Board -->
            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label
                                for="date_of_establishment"
                                class="tw-form-label"
                                >Date Of Establishment<span class="text-red-500"
                                    >*</span
                                ></label
                            >
                        </div>
                        <div class="w-full lg:w-3/4 my-2">
                            <input
                                type="date"
                                name="date_of_establishment"
                                v-model="date_of_establishment"
                                id="date_of_establishment"
                                class="tw-form-control w-full"
                            />
                        </div>
                        <span
                            v-if="errors.date_of_establishment"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.date_of_establishment[0] }}</span
                        >
                    </div>
                </div>

                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="board" class="tw-form-label"
                                >Board Of Education<span class="text-red-500"
                                    >*</span
                                ></label
                            >
                        </div>
                        <div class="w-full lg:w-3/4 my-2">
                            <select
                                name="board"
                                v-model="board"
                                id="board"
                                class="tw-form-control w-full"
                            >
                                <option value="" disabled>Select board</option>
                                <option
                                    v-for="boards in boardlist"
                                    :value="boards.id"
                                >
                                    {{ boards.name }}
                                </option>
                            </select>
                            <span
                                v-if="errors.board"
                                class="text-red-500 text-xs font-semibold"
                                >{{ errors.board[0] }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>

            <portal-target name="edit_school_address"></portal-target>

            <!-- Location -->
            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="country" class="tw-form-label"
                                >Country<span class="text-red-500"
                                    >*</span
                                ></label
                            >
                        </div>
                        <div class="w-full lg:w-3/4 my-2">
                            <select
                                class="tw-form-control w-full"
                                id="country_id"
                                v-model="country_id"
                                name="country_id"
                            >
                                <option value="" disabled>
                                    Select Country
                                </option>
                                <option
                                    v-for="country in countrylist"
                                    :value="country.id"
                                >
                                    {{ country.name }}
                                </option>
                            </select>
                        </div>
                        <span
                            v-if="errors.country_id"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.country_id[0] }}</span
                        >
                    </div>
                </div>

                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="city" class="tw-form-label"
                                >District<span class="text-red-500"
                                    >*</span
                                ></label
                            >
                        </div>
                        <div class="w-full lg:w-3/4 my-2">
                            <select
                                class="tw-form-control w-full"
                                id="city_id"
                                v-model="city_id"
                                name="city_id"
                            >
                                <option value="" disabled>
                                    Select District
                                </option>
                                <option
                                    v-for="city in citylist[country_id]"
                                    :value="city.id"
                                >
                                    {{ city.name }}
                                </option>
                            </select>
                        </div>
                        <span
                            v-if="errors.city_id"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.city_id[0] }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- About & Website -->
            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="about_us" class="tw-form-label"
                                >About Us<span class="text-red-500"
                                    >*</span
                                ></label
                            >
                        </div>
                        <div class="w-full lg:w-3/4 my-2">
                            <textarea
                                name="about_us"
                                v-model="about_us"
                                id="about_us"
                                class="tw-form-control w-full"
                                placeholder="Enter About Us"
                            ></textarea>
                        </div>
                        <span
                            v-if="errors.about_us"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.about_us[0] }}</span
                        >
                    </div>
                </div>
                <div class="tw-form-group w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="website" class="tw-form-label"
                                >Website</label
                            >
                        </div>
                        <div class="w-full lg:w-3/4 my-2">
                            <input
                                type="text"
                                name="website"
                                v-model="website"
                                id="website"
                                class="tw-form-control w-full"
                                placeholder="Enter Website"
                            />
                        </div>
                        <span
                            v-if="errors.website"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.website[0] }}</span
                        >
                    </div>
                </div>
            </div>

            <portal-target name="submit-btn"></portal-target>
            <portal to="submit-btn">
                <div class="py-3">
                    <a
                        href="#"
                        class="btn btn-primary submit-btn"
                        @click="updateDetails()"
                        >Submit</a
                    >
                    <a
                        href="#"
                        class="btn btn-reset reset-btn"
                        @click="resetForm()"
                        >Reset</a
                    >
                    <input type="submit" class="hidden" id="submit-btn" />
                </div>
            </portal>
        </div>
    </div>
</template>

<script>
export default {
    props: ["url", "school_id"],

    data() {
        return {
            details: [],
            name: "",
            moto: "",
            date_of_establishment: "",
            board: "",
            school_logo: "",
            school_logo_display: "",
            about_us: "",
            country_id: 7,
            city_id: "",
            website: "",

            countrylist: [],
            citylist: [],
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
            errors: [],
            success: null,
        };
    },

    methods: {
        getDetails() {
            axios
                .get("/admin/schooldetails/edit/" + this.school_id)
                .then((response) => {
                    this.details = response.data.details;
                    this.setDetails();
                })
                .catch(() => {
                    console.error("Failed to load school details");
                });
        },

        setDetails() {
            if (Object.keys(this.details).length > 0) {
                this.name = this.details.name || "";
                this.moto = this.details.moto || "";
                this.date_of_establishment =
                    this.details.date_of_establishment || "";
                this.board = this.details.board || "";
                this.school_logo_display =
                    this.details.school_logo_display || "";
                this.about_us = this.details.about_us || "";
                this.country_id = this.details.country_id || 7;
                this.city_id = this.details.city_id || "";
                this.website = this.details.website || "";

                this.countrylist = this.details.countrylist || [];
                this.citylist = this.details.citylist || [];
            }
        },

        updateDetails() {
            this.errors = [];
            this.success = null;

            let formData = new FormData();
            formData.append("name", this.name);
            formData.append("moto", this.moto);
            formData.append(
                "date_of_establishment",
                this.date_of_establishment
            );
            formData.append("board", this.board);
            formData.append("school_logo", this.school_logo);
            formData.append("about_us", this.about_us);
            formData.append("country_id", this.country_id);
            formData.append("city_id", this.city_id);
            formData.append("website", this.website);

            axios
                .post(
                    "/admin/schooldetails/update/validationUpdate/" +
                        this.school_id,
                    formData
                )
                .then(() => {
                    $("#submit-btn").click();
                })
                .catch((error) => {
                    this.errors = error.response?.data?.errors || {};
                });
        },

        OnFileSelected(event) {
            this.school_logo = event.target.files[0];
        },

        resetForm() {
            this.name = "";
            this.moto = "";
            this.date_of_establishment = "";
            this.board = "";
            this.about_us = "";
            this.website = "";
            this.school_logo = "";
            this.city_id = "";
            this.errors = [];
            this.success = null;
            // Note: country_id remains default (7)
        },
    },

    created() {
        this.getDetails();
    },
};
</script>

<style scoped>
.tw-label {
    color: #3492e2;
}
.tw-label input[type="file"] {
    display: none;
}
</style>
