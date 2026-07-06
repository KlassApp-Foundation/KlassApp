<!-- <FILE file_path="/home/workdir/attachments/pasted-text.txt"> -->
<template>
    <div class="bg-white shadow px-4 py-3">
        <div
            v-if="this.success != null"
            class="alert alert-success"
            id="success-alert"
        >
            {{ this.success }}
        </div>
        <h1>Personal Details</h1>
        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="firstname" class="tw-form-label"
                            >First Name<span class="text-red-500"
                                >*</span
                            ></label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            class="tw-form-control w-full"
                            id="firstname"
                            v-model="firstname"
                            name="firstname"
                            Placeholder="First Name"
                        />
                    </div>
                    <span
                        v-if="errors.firstname"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.firstname[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="lastname" class="tw-form-label"
                            >Last Name
                        </label>
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="lastname"
                            name="lastname"
                            id="lastname"
                            class="tw-form-control w-full"
                            Placeholder="Last Name"
                        />
                    </div>
                    <span
                        v-if="errors.lastname"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.lastname[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="date_of_birth" class="tw-form-label"
                            >Date Of Birth<span class="text-red-500"
                                >*</span
                            ></label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="date"
                            v-model="date_of_birth"
                            name="date_of_birth"
                            id="date_of_birth"
                            class="tw-form-control w-full"
                        />
                    </div>
                    <span
                        v-if="errors.date_of_birth"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.date_of_birth[0] }}</span
                    >
                </div>
            </div>
        </div>

        <!-- Mobile Number - Commented Out -->
        <!-- 
        <div class="tw-form-group w-full lg:w-1/2">
            <div class="lg:mr-8 md:mr-8">
                <div class="mb-2">
                    <label for="mobile_no" class="tw-form-label"
                        >Mobile Number</label
                    >
                </div>
                <div class="mb-2">
                    <input
                        type="text"
                        v-model="mobile_no"
                        name="mobile_no"
                        id="mobile_no"
                        class="tw-form-control w-full"
                        placeholder="Mobile Number"
                    />
                </div>
                <span
                    v-if="errors.mobile_no"
                    class="text-red-500 text-xs font-semibold"
                    >{{ errors.mobile_no[0] }}</span
                >
            </div>
        </div>
        -->

        <div class="tw-form-group w-full lg:w-1/2">
            <div class="lg:mr-8 md:mr-8">
                <div class="mb-2">
                    <label for="email" class="tw-form-label">Email ID</label>
                </div>
                <div class="mb-2">
                    <input
                        type="text"
                        v-model="email"
                        name="email"
                        id="email"
                        class="tw-form-control w-full"
                        placeholder="Email ID"
                    />
                </div>
                <span
                    v-if="errors.email"
                    class="text-red-500 text-xs font-semibold"
                    >{{ errors.email[0] }}</span
                >
            </div>
        </div>

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/5">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="gender" class="tw-form-label"
                            >Gender<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="flex tw-form-control">
                        <div
                            class="w-1/2 flex items-center mr-2 lg:mr-8 md:mr-8"
                        >
                            <input
                                type="radio"
                                v-model="gender"
                                name="gender"
                                id="gender1"
                                value="male"
                            />
                            <span class="text-sm mx-1">Male</span>
                        </div>
                        <div class="w-1/2 flex items-center">
                            <input
                                type="radio"
                                v-model="gender"
                                name="gender"
                                id="gender2"
                                value="female"
                            />
                            <span class="text-sm mx-1">Female</span>
                        </div>
                    </div>
                    <span
                        v-if="errors.gender"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.gender[0] }}</span
                    >
                </div>
            </div>

        <portal-target name="address"></portal-target>

        <div class="tw-form-group">
            <div class="flex flex-col lg:flex-row">
                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="country" class="tw-form-label"
                            >Country<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            id="country_id"
                            v-model="country_id"
                            name="country_id"
                        >
                            <option value="" disabled>Select Country</option>
                            <option
                                v-for="country in countrylist"
                                v-bind:value="country.id"
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

                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="state" class="tw-form-label"
                            >Region<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            id="state_id"
                            v-model="state_id"
                            name="state_id"
                        >
                            <option value="" disabled>Select Region</option>
                            <option
                                v-for="state in statelist[this.country_id]"
                                v-bind:value="state.id"
                            >
                                {{ state.name }}
                            </option>
                        </select>
                    </div>
                    <span
                        v-if="errors.state_id"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.state_id[0] }}</span
                    >
                </div>

                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="city" class="tw-form-label"
                            >City<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            id="city_id"
                            v-model="city_id"
                            name="city_id"
                        >
                            <option value="" disabled>Select District</option>
                            <option
                                v-for="city in citylist[this.state_id]"
                                v-bind:value="city.id"
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

        <!-- Admission Number - Commented Out -->
        <!-- 
        <div class="tw-form-group w-full lg:w-1/3">
            <div class="lg:mr-8 md:mr-8">
                <div class="mb-2">
                    <label for="registration_number" class="tw-form-label">
                        Admission Number
                    </label>
                </div>
                <div class="mb-2">
                    <input
                        type="text"
                        v-model="registration_number"
                        name="registration_number"
                        id="registration_number"
                        class="tw-form-control w-full"
                        placeholder="Admission Number"
                    />
                </div>
                <span
                    v-if="errors.registration_number"
                    class="text-red-500 text-xs font-semibold"
                    >{{ errors.registration_number[0] }}</span
                >
            </div>
        </div>
        -->

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="LIN" class="tw-form-label">LIN</label>
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="lin"
                            name="lin"
                            id="lin"
                            class="tw-form-control w-full"
                            placeholder="lin"
                        />
                    </div>
                    <span
                        v-if="errors.lin"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.lin[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="joining_date" class="tw-form-label"
                            >Joining Date<span class="text-red-500"
                                >*</span
                            ></label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="date"
                            name="joining_date"
                            v-model="joining_date"
                            id="joining_date"
                            class="tw-form-control w-full"
                        />
                    </div>
                    <span
                        v-if="errors.joining_date"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.joining_date[0] }}</span
                    >
                </div>
            </div>
        </div>

        <!-- The rest of your form (Academic Details, Siblings, etc.) continues below -->
        <!-- I have kept all other sections intact as per original -->

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="standard" class="tw-form-label"
                            >Class<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            id="standard"
                            v-model="standard"
                            name="standard"
                        >
                            <option value="" disabled>Select Class</option>
                            <option
                                value=""
                                v-for="standardLink in standardLinklist"
                                v-bind:value="standardLink.id"
                            >
                                {{ standardLink.standard_section }}
                            </option>
                        </select>
                    </div>
                    <span
                        v-if="errors.standard"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.standard[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="std_school_pay_number" class="tw-form-label"
                            >Student School Pay Number
                        </label>
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="std_school_pay_number"
                            name="std_school_pay_number"
                            id="std_school_pay_number"
                            class="tw-form-control w-full"
                            placeholder="Student School Pay Number"
                        />
                    </div>
                    <span
                        v-if="errors.std_school_pay_number"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.std_school_pay_number[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="id_card_number" class="tw-form-label"
                            >ID Card Number</label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="id_card_number"
                            name="id_card_number"
                            id="id_card_number"
                            class="tw-form-control w-full"
                            placeholder="ID Card Number"
                        />
                    </div>
                    <span
                        v-if="errors.id_card_number"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.id_card_number[0] }}</span
                    >
                </div>
            </div>
        </div>

        <portal-target name="submit-btn"></portal-target>
        <portal to="submit-btn">
            <div class="my-6">
                <a
                    href="#"
                    dusk="submit-btn"
                    class="btn btn-primary submit-btn"
                    @click="submitForm()"
                    >Submit</a
                >
                <a href="#" class="btn btn-reset reset-btn" @click="resetForm()"
                    >Reset</a
                >
                <input type="submit" class="hidden" id="submit-btn" />
            </div>
        </portal>
    </div>
