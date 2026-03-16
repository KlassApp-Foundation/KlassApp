<template>
    <div class="bg-white shadow px-4 py-3" v-bind:class="[this.profile_tab==5?'block' :'hidden']">
        <div>
            <fieldset class="shadow">
                <div class="my-1">
                    <h2 class="text-lg">Medical History<span class="text-red-500">*</span></h2>
                    <div class="flex flex-col lg:flex-row lg:items-center">
                        <label for="medical_history" class="tw-form-label">Does the Student having any Health issues? : </label>
                        <div class="flex py-2 lg:mx-1" v-for="list in booleanlist">
                            <div class="w-full lg:w-1/4 flex items-center mr-2 lg:mr-8 md:mr-8">
                                <input type="radio" v-model="medical_history" name="medical_history" :value="list.id"> 
                                <span class="text-sm mx-2">{{ list.name }}</span>
                            </div>
                        </div>
                    </div>
                        <span v-if="errors.medical_history" class="text-red-500 text-xs font-semibold">{{ errors.medical_history[0] }}</span>

                    <div id="medical_history" v-if="this.medical_history=='yes'">
                        <ul class="flex flex-wrap list-reset leading-loose">
                            <li class="w-full lg:w-1/4 md:w-1/2 my-2 relative flex items-center my-1" v-for="list in medicalList">
                                <input type="checkbox" id="medical_details" name="medical_details" :value="list.id">
                                <label for="list.id" class="tw-form-label mx-2">{{ list.name }}</label>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="my-1">
                    <h2 class="text-lg mt-4">Extra Curricular<span class="text-red-500">*</span></h2>
                    <div class="flex flex-col lg:flex-row lg:items-center">
                        <label for="extra_curricular_activities" class="tw-form-label">Does the Student has interest in any extra co – curricular activities ? :</label>
                        <div class="flex py-2 lg:mx-1" v-for="list in booleanlist">
                            <div class="w-full lg:w-1/4 flex items-center mr-2 lg:mr-8 md:mr-8">
                                <input type="radio" v-model="extra_curricular_activities" name="extra_curricular_activities" :value="list.id"> 
                                <span class="text-sm mx-2">{{ list.name }}</span>
                            </div>
                        </div>
                    </div>
                        <span v-if="errors.extra_curricular_activities" class="text-red-500 text-xs font-semibold">{{ errors.extra_curricular_activities[0] }}</span>

                    <div id="extra_curricular_activities" v-if="this.extra_curricular_activities=='yes'">
                        <ul class="flex flex-wrap list-reset leading-loose">
                            <li class="w-full lg:w-1/4 md:w-1/2 my-2 relative flex items-center my-1" v-for="list in activitiesList">
                                <input type="checkbox" id="activities" name="activities" :value="list.id">
                                <label for="Vocal" class="tw-form-label mx-2">{{ list.name }}</label>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="my-1">
                    <h2 class="text-lg mt-4">Mode of Transport<span class="text-red-500">*</span></h2>
                    <div class="">
                        <div class="flex flex-wrap py-2">
                            <div class="w-full lg:w-1/4 md:w-1/2 my-2 relative flex items-center my-1" v-for="list in transportList">
                                <input type="radio" v-model="mode_of_transport" name="mode_of_transport" :value="list.id"> 
                                <span class="text-sm mx-2">{{ list.name }}</span>
                            </div>
                        </div>
                    </div>
                        <span v-if="errors.mode_of_transport"><p class="text-red-500 text-xs font-semibold">{{errors.mode_of_transport[0]}}</p></span>

                    <div v-if="this.mode_of_transport=='auto' || this.mode_of_transport=='rickshaw' || this.mode_of_transport=='taxi'">
                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 my-1 mr-2">
                                <label for="driver_name" class="tw-form-label">Driver's Name</label>
                                <input type="text" v-model="driver_name" name="driver_name" placeholder="Driver's Name" class="tw-form-control w-full my-1 py-2">
                                <span v-if="errors.driver_name" class="text-red-500 text-xs font-semibold">{{ errors.driver_name[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 my-1 mr-2">
                                <label for="driver_mobile_number" class="tw-form-label">Phone Number</label>
                                <input type="text" v-model="driver_mobile_number" name="driver_mobile_number" placeholder="Phone Number" class="tw-form-control w-full my-1 py-2">
                                <span v-if="errors.driver_mobile_number" class="text-red-500 text-xs font-semibold">{{ errors.driver_mobile_number[0] }}</span>
                            </div>
                        </div>  
                    </div>
                </div>

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
    import { bus } from "../../app";
    import PortalVue from "portal-vue";
    export default {
        props:['url','slug'],
        data(){
            return{
                profile_tab:'',
                medical_history:'',
                medical_details:[],
                extra_curricular_activities:'',
                activities:[],
                mode_of_transport:'',
                driver_name:'',
                driver_mobile_number:'',
                errors:[],
                booleanlist:[{id:'yes' , name:'Yes'} , {id:'no' , name:'No'}],
                medicalList:[ {id:'Asthama' , name:'Asthama'} , {id:'Diabetics' , name:'Diabetics'} , {id:'Dizziness' , name:'Fainting / Dizziness'} , {id:'Fits' , name:'Fits'} , {id:'Hear Problem' , name:'Hear Problem'} , {id:'Intellectual Disability' , name:'Intellectual Disability'} , {id:'Migraines' , name:'Migraines'} , {id:'Recent Injuries' , name:'Recent Injuries'}  , {id:'Others' , name:'Others'} ],
                activitiesList:[ {id:'Art & Craft' , name:'Art & Craft'} , {id:'Bharatham' , name:'Bharatham'} , {id:'Chess' , name:'Chess'} , {id:'Drums' , name:'Drums'} , {id:'Embroidery' , name:'Embroidery'} ,  {id:'Guitar' , name:'Guitar'} , {id:'Karate' , name:'Karate'} , {id:'Keyboard' , name:'Keyboard'} , {id:'Mridangam' , name:'Mridangam'} , {id:'Skating' , name:'Skating'} , {id:'Vocal' , name:'Vocal'} ,  {id:'Violin' , name:'Violin'} ],
                transportList:[],
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
         
                formData.append('medical_history',this.medical_history);          
                formData.append('medical_details',this.medical_details);          
                formData.append('activities',this.activities);          
                formData.append('extra_curricular_activities',this.extra_curricular_activities);          
                formData.append('mode_of_transport',this.mode_of_transport);                
                formData.append('driver_name',this.driver_name);                
                formData.append('driver_mobile_number',this.driver_mobile_number);                
       
                axios.post(this.url+'/'+this.slug+'/admission-form/validationPersonalDetail',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     console.log(this.medical_details);
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
            axios.get(this.url+'/'+this.slug+'/standardlist').then(response => {
                this.transportList = response.data.transportList;
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