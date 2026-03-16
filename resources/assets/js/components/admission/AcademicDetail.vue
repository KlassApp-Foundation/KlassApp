<template>
    <div class="bg-white shadow px-4 py-3" v-bind:class="[this.profile_tab==3?'block' :'hidden']">
        <div>
            <fieldset class="shadow">
                <h6 class="text-sm font-bold mb-3">Half Yearly Mark Details</h6>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full">
                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2">English</label>
                                    <input type="text" name="english" v-model="english" placeholder="English" class="tw-form-control w-1/2 mx-4 my-1 py-2">
                                </div>
                                <span v-if="errors.english" class="text-red-500 text-xs font-semibold">{{ errors.english[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2">Tamil</label>
                                    <input type="text" name="tamil" v-model="tamil" placeholder="Tamil" class="tw-form-control w-1/2 mx-4 my-1 py-2">
                                </div>
                                <span v-if="errors.tamil" class="text-red-500 text-xs font-semibold">{{ errors.tamil[0] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2">Maths</label>
                                    <input type="text" name="maths" v-model="maths" placeholder="Maths" class="tw-form-control w-1/2 mx-4 my-1 py-2">
                                </div>
                                <span v-if="errors.maths" class="text-red-500 text-xs font-semibold">{{ errors.maths[0] }}</span>
                            </div>
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2">Science</label>
                                    <input type="text" name="science" v-model="science" placeholder="Science" class="tw-form-control w-1/2 mx-4 my-1 py-2">
                                </div>
                                <span v-if="errors.science" class="text-red-500 text-xs font-semibold">{{ errors.science[0] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-1/2 lg:mr-2">
                                <div class="my-1">
                                    <label for="name" class="tw-form-label mr-2">Social</label>
                                    <input type="text" name="social" v-model="social" placeholder="Social" class="tw-form-control w-1/2 mx-4 my-1 py-2">
                                </div>
                                <span v-if="errors.social" class="text-red-500 text-xs font-semibold">{{ errors.social[0] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full my-1">
                        <h6 class="text-sm font-bold mb-3">Board of Study<span class="text-red-500">*</span></h6>
                        <ul class="list-reset leading-loose flex items-center">
                            <li v-for="board in boardlist">
                                <input type="radio" v-model="board_of_education" name="board_of_education" :value="board.id"> 
                                <span class="text-sm mr-6">{{ board.name }}</span>
                            </li>
                        </ul>
                        <span v-if="errors.board_of_education" class="text-red-500 text-xs font-semibold">{{ errors.board_of_education[0] }}</span>
                    </div>
                </div>
                <div class="flex flex-col lg:flex-row">
                    <div class="w-full my-1">
                        <h6 class="text-sm font-bold mb-3">Choice of Language<span class="text-red-500">*</span></h6>
                        <ul class="list-reset leading-loose flex items-center">
                            <li v-for="language in languagelist">
                                <input type="radio" v-model="choice_of_language" name="choice_of_language" :value="language.id"> 
                                <span class="text-sm mr-6">{{ language.name }}</span>
                            </li>
                        </ul>
                        <span v-if="errors.choice_of_language" class="text-red-500 text-xs font-semibold">{{ errors.choice_of_language[0] }}</span>
                      </div>
                </div>
                <div class="my-1">
                    <h6 class="text-sm font-bold mb-3">Group Selection<span class="text-red-500 whitespace-no-wrap">*Only For Class X , XI , XII</span></h6>
                    <ul class="list-reset leading-loose">
                        <li v-for="group in grouplist">
                            <input type="radio" v-model="group_selection" name="group_selection" :value="group.id" @click="selectGroup(group.id)"> 
                            <span class="text-sm mx-2">{{ group.name }}</span>
                        </li>
                    </ul>
                    <span v-if="errors.group_selection" class="text-red-500 text-xs font-semibold">{{ errors.group_selection[0] }}</span>
                </div>

                <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2 lg:mr-2">
                        <div class="my-1">
                            <label for="board_registration_number" class="tw-form-label"><h6 class="text-sm font-bold mb-3">Board Registration Number<span class="text-red-500 whitespace-no-wrap">*Only For Class X , XI , XII</span></h6></label>
                            <input type="text" v-model="board_registration_number" name="board_registration_number" placeholder="Board Registration Number" class="tw-form-control w-full my-1 py-2">
                        </div>
                        <span v-if="errors.board_registration_number" class="text-red-500 text-xs font-semibold">{{ errors.board_registration_number[0] }}</span>
                    </div>
                </div>

                <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="previousForm('2')">Previous</a>
                <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="submitForm('4')">Next</a>
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
                half_yearly_mark_details:'',
                english:'',
                tamil:'',
                maths:'',
                science:'',
                social:'',
                board_of_education:'',
                board_registration_number:'',
                choice_of_language:'',
                group_selection:'',
                standard_id:'',
                boardlist:[ {id:'anglo-indian' , name:'Anglo Indian'} , {id:'cbse' , name:'CBSE'} , {id:'icse' , name:'ICSE'} , {id:'matric' , name:'Matric'} , {id:'state-board' , name:'State Board'} , {id:'others' , name:'Others'} ],
                languagelist:[ {id:'tamil' , name:'Tamil'} , {id:'hindi' , name:'Hindi'} , {id:'sanskrit' , name:'Sanskrit'} , {id:'french' , name:'French'} ],
                grouplist:[ 
                    {id:'group1' , name:'Group 1    :   Maths, Physics, Chemistry &Computer Science'} , 
                    {id:'group2' , name:'Group 2    :   Maths, Physics, Chemistry & Biology'} , 
                    {id:'group3' , name:'Group 3    :   Physics, Chemistry, Biology & Computer Science'} , 
                    {id:'group4' , name:'Group 4    :   Commerce, Accountancy, Economics & Business Maths'} , 
                    {id:'group5' , name:'Group 5    :   Commerce, Accountancy, Economics & Computer Science'} 
                ],
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
               
                formData.append('english',this.english);          
                formData.append('tamil',this.tamil);          
                formData.append('maths',this.maths);          
                formData.append('science',this.science);          
                formData.append('social',this.social);           
                formData.append('board_of_education',this.board_of_education);          
                formData.append('choice_of_language',this.choice_of_language);          
                formData.append('group_selection',this.group_selection);
                formData.append('board_registration_number',this.board_registration_number);
                formData.append('standard_id',this.standard_id);                 
               
                axios.post(this.url+'/'+this.slug+'/admission-form/validationAcademicDetail',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
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

            selectGroup(val)
            {
                if(this.group_selection == val)
                {
                    this.group_selection = false;
                }
                else
                {
                    this.group_selection = val;
                }
            }
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