</template>

<script>
import PortalVue from "portal-vue";
export default {
    props: ["url"],

    data() {
        return {
            user: [],
            academic_year_id: "",
            firstname: "",
            lastname: "",
            // mobile_no: "",           // commented
            email: "",
            gender: "",
            date_of_birth: "",
            blood_group: "",
            standard: "",
            city_id: "",
            state_id: "",
            country_id: 7,
            pincode: "",
            birth_place: "",
            native_place: "",
            // mother_tongue: "",       // commented
            caste: "",
            sub_caste: "",
            aadhar_number: "",
            joining_date: "",
            // registration_number: "", // commented
            lin: "",
            std_school_pay_number: "",
            id_card_number: "",
            board_registration_number: "",
            mode_of_transport: "",
            driver_name: "",
            driver_contact_number: "",
            siblings: "",
            siblings_count: "",
            notes: "",
            avatar: "",
            countrylist: [],
            statelist: [],
            citylist: [],
            standardLinklist: [],
            inputs: [
                {
                    sibling_relation: "",
                    sibling_name: "",
                    sibling_date_of_birth: "",
                    sibling_standard: "",
                },
            ],
            blood_groups: [],
            castelist: [],
            transportlist: [],
            lists: ["auto", "rickshaw", "taxi"],
            siblinglist: [
                { id: "brother", name: "Brother" },
                { id: "sister", name: "Sister" },
            ],
            errors: [],
            success: null,
        };
    },
    // ... rest of your script methods remain same
};
</script>
<!-- </FILE> -->
