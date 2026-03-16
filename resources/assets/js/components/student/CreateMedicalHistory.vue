<template>
    <div class="bg-white shadow px-4 py-3">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{ this.success }}</div>
        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="height" class="tw-form-label">Height ( in cm's )<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <input type="text" class="tw-form-control w-full" id="height" v-model="height" name="height" placeholder="Height">
                    </div>
                    <span v-if="errors.height" class="text-red-500 text-xs font-semibold">{{ errors.height[0] }}</span>
                </div> 
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="weight" class="tw-form-label">Weight ( in kg's )<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <input type="text" v-model="weight" name="weight" id="weight" class="tw-form-control w-full" placeholder="Weight">
                    </div>
                    <span v-if="errors.weight" class="text-red-500 text-xs font-semibold">{{ errors.weight[0] }}</span>
                </div> 
            </div>
        </div>

        <hr style="border-width: 1px;" class="mb-2">
        <h1><b>Medical Problems</b></h1>
        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="medication_problems" class="tw-form-label">Medication Problems</label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" v-model="medication_problems" name="medication_problems" id="medication_problems" class="tw-form-control w-full" placeholder="Medication Problems" :maxlength="300"></textarea>
                        <div class="text-gray-700 text-xs my-1" v-text="(300 - medication_problems.length)+'/'+300" style="text-align: right" v-if="medication_problems != null"></div>               
                    </div>
                    <span v-if="errors.medication_problems" class="text-red-500 text-xs font-semibold">{{ errors.medication_problems[0] }}</span>
                </div> 
            </div>

            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="medication_needs" class="tw-form-label">Medication Needs</label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" v-model="medication_needs" name="medication_needs" id="medication_needs" class="tw-form-control w-full" placeholder="Medication Needs" :maxlength="300"></textarea>
                        <div class="text-gray-700 text-xs my-1" v-text="(300 - medication_needs.length)+'/'+300" style="text-align: right" v-if="medication_needs != null"></div> 
                    </div>
                    <span v-if="errors.medication_needs" class="text-red-500 text-xs font-semibold">{{ errors.medication_needs[0] }}</span>
                </div>
            </div>
        </div>

        <hr style="border-width: 1px;" class="mb-2">
        <h1><b>Allergies</b></h1>
        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="medication_allergies" class="tw-form-label">Medication Allergies</label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" v-model="medication_allergies" name="medication_allergies" id="medication_allergies" class="tw-form-control w-full" placeholder="Medication Allergies" :maxlength="300"></textarea> 
                        <div class="text-gray-700 text-xs my-1" v-text="(300 - medication_allergies.length)+'/'+300" style="text-align: right" v-if="medication_allergies != null"></div> 
                    </div>
                    <span v-if="errors.medication_allergies" class="text-red-500 text-xs font-semibold">{{ errors.medication_allergies[0] }}</span>
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="food_allergies" class="tw-form-label">Food Allergies</label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" class="tw-form-control w-full" id="food_allergies" v-model="food_allergies" name="food_allergies" placeholder="Food Allergies" :maxlength="300"></textarea>
                        <div class="text-gray-700 text-xs my-1" v-text="(300 - food_allergies.length)+'/'+300" style="text-align: right" v-if="food_allergies != null"></div>
                    </div>
                    <span v-if="errors.food_allergies" class="text-red-500 text-xs font-semibold">{{ errors.food_allergies[0] }}</span>
                </div> 
            </div>
        </div>

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-full">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="other_allergies" class="tw-form-label">Other Allergies</label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" class="tw-form-control w-full" v-model="other_allergies" name="other_allergies" id="other_allergies" rows="3" placeholder="Other Allergies" :maxlength="500"></textarea>
                        <div class="text-gray-700 text-xs my-1" v-text="(500 - other_allergies.length)+'/'+500" style="text-align: right" v-if="other_allergies != null"></div>
                    </div>
                    <span v-if="errors.other_allergies" class="text-red-500 text-xs font-semibold">{{ errors.other_allergies[0] }}</span>
                </div>
            </div>
        </div>

        <hr style="border-width: 1px;" class="mb-2">
        <h1><b>Additional Medical Information</b></h1>
        <div class="tw-form-group">
            <div class="flex flex-col lg:flex-row">
                <div class="w-full lg:w-full lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="other_medical_information" class="tw-form-label">Student Personal identification</label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" class="tw-form-control w-full" id="other_medical_information" v-model="other_medical_information" rows="3" name="other_medical_information" placeholder="(Mole, scar, cut etc……)" :maxlength="500"></textarea>
                        <div class="text-gray-700 text-xs my-1" v-text="(500 - other_medical_information.length)+'/'+500" style="text-align: right" v-if="other_medical_information != null"></div>
                    </div>
                    <span v-if="errors.other_medical_information" class="text-red-500 text-xs font-semibold">{{ errors.other_medical_information[0] }}</span>
                </div>
            </div>
        </div>  

        <div class="my-6">
            <a href="#" class="btn btn-primary submit-btn" @click="submitForm()">Submit</a>
            <a href="#" class="btn btn-reset reset-btn" @click="resetForm()" v-if="this.height == ''">Reset</a>
        </div>
    </div>
</template>

<script> 
    export default {
        props:['url' , 'name'],
        data(){
            return {
                user:[],
                height:'',
                weight:'',
                medication_problems:'',
                medication_needs:'',
                medication_allergies:'',
                food_allergies:'',
                other_allergies:'',
                other_medical_information:'',
                errors:[],
                success:null,
            }
        },

        methods:
        {
            getData()
            {
                axios.get('/admin/student/show/medicalHistory/'+this.name).then(response => {
                    this.user = response.data;
                    //console.log(this.user);
                    this.setData();   
                });
            },

            setData()
            {
                if(Object.keys(this.user).length > 0)
                {
                    this.height                     = this.user.height;
                    this.weight                     = this.user.weight;
                    this.medication_problems        = this.user.medication_problems;
                    this.medication_needs           = this.user.medication_needs;
                    this.medication_allergies       = this.user.medication_allergies;
                    this.food_allergies             = this.user.food_allergies;
                    this.other_allergies            = this.user.other_allergies;
                    this.other_medical_information  = this.user.other_medical_information;
                }
            },

            resetForm()
            {
                this.height                     = '';
                this.weight                     = '';
                this.medication_problems        = '';
                this.medication_needs           = '';
                this.medication_allergies       = '';
                this.food_allergies             = '';
                this.other_allergies            = '';
                this.other_medical_information  = '';
            },

            submitForm()
            {
                this.errors=[];
                this.success=null;

                let formData=new FormData(); 

                formData.append('height',this.height);          
                formData.append('weight',this.weight);
                formData.append('medication_problems',this.medication_problems);          
                formData.append('medication_needs',this.medication_needs);           
                formData.append('medication_allergies',this.medication_allergies);          
                formData.append('food_allergies',this.food_allergies);       
                formData.append('other_allergies',this.other_allergies); 
                formData.append('other_medical_information',this.other_medical_information);

                axios.post('/admin/student/add/medicalHistory/'+this.name,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success; 
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },
        },
      
        created()
        {
            this.getData();
        }
    }
</script>