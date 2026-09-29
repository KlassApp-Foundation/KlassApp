<template>
    <div class="bg-white shadow px-4 py-3" v-bind:class="[this.profile_tab==4?'block' :'hidden']">
        <div>
            <fieldset class="shadow">
                <h2 class="text-lg my-2">Parent or guardian 1 (primary contact)</h2>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="father_name" class="tw-form-label">Full name<span class="text-red-500">*</span></label>
                            <input type="text" name="father_name" v-model="father_name" placeholder="Full name" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.father_name" class="text-red-500 text-xs font-semibold">{{ errors.father_name[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="father_relationship" class="tw-form-label">Relationship to the child<span class="text-red-500">*</span></label>
                            <input type="text" name="father_relationship" v-model="father_relationship" placeholder="Relationship to the child" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.father_relationship" class="text-red-500 text-xs font-semibold">{{ errors.father_relationship[0] }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="father_mobile_no" class="tw-form-label">Phone<span class="text-red-500">*</span></label>
                            <input type="text" name="father_mobile_no" v-model="father_mobile_no" placeholder="Phone number" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.father_mobile_no" class="text-red-500 text-xs font-semibold">{{ errors.father_mobile_no[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1 flex items-center">
                            <input type="checkbox" id="father_on_whatsapp" name="father_on_whatsapp" value="1" v-model="father_on_whatsapp">
                            <label for="father_on_whatsapp" class="tw-form-label mx-2">This number is on WhatsApp</label>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="father_alt_phone" class="tw-form-label">Alternative phone</label>
                            <input type="text" name="father_alt_phone" v-model="father_alt_phone" placeholder="Alternative phone (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.father_alt_phone" class="text-red-500 text-xs font-semibold">{{ errors.father_alt_phone[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="father_email" class="tw-form-label">Email</label>
                            <input type="text" name="father_email" v-model="father_email" placeholder="Email (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.father_email" class="text-red-500 text-xs font-semibold">{{ errors.father_email[0] }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="father_occupation" class="tw-form-label">Occupation</label>
                            <input type="text" name="father_occupation" v-model="father_occupation" placeholder="Occupation (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.father_occupation" class="text-red-500 text-xs font-semibold">{{ errors.father_occupation[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="father_district" class="tw-form-label">District of residence<span class="text-red-500">*</span></label>
                            <input type="text" name="father_district" v-model="father_district" placeholder="District of residence" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.father_district" class="text-red-500 text-xs font-semibold">{{ errors.father_district[0] }}</span>
                        </div>
                    </div>
                </div>

                <h2 class="text-lg my-2">Parent or guardian 2</h2>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_name" class="tw-form-label">Full name</label>
                            <input type="text" name="mother_name" v-model="mother_name" placeholder="Full name (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.mother_name" class="text-red-500 text-xs font-semibold">{{ errors.mother_name[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_relationship" class="tw-form-label">Relationship to the child</label>
                            <input type="text" name="mother_relationship" v-model="mother_relationship" placeholder="Relationship to the child (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.mother_relationship" class="text-red-500 text-xs font-semibold">{{ errors.mother_relationship[0] }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_mobile_no" class="tw-form-label">Phone</label>
                            <input type="text" name="mother_mobile_no" v-model="mother_mobile_no" placeholder="Phone number (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.mother_mobile_no" class="text-red-500 text-xs font-semibold">{{ errors.mother_mobile_no[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1 flex items-center">
                            <input type="checkbox" id="mother_on_whatsapp" name="mother_on_whatsapp" value="1" v-model="mother_on_whatsapp">
                            <label for="mother_on_whatsapp" class="tw-form-label mx-2">This number is on WhatsApp</label>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_alt_phone" class="tw-form-label">Alternative phone</label>
                            <input type="text" name="mother_alt_phone" v-model="mother_alt_phone" placeholder="Alternative phone (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.mother_alt_phone" class="text-red-500 text-xs font-semibold">{{ errors.mother_alt_phone[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_email" class="tw-form-label">Email</label>
                            <input type="text" name="mother_email" v-model="mother_email" placeholder="Email (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.mother_email" class="text-red-500 text-xs font-semibold">{{ errors.mother_email[0] }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_occupation" class="tw-form-label">Occupation</label>
                            <input type="text" name="mother_occupation" v-model="mother_occupation" placeholder="Occupation (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.mother_occupation" class="text-red-500 text-xs font-semibold">{{ errors.mother_occupation[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="mother_district" class="tw-form-label">District of residence</label>
                            <input type="text" name="mother_district" v-model="mother_district" placeholder="District of residence (optional)" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.mother_district" class="text-red-500 text-xs font-semibold">{{ errors.mother_district[0] }}</span>
                        </div>
                    </div>
                </div>

                <h2 class="text-lg my-2">Emergency contact (if different)</h2>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="emergency_contact_name_1" class="tw-form-label">Name</label>
                            <input type="text" name="emergency_contact_name_1" v-model="emergency_contact_name_1" placeholder="Name" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.emergency_contact_name_1" class="text-red-500 text-xs font-semibold">{{ errors.emergency_contact_name_1[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="relation_with_student_1" class="tw-form-label">Relationship to the child</label>
                            <input type="text" name="relation_with_student_1" v-model="relation_with_student_1" placeholder="Relationship" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.relation_with_student_1" class="text-red-500 text-xs font-semibold">{{ errors.relation_with_student_1[0] }}</span>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/3 lg:mr-2">
                        <div class="my-1">
                            <label for="emergency_contact_1" class="tw-form-label">Phone</label>
                            <input type="text" name="emergency_contact_1" v-model="emergency_contact_1" placeholder="Phone number" class="tw-form-control w-full my-1 py-2">
                            <span v-if="errors.emergency_contact_1" class="text-red-500 text-xs font-semibold">{{ errors.emergency_contact_1[0] }}</span>
                        </div>
                    </div>
                </div>

                <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="previousForm('3')">Previous</a>
                <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="submitForm('5')">Next</a>
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
                father_name:'',
                father_relationship:'',
                father_mobile_no:'',
                father_on_whatsapp:true,
                father_alt_phone:'',
                father_email:'',
                father_occupation:'',
                father_district:'',
                mother_name:'',
                mother_relationship:'',
                mother_mobile_no:'',
                mother_on_whatsapp:true,
                mother_alt_phone:'',
                mother_email:'',
                mother_occupation:'',
                mother_district:'',
                emergency_contact_name_1:'',
                relation_with_student_1:'',
                emergency_contact_1:'',
                errors:[],
                success:null,
            }
        },
        
        methods:
        {     
            submitForm(val)
            {
                this.errors=[];
                this.success=null; 

                let formData = new FormData(); 

                formData.append('father_name',this.father_name);          
                formData.append('father_relationship',this.father_relationship);          
                formData.append('father_mobile_no',this.father_mobile_no);          
                formData.append('father_on_whatsapp',this.father_on_whatsapp ? 1 : 0);          
                formData.append('father_alt_phone',this.father_alt_phone);          
                formData.append('father_email',this.father_email);          
                formData.append('father_occupation',this.father_occupation);          
                formData.append('father_district',this.father_district);          

                formData.append('mother_name',this.mother_name);          
                formData.append('mother_relationship',this.mother_relationship);          
                formData.append('mother_mobile_no',this.mother_mobile_no);          
                formData.append('mother_on_whatsapp',this.mother_on_whatsapp ? 1 : 0);          
                formData.append('mother_alt_phone',this.mother_alt_phone);          
                formData.append('mother_email',this.mother_email);          
                formData.append('mother_occupation',this.mother_occupation);          
                formData.append('mother_district',this.mother_district);          

                formData.append('emergency_contact_name_1',this.emergency_contact_name_1);                    
                formData.append('relation_with_student_1',this.relation_with_student_1);                    
                formData.append('emergency_contact_1',this.emergency_contact_1);                    
       
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
            bus.$on("dataAdmissionTab", data => {
                if(data!='')
                {
                    this.profile_tab=data;                   
                }
            });   
        }
    }
</script>
