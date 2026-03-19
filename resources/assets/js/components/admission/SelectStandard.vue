<template>
    <div class="bg-white shadow px-4 py-3" v-bind:class="[this.profile_tab == 1 ?'block' :'hidden']">
        <div>
            <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <label for="standard_id" class="tw-form-label">Class<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                            <select class="tw-form-control w-full" id="standard_id" v-model="standard_id" name="standard_id">
                                <option value="" disabled>Select Class</option>
                                <option v-for="standard in standardlist" v-bind:value="standard.id">{{ standard.name }}</option>
                            </select>
                            <span v-if="errors.standard_id" class="text-red-500 text-xs font-semibold">{{ errors.standard_id[0] }}</span>
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
    import { bus } from "../../app";
    import PortalVue from "portal-vue";
    export default {
        props:['url','slug'],
        data(){
            return{
                profile_tab:'1',
                standardlist:[],
                standard_id:'',
                errors:[],
                success:null,
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