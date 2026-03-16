<template>
    <div>
        <div class="bg-white shadow px-4 py-3">
            <div>
                <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

                <div class="flex flex-col lg:flex-row md:flex-row items-end">
                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="mb-2">
                                <label for="relation" class="tw-form-label">Visitor Type<span class="text-red-500">*</span></label>
                            </div>
                            <div class="">
                                <div class="flex">
                                    <div class="w-1/2 flex items-center mr-2"> 
                                        <input type="radio" v-model="relation" name="relation" class="tw-form-control" id="parent" value="parent" @click="enableDiv('parent')">
                                        <span class="text-sm mx-1">Parent</span>
                                    </div>
                                    <div class="w-1/2 flex items-center"> 
                                        <input type="radio" v-model="relation" name="relation" class="tw-form-control" id="other" value="other" @click="enableDiv('other')">
                                        <span class="text-sm mx-1">Other</span>
                                    </div>
                                </div>
                                <span v-if="errors.relation" class="text-red-500 text-xs font-semibold">{{ errors.relation[0] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row md:flex-row items-end" v-if="this.relation == 'parent'">
                    <div class="w-full lg:w-1/4 md:w-1/2" id="parent">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="relation_with_student" class="tw-form-label">Relation With Student<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <select class="tw-form-control w-full" id="relation_with_student" v-model="relation_with_student" name="relation_with_student" @change="enableName()">
                                        <option value="" disabled>Relation With Student</option>
                                        <option v-for="relation in relationlist" v-bind:value="relation.id">{{ relation.name }}</option>               
                                    </select>
                                    <span v-if="errors.relation_with_student" class="text-red-500 text-xs font-semibold">{{ errors.relation_with_student[0] }}</span>
                                </div>
                            </div>     
                        </div>
                    </div>

                    <div class="w-full lg:w-1/4 md:w-1/2" v-if="this.relation_with_student == 'others'">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="relation_name" class="tw-form-label">Relation With Student<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="relation_name" id="relation_name" v-model="relation_name" class="tw-form-control w-full" placeholder="Relation With Student">
                                    <span v-if="errors.relation_name" class="text-red-500 text-xs font-semibold">{{ errors.relation_name[0] }}</span>
                                </div>
                            </div>     
                        </div>
                    </div>

                    <div class="w-full lg:w-1/4 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="standardLink_id" class="tw-form-label">Class<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <select name="standardLink_id" id="standardLink_id" v-model="standardLink_id"  @change='getstudent()' class="tw-form-control w-full">
                                        <option value="" disabled>Select class</option>
                                        <option v-for="classlist in standardlist" v-bind:value="classlist.id">{{ classlist.standard_section }}</option>
                                    </select>
                                    <span v-if="errors.standardLink_id" class="text-red-500 text-xs font-semibold">{{ errors.standardLink_id[0] }}</span>
                                </div>            
                            </div> 
                        </div>
                    </div> 

                    <div class="w-full lg:w-1/4 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="student_id" class="tw-form-label">Student<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <select name="student_id" id="student_id" v-model="student_id" class="tw-form-control w-full">
                                        <option value="" disabled>Select Student</option>
                                        <option v-for="student in studentlist" v-bind:value="student.id">{{ student.fullname }}</option>
                                    </select>
                                    <span v-if="errors.student_id" class="text-red-500 text-xs font-semibold">{{ errors.student_id[0] }}</span>
                                </div>
                            </div> 
                        </div>
                    </div>     
                </div>

                <div class="flex flex-col lg:flex-row md:flex-row">
                    <div class="w-full lg:w-1/3 md:w-1/2 hidden" id="visitor_name">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="name" class="tw-form-label">Visitor Name<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="name" id="name" v-model="name" class="tw-form-control w-full" placeholder="Visitor Name">
                                    <span v-if="errors.name" class="text-red-500 text-xs font-semibold">{{ errors.name[0] }}</span>
                                </div>
                            </div>     
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="visiting_purpose" class="tw-form-label">Visiting Purpose<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <select class="tw-form-control w-full" id="visiting_purpose" v-model="visiting_purpose" name="visiting_purpose">
                                        <option value="" disabled>Visiting Purpose</option>
                                        <option v-for="list in visitinglist" v-bind:value="list.id">{{ list.name }}</option>
                                    </select>
                                </div>
                                <span v-if="errors.visiting_purpose" class="text-red-500 text-xs font-semibold">{{ errors.visiting_purpose[0] }}</span>
                            </div> 
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="number_of_visitors" class="tw-form-label">Visitor Count</label>
                                </div>
                                <div class="mb-2">
                                    <input type="number" name="number_of_visitors" id="number_of_visitors" v-model="number_of_visitors" class="tw-form-control w-full" placeholder="Visitor Count">
                                    <span v-if="errors.number_of_visitors" class="text-red-500 text-xs font-semibold">{{ errors.number_of_visitors[0] }}</span>
                                </div>
                            </div>     
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="employee_id" class="tw-form-label">Whom To Meet<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <select class="tw-form-control w-full" id="employee_id" v-model="employee_id" name="employee_id">
                                        <option value="" disabled>Select Employee</option>
                                        <option v-for="teacher in teacherlist" v-bind:value="teacher.id">{{ teacher.fullname }} ( {{teacher.designation_display}} )</option>
                                    </select>
                                    <span v-if="errors.employee_id" class="text-red-500 text-xs font-semibold">{{ errors.employee_id[0] }}</span>
                                </div>          
                            </div> 
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row md:flex-row" v-if="this.relation == 'other' || this.visiting_purpose == 'business'">
                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="company_name" class="tw-form-label">Organisation Name<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="company_name" id="company_name" v-model="company_name" class="tw-form-control w-full" placeholder="Organisation Name">
                                    <span v-if="errors.company_name" class="text-red-500 text-xs font-semibold">{{ errors.company_name[0] }}</span>
                                </div>
                            </div>     
                        </div>
                    </div>    

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="address" class="tw-form-label">Address<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <textarea type="text" name="address" id="address" v-model="address" class="tw-form-control w-full" rows="3" placeholder="Address"></textarea>
                                    <span v-if="errors.address" class="text-red-500 text-xs font-semibold">{{errors.address[0]}}</span>
                                </div>
                            </div>     
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="email" class="tw-form-label">Email</label>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="email" id="email" v-model="email" class="tw-form-control w-full" placeholder="Email">
                                    <span v-if="errors.email" class="text-red-500 text-xs font-semibold">{{ errors.email[0] }}</span>
                                </div>
                            </div>     
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="contact_number" class="tw-form-label">Contact Number<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <input type="text" name="contact_number" id="contact_number" v-model="contact_number" class="tw-form-control w-full" placeholder="Contact Number">
                                    <span v-if="errors.contact_number" class="text-red-500 text-xs font-semibold">{{ errors.contact_number[0] }}</span>
                                </div>
                            </div>     
                        </div>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row md:flex-row">
                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="date_of_visit" class="tw-form-label">Date Of Visit<span class="text-red-500">*</span></label>
                                </div>
                                <div class="mb-2">
                                    <input type="date" name="date_of_visit" v-model="date_of_visit" class="tw-form-control w-full" id="date_of_visit">
                                    <span v-if="errors.date_of_visit" class="text-red-500 text-xs font-semibold">{{ errors.date_of_visit[0] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="entry_time" class="tw-form-label">Entry Time</label>
                                </div>
                                <div class="mb-2">
                                    <input type="time" name="entry_time" v-model="entry_time" class="tw-form-control w-full" id="entry_time">
                                    <span v-if="errors.entry_time" class="text-red-500 text-xs font-semibold">{{ errors.entry_time[0] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="exit_time" class="tw-form-label">Exit Time</label>
                                </div>
                                <div class="mb-2">
                                    <input type="time" name="exit_time" v-model="exit_time" class="tw-form-control w-full" id="exit_time">
                                    <span v-if="errors.exit_time" class="text-red-500 text-xs font-semibold">{{ errors.exit_time[0] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-1/3 md:w-1/2">
                        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
                            <div class="">
                                <div class="mb-2">
                                    <label for="remark" class="tw-form-label">Remarks</label>
                                </div>
                                <div class="mb-2">
                                    <textarea type="textarea" name="remark" v-model="remark" class="tw-form-control w-full" id="remark" placeholder="Remarks"></textarea> 
                                    <span v-if="errors.remark" class="text-red-500 text-xs font-semibold">{{ errors.remark[0] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="my-6">
                    <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="addVisitorLog()">Submit</a>
                    <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>  
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props:['url','mode','date_of_visit'],
        data(){
            return{
                visitorlog:[],
                studentlist:[],
                teacherlist:[],
                standardlist:[],
                parents:[],
                name:'',
                relation:'',
                company_name:'',
                contact_number:'',
                address:'',
                email:'',
                student_id:'',
                relation_with_student:'',
                relation_name:'',
                number_of_visitors:'',
                visiting_purpose:'',
                standardLink_id:'',
                employee_id:'',
                //date_of_visit:'',
                entry_time:'',
                exit_time:'',
                remark:'',
                errors:[],
                success:null,
                relationlist:[{id:'father' , name:'Father'} , {id:'mother' , name:'Mother'} , {id:'guardian' , name:'Guardian'} , {id:'others' , name:'Others'}],
                visitinglist:[{id:'admission' , name:'Admission'} , {id:'business' , name:'Business'} , {id:'complaint' , name:'Complaint'} , {id:'enquiry' , name:'Enquiry'} , {id:'fee_payment' , name:'Fee Payment'} , {id:'parents_meeting' , name:'Parents Meeting'} , {id:'transfer_certificate' , name:'Transfer Certificate'}],
            }
        },
        
        methods:
        {
            getData()
            {
                axios.get(this.url+'/'+this.mode+'/visitorlog/list').then(response => {
                    this.standardlist = response.data.standardlist;
                    this.teacherlist  = response.data.teacherlist;
                });
            },

            getstudent()
            {
                axios.get(this.url+'/'+this.mode+'/visitorlog/list?standardLink_id='+this.standardLink_id).then(response=>{
                    this.studentlist = response.data.studentlist;
                    //console.log(this.studentlist);
                });
            },

            enableDiv(type)
            {
                if(type == 'parent')
                {
                    if($('#visitor_name').hasClass('block'))
                    {
                        $('#visitor_name').addClass('hidden').removeClass('block');
                    }
                }
                else
                {
                    $('#visitor_name').addClass('block').removeClass('hidden');
                }
            },

            enableName()
            {
                if(this.relation_with_student == 'others')
                {
                    $('#visitor_name').addClass('block').removeClass('hidden');
                }
                else
                {
                    $('#visitor_name').addClass('hidden').removeClass('block');
                }
            },

            reset()
            {
                this.relation='';
                this.student_id='';
                this.employee_id='';
                this.name='';
                this.visiting_purpose='';
                this.number_of_visitors='';
                this.relation='';  
                this.relation_with_student='';  
                this.relation_name='';  
                this.company_name='';  
                this.contact_number='';  
                this.address='';  
                this.email='';  
                this.whom_to_meet='';  
                this.entry_time='';  
                this.date_of_visit='';  
                this.exit_time='';  
                this.remark='';  
            }, 

            addVisitorLog()
            {
                this.errors=[];
                this.success=null; 

                let formData=new FormData();

                formData.append('student_id',this.student_id);                 
                formData.append('employee_id',this.employee_id); 
                formData.append('name',this.name);                
                formData.append('relation',this.relation); 
                formData.append('visiting_purpose',this.visiting_purpose);                 
                formData.append('standardLink_id',this.standardLink_id);                 
                formData.append('number_of_visitors',this.number_of_visitors);          
                formData.append('relation_with_student',this.relation_with_student);          
                formData.append('relation_name',this.relation_name);          
                formData.append('company_name',this.company_name);          
                formData.append('contact_number',this.contact_number);          
                formData.append('address',this.address);                    
                formData.append('email',this.email);                    
                formData.append('date_of_visit',this.date_of_visit);          
                formData.append('entry_time',this.entry_time);          
                formData.append('exit_time',this.exit_time);          
                formData.append('remark',this.remark);          
              
                axios.post(this.url+'/'+this.mode+'/visitorlog/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {  
                    this.success = response.data.success;
                    //this.reset();
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