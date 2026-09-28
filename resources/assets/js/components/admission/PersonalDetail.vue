<template>
    <div class="bg-white shadow px-4 py-3" v-bind:class="[this.profile_tab==5?'block' :'hidden']">
        <div>
            <fieldset class="shadow">
                <h2 class="text-lg my-2">Health and support</h2>
                <div class="my-1">
                    <label for="medical_conditions" class="tw-form-label">Allergies or medical conditions the school should know about (optional)</label>
                    <textarea name="medical_conditions" v-model="medical_conditions" rows="3" placeholder="Anything the school should know" class="tw-form-control w-full my-1 py-2"></textarea>
                    <span v-if="errors.medical_conditions" class="text-red-500 text-xs font-semibold">{{ errors.medical_conditions[0] }}</span>
                </div>
                <div class="my-1">
                    <label for="special_needs" class="tw-form-label">Special needs or disability support required (optional)</label>
                    <textarea name="special_needs" v-model="special_needs" rows="3" placeholder="Support the school can provide" class="tw-form-control w-full my-1 py-2"></textarea>
                    <span v-if="errors.special_needs" class="text-red-500 text-xs font-semibold">{{ errors.special_needs[0] }}</span>
                </div>
                <p class="text-sm text-gray-600 my-3" data-testid="health-notice">This information is only used to care for the child and is visible only to authorised staff.</p>

                <portal-target name="submit-btn"></portal-target>
                <portal to="submit-btn">
                    <div class="my-6">
                        <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="previousForm('4')">Previous</a>
                        <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="submitForm()">Submit</a>
                        <input type="submit" class="hidden" id="submit-btn">
                    </div>
                </portal>
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
                medical_conditions:'',
                special_needs:'',
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
         
                formData.append('medical_conditions',this.medical_conditions);          
                formData.append('special_needs',this.special_needs);          
       
                axios.post(this.url+'/'+this.slug+'/admission-form/validationPersonalDetail',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    $('#submit-btn').click();  
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
