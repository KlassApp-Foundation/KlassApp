<template>
    <div class="bg-white shadow px-4 py-3" v-bind:class="[this.profile_tab==4?'block' :'hidden']">
        <div>
            <fieldset class="shadow">
                <h2 class="text-lg my-2">Father's Detail</h2>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-2/3">
                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="father_name" class="tw-form-label">Father Name<span class="text-red-500">*</span></label>
                                    <input type="text" v-model="father_name" name="father_name" placeholder="Father Name" class="tw-form-control w-full my-1 py-2">
                                    <span v-if="errors.father_name" class="text-red-500 text-xs font-semibold">{{ errors.father_name[0] }}</span>
                                </div>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="father_qualification_id" class="tw-form-label">Qualification<span class="text-red-500">*</span></label>
                                    <select class="tw-form-control w-full" id="father_qualification_id" v-model="father_qualification_id" name="father_qualification_id">
                                        <option value="" disabled>Select Qualification</option>
                                        <option v-for="qualifications in qualificationlist" v-bind:value="qualifications.id">{{ qualifications.display_name }}</option>
                                    </select>
                                </div>
                                <span v-if="errors.father_qualification_id" class="text-red-500 text-xs font-semibold">{{ errors.father_qualification_id[0] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="father_occupation" class="tw-form-label">Occupation<span class="text-red-500">*</span></label>
                                    <input type="text" v-model="father_occupation" name="father_occupation" placeholder="Occupation" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.father_occupation" class="text-red-500 text-xs font-semibold">{{ errors.father_occupation[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="father_designation" class="tw-form-label">Designation<span class="text-red-500">*</span></label>
                                    <input type="text" v-model="father_designation" name="father_designation" placeholder="Designation" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.father_designation" class="text-red-500 text-xs font-semibold">{{ errors.father_designation[0] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="father_organization" class="tw-form-label">Organization<span class="text-red-500">*</span></label>
                                    <input type="text" name="father_organization" v-model="father_organisation" placeholder="Organization" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.father_organisation" class="text-red-500 text-xs font-semibold">{{ errors.father_organisation[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="father_income" class="tw-form-label">Income (P.A)<span class="text-red-500">*</span></label>
                                    <input type="text" name="father_income" v-model="father_income" placeholder="Income" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.father_income" class="text-red-500 text-xs font-semibold">{{ errors.father_income[0] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/3">
                        <div class="relative w-10/12 mx-auto my-2">
                            <label for="father_avatar" class="tw-form-label">Attach Photo</label>
                            <input type="file" name="father_avatar" @change="OnFileSelected($event,'father')" id="father_avatar" class="tw-form-control w-full">
                            <div class="" v-if="father_image != ''">
                                <img :src="father_image" style="width: 150px;height: 150px;">
                            </div>
                            <div class="" v-else>
                                <img id="blah1" class="student-img text-sm border border-dashed border-gray-300 my-2" :src='url+"/uploads/user/avatar/default-user.jpg"' style="width: 150px;height: 150px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="father_mobile_no" class="tw-form-label">Mobile Number<span class="text-red-500">*</span></label>
                            <input type="text" v-model="father_mobile_no" name="father_mobile_no" placeholder="Mobile Number" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.father_mobile_no" class="text-red-500 text-xs font-semibold">{{ errors.father_mobile_no[0] }}</span>
                    </div>
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="father_email" class="tw-form-label">Email ID</label>
                            <input type="text" name="father_email" v-model="father_email" placeholder="Email ID" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.father_email" class="text-red-500 text-xs font-semibold">{{ errors.father_email[0] }}</span>
                    </div>
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="father_aadhar_number" class="tw-form-label">Aadhaar ID</label>
                            <input type="text" name="father_aadhar_number" v-model="father_aadhar_number" placeholder="Aadhar Number" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.father_aadhar_number" class="text-red-500 text-xs font-semibold">{{ errors.father_aadhar_number[0] }}</span>
                    </div>
                </div>

                <h2 class="text-lg my-2">Mother's Detail</h2>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-2/3">
                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="mother_name" class="tw-form-label">Mother Name<span class="text-red-500">*</span></label>
                                    <input type="text" name="mother_name" v-model="mother_name" placeholder="Mother Name" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.mother_name" class="text-red-500 text-xs font-semibold">{{ errors.mother_name[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="mother_qualification_id" class="tw-form-label">Qualification<span class="text-red-500">*</span></label>
                                    <select class="tw-form-control w-full" id="mother_qualification_id" v-model="mother_qualification_id" name="mother_qualification_id">
                                        <option value="" disabled>Select Qualification</option>
                                        <option v-for="qualifications in qualificationlist" v-bind:value="qualifications.id">{{ qualifications.display_name }}</option>
                                    </select>
                                </div>
                                <span v-if="errors.mother_qualification_id" class="text-red-500 text-xs font-semibold">{{ errors.mother_qualification_id[0] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="mother_occupation" class="tw-form-label">Occupation<span class="text-red-500">*</span></label>
                                    <input type="text"  name="mother_occupation" v-model="mother_occupation" placeholder="Occupation" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.mother_occupation" class="text-red-500 text-xs font-semibold">{{ errors.mother_occupation[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="mother_designation" class="tw-form-label">Designation<span class="text-red-500">*</span></label>
                                    <input type="text" name="mother_designation" v-model="mother_designation" placeholder="Designation" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.mother_designation" class="text-red-500 text-xs font-semibold">{{ errors.mother_designation[0] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="mother_organisation" class="tw-form-label">Organization<span class="text-red-500">*</span></label>
                                    <input type="text" name="mother_organisation" v-model="mother_organisation" placeholder="Organization" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.mother_organisation" class="text-red-500 text-xs font-semibold">{{ errors.mother_organisation[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="mother_income" class="tw-form-label">Income (P.A)<span class="text-red-500">*</span></label>
                                    <input type="text" name="mother_income" placeholder="Income" v-model="mother_income" class="tw-form-control w-full my-1 py-2">
                                </div>
                                <span v-if="errors.mother_income" class="text-red-500 text-xs font-semibold">{{ errors.mother_income[0] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3">
                        <div class="relative w-10/12 mx-auto my-2">
                            <label for="mother_avatar" class="tw-form-label">Attach Photo</label>
                            <input type="file" name="mother_avatar" @change="OnFileSelected($event,'mother')" id="mother_avatar" class="tw-form-control w-full">
                            <div class="" v-if="mother_image != ''">
                                <img :src="mother_image" style="width: 150px;height: 150px;">
                            </div>
                            <div class="" v-else>
                                <img id="blah1" class="student-img text-sm border border-dashed border-gray-300 my-2" :src='url+"/uploads/user/avatar/default-user.jpg"' style="width: 150px;height: 150px;">
                            </div>
                        </div>
                        <span v-if="errors.mother_avatar" class="text-red-500 text-xs font-semibold">{{ errors.mother_avatar[0] }}</span>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_mobile_no" class="tw-form-label">Mobile Number</label>
                            <input type="text" name="mother_mobile_no" v-model="mother_mobile_no" placeholder="Mobile Number" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.mother_mobile_no" class="text-red-500 text-xs font-semibold">{{ errors.mother_mobile_no[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_email" class="tw-form-label">Email ID</label>
                            <input type="text" name="mother_email" v-model="mother_email" placeholder="Email ID" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.mother_email" class="text-red-500 text-xs font-semibold">{{ errors.mother_email[0] }}</span>
                    </div>
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_aadhar_number" class="tw-form-label">Aadhaar ID</label>
                            <input type="text" name="mother_aadhar_number" v-model="mother_aadhar_number" placeholder="Aadhar Number" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.mother_aadhar_number" class="text-red-500 text-xs font-semibold">{{ errors.mother_aadhar_number[0] }}</span>
                    </div>
                </div>

                <h2 class="text-lg my-2">Emergency Contact<span class="text-red-500">*</span></h2>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1 flex items-center">
                            <label for="emergency_contact_1" class="tw-form-label">1.</label>
                            <input type="text" name="emergency_contact_1" v-model="emergency_contact_1" placeholder="Mobile Number" class="tw-form-control w-full my-1 py-2 mx-2">
                        </div>
                        <span v-if="errors.emergency_contact_1" class="text-red-500 text-xs font-semibold">{{ errors.emergency_contact_1[0] }}</span>
                    </div>
                    <div class="w-full lg:w-2/3 lg:mr-2">
                        <div class="my-1 flex flex-col lg:flex-row lg:items-center ml-5 lg:ml-0">
                            <label for="relation_with_student_1" class="tw-form-label w-full lg:w-4/12">Relationship with Student</label>
                            <input type="text" name="relation_with_student_1" v-model="relation_with_student_1" placeholder="Relationship" class="tw-form-control w-full lg:w-8/12 my-1 py-2">
                        </div>
                        <span v-if="errors.relation_with_student_1" class="text-red-500 text-xs font-semibold">{{ errors.relation_with_student_1[0] }}</span>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1 flex items-center">
                            <label for="emergency_contact_2" class="tw-form-label">2.</label>
                            <input type="text" name="emergency_contact_2" v-model="emergency_contact_2" placeholder="Mobile Number" class="tw-form-control w-full my-1 py-2 mx-2">
                        </div>
                        <span v-if="errors.emergency_contact_2" class="text-red-500 text-xs font-semibold">{{ errors.emergency_contact_2[0] }}</span>
                    </div>
                    <div class="w-full lg:w-2/3 lg:mr-2">
                        <div class="my-1 flex flex-col lg:flex-row items-center ml-5 lg:ml-0">
                            <label for="relation_with_student_2" class="tw-form-label w-full lg:w-4/12">Relationship with Student</label>
                            <input type="text" name="relation_with_student_2" v-model="relation_with_student_2" placeholder="Relationship" class="tw-form-control w-full lg:w-8/12 my-1 py-2">
                        </div>
                        <span v-if="errors.relation_with_student_2" class="text-red-500 text-xs font-semibold">{{ errors.relation_with_student_2[0] }}</span>
                    </div>
                </div>
  
                <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="previousForm('3')">Previous</a>
                <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="submitForm('5')">Next</a>
            </fieldset>
        </div>
    </div>
</template>

<script>
    import { bus } from "../../app";
    import PortalVue from "portal-vue";
    export default {
        props:['url','slug'],
        data(){
            return{
                profile_tab:'',
                father_name:'',
                father_qualification_id:'',
                father_occupation:'',
                father_designation:'',
                father_organisation:'',
                father_income:'',
                father_mobile_no:'',
                father_email:'',
                father_aadhar_number:'',
                father_avatar:'',
                father_image:'',
                mother_name:'',
                mother_qualification_id:'',
                mother_occupation:'',
                mother_designation:'',
                mother_organisation:'',
                mother_income:'',
                mother_mobile_no:'',
                mother_email:'',
                mother_aadhar_number:'',
                mother_avatar:'',
                mother_image:'',
                emergency_contact_1:'',
                relation_with_student_1:'',
                emergency_contact_2:'',
                relation_with_student_2:'',
                qualificationlist:[],
                errors:[],
                success:null,
            }
        },
        
        methods:
        {     
            OnFileSelected(event,type)
            {
                if(type == 'mother')
                {
                    this.mother_avatar = event.target.files[0];
                }
                else if(type == 'father')
                {
                    this.father_avatar = event.target.files[0];
                }
                let files = event.target.files || event.dataTransfer.files;
                if (!files.length)
                return;
                this.createImage(files[0],type);
            },

            createImage(file,type) 
            {
                let reader = new FileReader();
                let vm = this;
                if(type == 'mother')
                {
                    reader.onload = (e) => {
                        vm.mother_image = e.target.result;
                    };
                }
                else if(type == 'father')
                {
                    reader.onload = (e) => {
                        vm.father_image = e.target.result;
                    };
                }
                reader.readAsDataURL(file);
            },

            submitForm(val)
            {
                this.errors=[];
                this.success=null; 

                let formData = new FormData(); 

                formData.append('father_name',this.father_name);          
                formData.append('father_qualification_id',this.father_qualification_id);          
                formData.append('father_designation',this.father_designation);          
                formData.append('father_occupation',this.father_occupation);                
                formData.append('father_organisation',this.father_organisation);                
                formData.append('father_income',this.father_income);                
                formData.append('father_mobile_no',this.father_mobile_no);                
                formData.append('father_email',this.father_email);                
                formData.append('father_aadhar_number',this.father_aadhar_number);                
                formData.append('father_avatar',this.father_avatar);                

                formData.append('mother_name',this.mother_name);          
                formData.append('mother_qualification_id',this.mother_qualification_id);          
                formData.append('mother_designation',this.mother_designation);          
                formData.append('mother_occupation',this.mother_occupation);                
                formData.append('mother_organisation',this.mother_organisation);                
                formData.append('mother_income',this.mother_income);                
                formData.append('mother_mobile_no',this.mother_mobile_no);                
                formData.append('mother_email',this.mother_email);                
                formData.append('mother_aadhar_number',this.mother_aadhar_number);                
                formData.append('mother_avatar',this.mother_avatar);                

                formData.append('emergency_contact_1',this.emergency_contact_1);                    
                formData.append('relation_with_student_1',this.relation_with_student_1);                    
                formData.append('emergency_contact_2',this.emergency_contact_2);                    
                formData.append('relation_with_student_2',this.relation_with_student_2);                    
       
                axios.post(this.url+'/'+this.slug+'/admission-form/validationParentDetail',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
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
            },
        },
        created()
        {
            axios.get(this.url+'/'+this.slug+'/standardlist').then(response => { 
                this.qualificationlist = response.data.qualificationlist;    
            });
            bus.$on("dataAdmissionTab", data => {
                if(data!='')
                {
                    this.profile_tab=data;                   
                }
            });   
        }
    }
</script>