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
        <div class="my-6">
            <div class="flex items-center">
                <img
                    :src="this.avatar_display"
                    class="img-responsive w-12 h-12 rounded-full"
                />
                <div class="mx-2">
                    <h2 class="font-semibold text-sm text-gray-700">
                        {{ user.firstname }} {{ user.lastname }}
                    </h2>
                    <label
                        class="tw-label cursor-pointer text-xs text-gray-600"
                    >
                        Change Avatar
                        <input
                            type="file"
                            size="60"
                            name="avatar"
                            id="avatar"
                            @change="OnFileSelected"
                        />
                        <span
                            v-if="errors.avatar"
                            class="text-red-500 text-xs font-semibold"
                            >{{ errors.avatar[0] }}</span
                        >
                    </label>
                </div>
            </div>
        </div>
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

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="gender" class="tw-form-label"
                            >Gender<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="flex">
                        <div
                            class="w-1/2 flex items-center tw-form-control mr-2 lg:mr-8 md:mr-8"
                        >
                            <input
                                type="radio"
                                v-model="gender"
                                name="gender"
                                id="gender1"
                                value="male"
                            />
                            <span class="text-sm mx-1">Boy</span>
                        </div>
                        <div
                            class="w-1/2 flex items-center tw-form-control lg:mr-8 md:mr-8"
                        >
                            <input
                                type="radio"
                                v-model="gender"
                                name="gender"
                                id="gender2"
                                value="female"
                            />
                            <span class="text-sm mx-1">Girl</span>
                        </div>
                    </div>
                    <span
                        v-if="errors.gender"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.gender[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="blood_group" class="tw-form-label">
                            Blood Group
                            <!-- <span class="text-red-500" >*</span> -->
                            </label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            id="blood_group"
                            v-model="blood_group"
                            name="blood_group"
                        >
                            <option
                                value=""
                                v-for="blood_group in blood_groups"
                                v-bind:value="blood_group.num"
                            >
                                {{ blood_group.name }}
                            </option>
                        </select>
                    </div>
                    <span
                        v-if="errors.blood_group"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.blood_group[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="aadhar_number" class="tw-form-label"
                            >Aadhaar Number</label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            class="tw-form-control w-full"
                            id="aadhar_number"
                            v-model="aadhar_number"
                            name="aadhar_number"
                            Placeholder="Aadhar Number"
                        />
                    </div>
                    <span
                        v-if="errors.aadhar_number"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.aadhar_number[0] }}</span
                    >
                </div>
            </div>
        </div>

        <portal-target name="edit_address"></portal-target>

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
                            >State<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            id="state_id"
                            v-model="state_id"
                            name="state_id"
                        >
                            <option value="" disabled>Select State</option>
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
                            <option value="" disabled>Select City</option>
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

                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="pincode" class="tw-form-label"
                            >Pincode</label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            class="tw-form-control w-full"
                            v-model="pincode"
                            name="pincode"
                            id="pincode"
                            placeholder="Enter Pincode"
                        />
                    </div>
                    <span
                        v-if="errors.pincode"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.pincode[0] }}</span
                    >
                </div>
            </div>
        </div>

        <div class="tw-form-group">
            <div class="flex flex-col lg:flex-row">
                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="birth_place" class="tw-form-label"
                            >Birth Place</label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            class="tw-form-control w-full"
                            id="birth_place"
                            v-model="birth_place"
                            name="birth_place"
                            placeholder="Birth Place"
                        />
                    </div>
                    <span
                        v-if="errors.birth_place"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.birth_place[0] }}</span
                    >
                </div>

                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="native_place" class="tw-form-label"
                            >Native Place</label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            class="tw-form-control w-full"
                            id="native_place"
                            v-model="native_place"
                            name="native_place"
                            placeholder="Native Place"
                        />
                    </div>
                    <span
                        v-if="errors.native_place"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.native_place[0] }}</span
                    >
                </div>

                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="mother_tongue" class="tw-form-label"
                            >Mother Tongue<span class="text-red-500"
                                >*</span
                            ></label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            class="tw-form-control w-full"
                            id="mother_tongue"
                            v-model="mother_tongue"
                            name="mother_tongue"
                            placeholder="Mother Tongue"
                        />
                    </div>
                    <span
                        v-if="errors.mother_tongue"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.mother_tongue[0] }}</span
                    >
                </div>

                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8 hidden">
                    <div class="mb-2">
                        <label for="caste" class="tw-form-label"
                            >Caste<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            v-model="caste"
                            name="caste"
                            id="caste"
                        >
                            <option value="Others" disabled>
                                Select Caste
                            </option>
                            <option
                                v-for="castes in castelist"
                                v-bind:value="castes.id"
                            >
                                {{ castes.name }}
                            </option>
                        </select>
                    </div>
                    <span
                        v-if="errors.caste"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.caste[0] }}</span
                    >
                </div>

                <div class="w-full lg:w-1/4 lg:mr-8 md:mr-8 hidden">
                    <div class="mb-2">
                        <label for="sub_caste" class="tw-form-label"
                            >Sub Caste<span class="text-red-500"></span
                        ></label>
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            class="tw-form-control w-full"
                            id="sub_caste"
                            v-model="sub_caste"
                            name="sub_caste"
                            placeholder="Sub Caste"
                        />
                    </div>
                    <span
                        v-if="errors.sub_caste"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.sub_caste[0] }}</span
                    >
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="mode_of_transport" class="tw-form-label"
                            >Mode Of Transport</label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            id="mode_of_transport"
                            v-model="mode_of_transport"
                            name="mode_of_transport"
                        >
                            <option value="" disabled>Select Transport</option>
                            <option
                                value=""
                                v-for="transport in transportlist"
                                v-bind:value="transport.id"
                            >
                                {{ transport.name }}
                            </option>
                        </select>
                    </div>
                    <span
                        v-if="errors.mode_of_transport"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.mode_of_transport[0] }}</span
                    >
                </div>
            </div>

            <div
                class="tw-form-group w-full lg:w-1/2"
                v-if="checkInArray(this.lists, this.mode_of_transport)"
            >
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label for="driver_name" class="tw-form-label"
                                    >Driver Name<span class="text-red-500"
                                        >*</span
                                    ></label
                                >
                            </div>
                            <div class="mb-2">
                                <input
                                    type="text"
                                    v-model="driver_name"
                                    name="driver_name"
                                    id="driver_name"
                                    class="tw-form-control w-full"
                                    placeholder="Driver Name"
                                />
                            </div>
                            <span
                                v-if="errors.driver_name"
                                class="text-red-500 text-xs font-semibold"
                                >{{ errors.driver_name[0] }}</span
                            >
                        </div>
                    </div>

                    <div class="w-full lg:w-1/2">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label
                                    for="driver_contact_number"
                                    class="tw-form-label"
                                    >Driver Contact Number<span
                                        class="text-red-500"
                                        >*</span
                                    ></label
                                >
                            </div>
                            <div class="mb-2">
                                <input
                                    type="text"
                                    v-model="driver_contact_number"
                                    name="driver_contact_number"
                                    id="driver_contact_number"
                                    class="tw-form-control w-full"
                                    placeholder="Driver Contact Number"
                                />
                            </div>
                            <span
                                v-if="errors.driver_contact_number"
                                class="text-red-500 text-xs font-semibold"
                                >{{ errors.driver_contact_number[0] }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/6">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="siblings" class="tw-form-label"
                            >Siblings<span class="text-red-500">*</span></label
                        >
                    </div>
                    <div class="mb-2">
                        <select
                            class="tw-form-control w-full"
                            id="siblings"
                            v-model="siblings"
                            name="siblings"
                        >
                            <option value="" disabled>Select Sibling</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <span
                        v-if="errors.siblings"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.siblings[0] }}</span
                    >
                </div>
            </div>

            <div
                class="tw-form-group w-full lg:w-1/6"
                v-if="this.siblings == 'yes'"
            >
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="siblings" class="tw-form-label"
                            >Siblings Count<span class="text-red-500"
                                >*</span
                            ></label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            class="tw-form-control w-full"
                            id="siblings_count"
                            v-model="siblings_count"
                            name="siblings_count"
                            placeholder="Siblings Count"
                        />
                    </div>
                    <span
                        v-if="errors.siblings_count"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.siblings_count[0] }}</span
                    >
                </div>
            </div>

            <div
                class="tw-form-group w-full lg:w-4/6"
                v-if="this.siblings == 'yes'"
            >
                <div
                    class="flex flex-col lg:flex-row"
                    v-for="(input, index) in inputs"
                >
                    <div class="w-full lg:w-1/4">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label
                                    for="sibling_relation"
                                    class="tw-form-label"
                                    >Sibling Relation<span class="text-red-500"
                                        >*</span
                                    ></label
                                >
                            </div>
                            <div class="mb-2">
                                <select
                                    v-model="input.sibling_relation"
                                    name="sibling_relation[]"
                                    id="sibling_relation"
                                    class="tw-form-control w-full"
                                >
                                    <option value="" disabled>
                                        Select Relation
                                    </option>
                                    <option
                                        v-for="sibling in siblinglist"
                                        v-bind:value="sibling.id"
                                    >
                                        {{ sibling.name }}
                                    </option>
                                </select>
                            </div>
                            <span
                                v-if="errors['sibling_relation' + index]"
                                class="text-red-500 text-xs font-semibold"
                                >{{ errors["sibling_relation" + index] }}</span
                            >
                        </div>
                    </div>

                    <div class="w-full lg:w-1/4">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label for="sibling_name" class="tw-form-label"
                                    >Sibling Name<span class="text-red-500"
                                        >*</span
                                    ></label
                                >
                            </div>
                            <div class="mb-2">
                                <input
                                    type="text"
                                    v-model="input.sibling_name"
                                    name="sibling_name[]"
                                    id="sibling_name"
                                    class="tw-form-control w-full"
                                    placeholder="Sibling Name"
                                />
                            </div>
                            <span
                                v-if="errors['sibling_name' + index]"
                                class="text-red-500 text-xs font-semibold"
                                >{{ errors["sibling_name" + index] }}</span
                            >
                        </div>
                    </div>

                    <div class="w-full lg:w-1/4">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label
                                    for="sibling_date_of_birth"
                                    class="tw-form-label"
                                    >Sibling Date Of Birth<span
                                        class="text-red-500"
                                        >*</span
                                    ></label
                                >
                            </div>
                            <div class="mb-2">
                                <input
                                    type="date"
                                    v-model="input.sibling_date_of_birth"
                                    name="sibling_date_of_birth[]"
                                    id="sibling_date_of_birth"
                                    class="tw-form-control w-full"
                                />
                            </div>
                            <span
                                v-if="errors['sibling_date_of_birth' + index]"
                                class="text-red-500 text-xs font-semibold"
                                >{{
                                    errors["sibling_date_of_birth" + index]
                                }}</span
                            >
                        </div>
                    </div>

                    <div class="w-full lg:w-1/4">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label
                                    for="sibling_standard"
                                    class="tw-form-label"
                                    >Sibling Class</label
                                >
                            </div>
                            <div class="mb-2">
                                <select
                                    class="tw-form-control w-full"
                                    id="sibling_standard"
                                    v-model="input.sibling_standard"
                                    name="sibling_standard[]"
                                >
                                    <option value="" disabled>
                                        Select Class
                                    </option>
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
                                v-if="errors['sibling_standard' + index]"
                                class="text-red-500 text-xs font-semibold"
                                >{{ errors["sibling_standard" + index] }}</span
                            >
                        </div>
                    </div>
                    <a href="#" class="py-8" @click="deleteRow(index)">
                        <svg
                            version="1.1"
                            id="Capa_1"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink"
                            x="0px"
                            y="0px"
                            viewBox="0 0 512 512"
                            xml:space="preserve"
                            class="w-4 h-4 fill-current text-black-600"
                        >
                            <g>
                                <g>
                                    <g>
                                        <polygon
                                            points="353.574,176.526 313.496,175.056 304.807,412.34 344.885,413.804"
                                        ></polygon>
                                        <rect
                                            x="235.948"
                                            y="175.791"
                                            width="40.104"
                                            height="237.285"
                                        ></rect>
                                        <polygon
                                            points="207.186,412.334 198.497,175.049 158.419,176.52 167.109,413.804"
                                        ></polygon>
                                        <path
                                            d="M17.379,76.867v40.104h41.789L92.32,493.706C93.229,504.059,101.899,512,112.292,512h286.74 c10.394,0,19.07-7.947,19.972-18.301l33.153-376.728h42.464V76.867H17.379z M380.665,471.896H130.654L99.426,116.971h312.474 L380.665,471.896z"
                                        ></path>
                                    </g>
                                </g>
                            </g>
                            <g>
                                <g>
                                    <path
                                        d="M321.504,0H190.496c-18.428,0-33.42,14.992-33.42,33.42v63.499h40.104V40.104h117.64v56.815h40.104V33.42 C354.924,14.992,339.932,0,321.504,0z"
                                    ></path>
                                </g>
                            </g>
                        </svg>
                    </a>
                </div>

                <div class="w-full lg:w-1/4">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="add_siblings" class="tw-form-label"
                                >Add Sibling</label
                            >
                        </div>
                        <div class="mb-2">
                            <a
                                href="#"
                                class="tw-form-control w-full"
                                @click="addRow"
                                >+
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="this.inputs != null">
            <input
                type="hidden"
                name="count"
                v-bind:value="this.inputs.length"
            />
        </div>

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="notes" class="tw-form-label">Notes</label>
                    </div>
                    <div class="mb-2">
                        <textarea
                            type="text"
                            class="tw-form-control w-full"
                            v-model="notes"
                            id="notes"
                            name="notes"
                            rows="3"
                        ></textarea>
                    </div>
                    <span
                        v-if="errors.notes"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.notes[0] }}</span
                    >
                </div>
            </div>
        </div>
        <hr style="border-width: 1px" />
        <h1>Academic Details</h1>
        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="registration_number" class="tw-form-label">
                            Admission Number
                            <!-- <span class="text-red-500">*</span> -->
                            </label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="registration_number"
                            name="registration_number"
                            id="registration_number"
                            class="tw-form-control w-full"
                            placeholder="Registration Number"
                        />
                    </div>
                    <span
                        v-if="errors.registration_number"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.registration_number[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="EMIS_number" class="tw-form-label"
                            >EMIS Number</label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="EMIS_number"
                            name="EMIS_number"
                            id="EMIS_number"
                            class="tw-form-control w-full"
                            placeholder="EMIS Number"
                        />
                    </div>
                    <span
                        v-if="errors.EMIS_number"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.EMIS_number[0] }}</span
                    >
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="joining_date" class="tw-form-label"
                            >Joining date<span class="text-red-500"
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
                        <label for="roll_number" class="tw-form-label"
                            >Roll Number<span class="text-red-500"
                                >*</span
                            ></label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="roll_number"
                            name="roll_number"
                            id="roll_number"
                            class="tw-form-control w-full"
                            placeholder="Roll Number"
                        />
                    </div>
                    <span
                        v-if="errors.roll_number"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.roll_number[0] }}</span
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

            <div class="tw-form-group w-full lg:w-1/3 hidden">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label
                            for="board_registration_number"
                            class="tw-form-label whitespace-no-wrap"
                            >Board Registration Number<span
                                class="text-red-500 whitespace-no-wrap"
                                >*Only For Class X , XI , XII</span
                            ></label
                        >
                    </div>
                    <div class="mb-2">
                        <input
                            type="text"
                            v-model="board_registration_number"
                            name="board_registration_number"
                            id="board_registration_number"
                            class="tw-form-control w-full"
                            placeholder="Board Registration Number"
                        />
                    </div>
                    <span
                        v-if="errors.board_registration_number"
                        class="text-red-500 text-xs font-semibold"
                        >{{ errors.board_registration_number[0] }}</span
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
    props: ["url", "student_name"],

    data() {
        return {
            user: [],
            firstname: "",
            lastname: "",
            gender: "",
            date_of_birth: "",
            blood_group: "",
            standard: "",
            standard_name: "",
            section_name: "",
            city_id: "",
            state_id: "",
            country_id: 7,
            pincode: "",
            birth_place: "",
            native_place: "",
            mother_tongue: "",
            caste: "",
            sub_caste: "",
            aadhar_number: "",
            joining_date: "",
            registration_number: "",
            EMIS_number: "",
            roll_number: "",
            id_card_number: "",
            board_registration_number: "",
            mode_of_transport: "",
            driver_name: "",
            driver_contact_number: "",
            siblings: "",
            siblings_count: "",
            sibling_relation: "",
            sibling_name: "",
            sibling_date_of_birth: "",
            sibling_standard: "",
            notes: "",
            avatar: "",
            avatar_display: "",
            today: "",
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
    methods: {
        getData() {
            axios
                .get("/admin/student/editStudent/" + this.student_name)
                .then((response) => {
                    this.user = response.data;
                    //console.log(this.user)
                    this.setData();
                });
        },

        setData() {
            if (Object.keys(this.user).length > 0) {
                this.firstname = this.user.firstname;
                this.lastname = this.user.lastname;
                this.date_of_birth = this.user.date_of_birth;
                this.gender = this.user.gender;
                this.blood_group = this.user.blood_group;
                this.aadhar_number = this.user.aadhar_number;
                this.city_id = this.user.city_id;
                this.state_id = this.user.state_id;
                this.country_id = this.user.country_id;
                this.pincode = this.user.pincode;
                this.birth_place = this.user.birth_place;
                this.native_place = this.user.native_place;
                this.mother_tongue = this.user.mother_tongue;
                this.caste = this.user.caste;
                this.sub_caste = this.user.sub_caste;
                this.avatar_display = this.user.avatar;
                this.notes = this.user.notes;
                this.registration_number = this.user.registration_number;
                this.EMIS_number = this.user.EMIS_number;
                this.joining_date = this.user.joining_date;

                this.standard = this.user.standardLink_id;
                this.roll_number = this.user.roll_number;
                this.id_card_number = this.user.id_card_number;
                this.board_registration_number =
                    this.user.board_registration_number;
                this.mode_of_transport = this.user.mode_of_transport;
                this.driver_name = this.user.driver_name;
                this.driver_contact_number = this.user.driver_contact_number;
                this.siblings = this.user.siblings;
                this.siblings_count = this.user.siblings_count;
                if (this.user.siblings_count != null) {
                    this.inputs = this.user.sibling_details;
                }

                this.countrylist = this.user.countrylist;
                this.statelist = this.user.statelist;
                this.citylist = this.user.citylist;
                this.standardLinklist = this.user.standardLinklist;
                this.blood_groups = this.user.blood_groups;
                this.castelist = this.user.castelist;
                this.transportlist = this.user.transportlist;
                this.today = this.user.today;
            }
        },

        submitForm() {
            this.errors = [];
            this.success = null;

            let formData = new FormData();

            formData.append("firstname", this.firstname);
            formData.append("lastname", this.lastname);
            formData.append("gender", this.gender);
            formData.append("date_of_birth", this.date_of_birth);
            formData.append("blood_group", this.blood_group);
            formData.append("standard", this.standard);
            formData.append("city_id", this.city_id);
            formData.append("state_id", this.state_id);
            formData.append("country_id", this.country_id);
            formData.append("pincode", this.pincode);
            formData.append("birth_place", this.birth_place);
            formData.append("native_place", this.native_place);
            formData.append("mother_tongue", this.mother_tongue);
            formData.append("caste", this.caste);
            formData.append("sub_caste", this.sub_caste);
            formData.append("aadhar_number", this.aadhar_number);
            formData.append("joining_date", this.joining_date);
            formData.append("registration_number", this.registration_number);
            formData.append("EMIS_number", this.EMIS_number);
            formData.append("roll_number", this.roll_number);
            formData.append("id_card_number", this.id_card_number);
            formData.append(
                "board_registration_number",
                this.board_registration_number
            );
            formData.append("mode_of_transport", this.mode_of_transport);
            formData.append("driver_name", this.driver_name);
            formData.append(
                "driver_contact_number",
                this.driver_contact_number
            );
            formData.append("siblings", this.siblings);
            formData.append("siblings_count", this.siblings_count);
            formData.append("notes", this.notes);
            formData.append("avatar", this.avatar);

            if (this.siblings == "yes") {
                for (let i = 0; i < this.inputs.length; i++) {
                    if (
                        typeof this.inputs[i]["sibling_relation"] !==
                        "undefined"
                    ) {
                        formData.append(
                            "sibling_relation" + i,
                            this.inputs[i]["sibling_relation"]
                        );
                    } else {
                        formData.append("sibling_relation" + i, "");
                    }

                    if (typeof this.inputs[i]["sibling_name"] !== "undefined") {
                        formData.append(
                            "sibling_name" + i,
                            this.inputs[i]["sibling_name"]
                        );
                    } else {
                        formData.append("sibling_name" + i, "");
                    }

                    if (
                        typeof this.inputs[i]["sibling_date_of_birth"] !==
                        "undefined"
                    ) {
                        formData.append(
                            "sibling_date_of_birth" + i,
                            this.inputs[i]["sibling_date_of_birth"]
                        );
                    } else {
                        formData.append("sibling_date_of_birth" + i, "");
                    }

                    if (
                        typeof this.inputs[i]["sibling_standard"] !==
                        "undefined"
                    ) {
                        formData.append(
                            "sibling_standard" + i,
                            this.inputs[i]["sibling_standard"]
                        );
                    } else {
                        formData.append("sibling_standard" + i, "");
                    }
                }
            }

            axios
                .post(
                    "/admin/student/edit/validationUser/" + this.student_name,
                    formData,
                    { headers: { "Content-Type": "multipart/form-data" } }
                )
                .then((response) => {
                    $("#submit-btn").click();
                })
                .catch((error) => {
                    this.errors = error.response.data.errors;
                });
        },

        OnFileSelected(event) {
            this.avatar = event.target.files[0];
        },

        checkInArray(array, value) {
            if (array.includes(value)) {
                return true;
            }
        },

        addRow() {
            this.inputs.push({
                sibling_relation: "",
                sibling_name: "",
                sibling_date_of_birth: this.today,
                sibling_standard: "",
            });
        },

        deleteRow(index) {
            this.inputs.splice(index, 1);
        },
    },

    created() {
        this.getData();
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
