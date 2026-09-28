<template>
    <div class="bg-white shadow px-4 py-3" v-bind:class="[this.profile_tab == 1 ?'block' :'hidden']">
        <div>
            <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <label for="standard_id" class="tw-form-label">Class applying for<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <select class="tw-form-control w-full" id="standard_id" v-model="standard_id" name="standard_id">
                                <option value="" disabled>Select Class</option>
                                <option v-for="standard in standardlist" v-bind:value="standard.id">{{ standard.name }}</option>
                            </select>
                            <span v-if="errors.standard_id" class="text-red-500 text-xs font-semibold">{{ errors.standard_id[0] }}</span>
                        </div>
                    </div>
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <label for="entry_term" class="tw-form-label">Term of entry<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <select class="tw-form-control w-full" id="entry_term" v-model="entry_term" name="entry_term">
                                <option value="" disabled>Select term</option>
                                <option value="1">Term I</option>
                                <option value="2">Term II</option>
                                <option value="3">Term III</option>
                            </select>
                            <span v-if="errors.entry_term" class="text-red-500 text-xs font-semibold">{{ errors.entry_term[0] }}</span>
                        </div>
                    </div>
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <label for="entry_year" class="tw-form-label">Year of entry<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <select class="tw-form-control w-full" id="entry_year" v-model="entry_year" name="entry_year">
                                <option value="" disabled>Select year</option>
                                <option v-for="year in years" v-bind:value="year">{{ year }}</option>
                            </select>
                            <span v-if="errors.entry_year" class="text-red-500 text-xs font-semibold">{{ errors.entry_year[0] }}</span>
                        </div>
                    </div>
                    <div v-if="offersBoarding" class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <label for="boarding_type" class="tw-form-label">Day or boarding<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <div class="flex tw-form-control py-2 my-1">
                                <div class="w-1/2 flex items-center mr-2">
                                    <input type="radio" name="boarding_type" id="boarding_day" v-model="boarding_type" value="day">
                                    <span class="text-sm mx-2">Day</span>
                                </div>
                                <div class="w-1/2 flex items-center">
                                    <input type="radio" name="boarding_type" id="boarding_boarding" v-model="boarding_type" value="boarding">
                                    <span class="text-sm mx-2">Boarding</span>
                                </div>
                            </div>
                            <span v-if="errors.boarding_type" class="text-red-500 text-xs font-semibold">{{ errors.boarding_type[0] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="my-6">
            <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="submitForm('2')">Next</a>
            <a href="#" class="btn-reset reset-btn" @click="resetForm()">Reset</a>
        </div>
    </div>
</template>


<script>
    import { bus } from "../../event-bus";
    import PortalVue from "portal-vue";
    export default {
        props:['url','slug','boarding'],
        data(){
            return{
                profile_tab:'1',
                standardlist:[],
                years:[],
                standard_id:'',
                entry_term:'',
                entry_year:String(new Date().getFullYear()),
                boarding_type:'',
                errors:[],
                success:null,
            }
        },

        computed:
        {
            offersBoarding()
            {
                return String(this.boarding) === 'true';
            }
        },
        
        methods:
        {
            resetForm()
            {
                window.location.reload(); 
            }, 

            submitForm(val)
            {
                this.errors=[];
                this.success=null; 

                let formData = new FormData(); 

                formData.append('standard_id',this.standard_id);          
                formData.append('entry_term',this.entry_term);          
                formData.append('entry_year',this.entry_year);          
                formData.append('boarding_type',this.boarding_type);          

                axios.post(this.url+'/'+this.slug+'/admission-form/validationStandard',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.setProfileTab(val); 
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
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
            const current = new Date().getFullYear();
            this.years = [current, current + 1, current + 2, current + 3];

            axios.get(this.url+'/'+this.slug+'/standardlist').then(response => { 
                this.standardlist=response.data.standardlist;    
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
