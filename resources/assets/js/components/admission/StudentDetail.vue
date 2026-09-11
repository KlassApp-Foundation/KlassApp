<template>
    <div class="bg-white shadow px-4 py-3" v-bind:class="[this.profile_tab == 2 ? 'block' :'hidden']">
        <div>
            <fieldset class="shadow">
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-2/3">
                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label">First Name<span class="text-red-500">*</span></label>
                                    <input type="text" name="name" v-model="name" placeholder="First Name" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.name" class="text-red-500 text-xs font-semibold">{{ errors.name[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="lastname" class="tw-form-label">Last Name</label>
                                    <input type="text" name="lastname" v-model="lastname" placeholder="Last Name" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.lastname" class="text-red-500 text-xs font-semibold">{{ errors.lastname[0] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="date_of_birth" class="tw-form-label">Date of birth<span class="text-red-500">*</span></label>
                                    <input type="date" name="date_of_birth" v-model="date_of_birth" placeholder="Enter Date Of Birth" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.date_of_birth" class="text-red-500 text-xs font-semibold">{{ errors.date_of_birth[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="gender" class="tw-form-label">Gender<span class="text-red-500">*</span></label>
                                    <div class="flex tw-form-control py-2 my-1">
                                        <div class="w-1/4 flex items-center mr-2 lg:mr-8 md:mr-8">
                                            <input type="radio" name="gender" v-model="gender" id="gender1" value="male"> 
                                            <span class="text-sm mx-2">Male</span>
                                        </div>
                                        <div class="w-1/4 flex items-center ">
                                            <input type="radio" name="gender" v-model="gender" id="gender2" value="female">
                                            <span class="text-sm mx-2">Female</span>
                                        </div>
                                    </div>
                                </div>
                                <span v-if="errors.gender" class="text-red-500 text-xs font-semibold">{{ errors.gender[0] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/3">
                        <div class="relative w-10/12 mx-auto my-2">
                            <label for="avatar" class="tw-form-label">Attach Photo</label>
                            <input type="file" name="avatar" @change="OnFileSelected" id="avatar" class="tw-form-control w-full">
                            <div class="" v-if="image != ''">
                                <img :src="image" style="width: 150px;height: 150px;">
                            </div>
                            <div class="" v-else>
                                <img id="blah" class="student-img text-sm border border-dashed border-gray-300 my-2" :src='url+"/uploads/user/avatar/default-user.jpg"' style="width: 150px;height: 150px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="">
                    <label for="identification_marks" class="tw-form-label">Identification Marks</label>
                    <div class="flex flex-col lg:flex-row">
                        <div class="my-1 w-full lg:w-1/2 lg:mr-2">
                            <input type="text" name="identification_marks" v-model="identification_marks" placeholder="Identification Marks" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.identification_marks" class="text-red-500 text-xs font-semibold">{{ errors.identification_marks[0] }}</span>
                        </div>
                        <div class="my-1 w-full lg:w-1/2 lg:mr-2">
                            <input type="text" name="identification_marks_1" placeholder="Identification Marks" v-model="identification_marks_1" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.identification_marks_1" class="text-red-500 text-xs font-semibold">{{ errors.identification_marks_1[0] }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="school_last_studied" class="tw-form-label">School last studied</label>
                            <input type="text" name="school_last_studied" v-model="school_last_studied" placeholder="School last studied" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.school_last_studied" class="text-red-500 text-xs font-semibold">{{ errors.school_last_studied[0] }}</span>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="reason_for_leaving" class="tw-form-label">Reason for leaving</label>
                            <input type="text" name="reason_for_leaving" v-model="reason_for_leaving" placeholder="Reason for leaving" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.reason_for_leaving" class="text-red-500 text-xs font-semibold">{{ errors.reason_for_leaving[0] }}</span>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">
                    <div class="my-1 w-full lg:w-1/2 lg:mr-2">
                        <label for="permanent_address" class="tw-form-label">Address : Permanent</label>
                        <textarea type="text" name="permanent_address" v-model="permanent_address" placeholder="Permanent Address" class="tw-form-control w-full my-1 py-2"></textarea>
                        <span v-if="errors.permanent_address" class="text-red-500 text-xs font-semibold">{{ errors.permanent_address[0] }}</span>
                    </div>
                    <div class="my-1 w-full lg:w-1/2 lg:mr-2">
                        <label for="address_for_communication" class="tw-form-label">Address : for communication</label>
                        <textarea type="text" name="address_for_communication" v-model="address_for_communication" placeholder="Communication Address" class="tw-form-control w-full my-1 py-2"></textarea>
                        <span v-if="errors.address_for_communication" class="text-red-500 text-xs font-semibold">{{ errors.address_for_communication[0] }}></span>
                    </div>
                </div>

                <div class="my-1">
                    <div class="flex flex-col lg:flex-row lg:items-center">
                        <label for="name" class="tw-form-label">Is the sibling studying in the same school:</label>
                        <div class="flex py-2 my-1 lg:mx-5">
                            <div class="w-1/4 flex items-center mr-2 lg:mr-8 md:mr-8">
                                <input type="radio" v-model="siblings" name="siblings" value="igotnone"> 
                                <span class="text-sm mx-2">Yes</span>
                            </div>
                            <div class="w-1/4 flex items-center">
                                <input type="radio" v-model="siblings" name="siblings" value="igottwo">
                                <span class="text-sm mx-2">No</span>
                            </div>
                        </div>
                        <span v-if="errors.siblings"><p class="text-red-500 text-xs font-semibold">{{errors.siblings[0]}}</p></span>
                    </div>
                    <div v-if="this.siblings=='igotnone'">
                        <input type="text" name="m1" placeholder="" class="tw-form-control w-full my-1 py-2">
                    </div>
                </div>
    
                <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="previousForm('1')">Previous</a>
                <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="submitForm('3')">Next</a>
            </fieldset>
        </div>
    </div>
</template>

<script>
    import { bus } from "../../event-bus";
    import PortalVue from "portal-vue";
    export default {
        props:['url','slug'],
        data(){
            return{
                profile_tab:'',
                name:'',
                lastname:'',
                date_of_birth:'',
                gender:'',
                identification_marks:'',
                identification_marks_1:'',
                school_last_studied:'',
                reason_for_leaving:'',
                permanent_address:'',
                address_for_communication:'',
                siblings:'',
                avatar:'',
                image:'',
                standard_id:'',
                errors:[],
                success:null,
            }
        },
        
        methods:
        {
            OnFileSelected(event)
            {
                this.avatar=event.target.files[0];
                let files = event.target.files || event.dataTransfer.files;
                if (!files.length)
                return;
                this.createImage(files[0]);
            },

            createImage(file) 
            {
                let reader = new FileReader();
                let vm = this;
                reader.onload = (e) => {
                    vm.image = e.target.result;
                };
                reader.readAsDataURL(file);
            },
     
            submitForm(val)
            {
                this.errors=[];
                this.success=null; 

                let formData = new FormData(); 

                formData.append('name',this.name);          
                formData.append('lastname',this.lastname);          
                formData.append('date_of_birth',this.date_of_birth);          
                formData.append('gender',this.gender);          
                formData.append('avatar',this.avatar);          
                formData.append('identification_marks',this.identification_marks);          
                formData.append('school_last_studied',this.school_last_studied);          
                formData.append('reason_for_leaving',this.reason_for_leaving);          
                formData.append('permanent_address',this.permanent_address);          
                formData.append('address_for_communication',this.address_for_communication);          
                formData.append('siblings',this.siblings); 
                formData.append('standard_id',this.standard_id);          
       
                axios.post(this.url+'/'+this.slug+'/admission-form/validationStudentDetail',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.setProfileTab(val); 
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            previousForm(val)
            {
                this.setProfileTab(val); 
            },

            setProfileTab(val)
            {
                this.profile_tab=val;
                bus.$emit("dataAdmissionTab", this.profile_tab);
                bus.$emit("standardVal", this.standard_id);
            },
        },

        created()
        {
            bus.$on("dataAdmissionTab", data => {
                if(data!='')
                {
                    this.profile_tab=data;                   
                }
            });   
            bus.$on("standardVal", data => {
                if(data!='')
                {
                    this.standard_id=data;                   
                }
            });   
        }
    }
</script>