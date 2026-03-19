<template>
    <div class="bg-white shadow px-3 py-4">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
        <!-- <div class="flex">
            <div class="tw-form-group w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="standardlink_id" class="tw-form-label">Select Class<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <select class="tw-form-control w-full" id="standardlink_id" v-model="standardlink_id" name="standardlink_id">
                            <option value="" disabled>Select Class</option>
                            <option value="" v-for="standardlink in standardLinklist" v-bind:value="standardlink.id">{{standardlink.standard.name}} - {{standardlink.section.name}}</option>
                        </select>
                    </div>
                    <span v-if="errors.standardlink_id"><p class="text-red-500 text-xs font-semibold">{{errors.standardlink_id[0]}}</p></span>
                </div> 
            </div>
            <div class="tw-form-group w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="relation" class="tw-form-label">Select Student<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <select class="tw-form-control w-full" v-model="student_id" name="student_id" id="student_id">
                            <option value="" disabled>Select Student</option>
                            <option v-for="student in studentlist[this.standardlink_id]" v-bind:value="student.student_id">{{ student.student_name }}</option>
                        </select>
                        <span v-if="errors.student_id" class="text-red-500 text-xs font-semibold">{{errors.student_id[0]}}</span>
                    </div> 
                </div>
            </div>
        </div> -->

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="incident_date" class="tw-form-label">Incident Date<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2 flex items-center">
                        <datetime format="DD-MM-YYYY h:i:s" name="incident_date" v-model="incident_date" class="w-full rounded" id="incident_date"></datetime>
                        <svg id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current text-gray-500 mx-2"><g><path d="m144 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m144 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m144 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m467 65h-36v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-36c-24.813 0-45 20.187-45 45v332c0 24.813 20.187 45 45 45h422c24.813 0 45-20.187 45-45 0-9.682 0-323.575 0-332 0-24.813-20.187-45-45-45zm-437 45c0-8.271 6.729-15 15-15h36v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h36c8.271 0 15 6.729 15 15v59h-452zm437 347h-422c-8.271 0-15-6.729-15-15v-243h452v243c0 8.271-6.729 15-15 15z"></path></g></svg>
                    </div>
                    <span v-if="errors.incident_date" class="text-red-500 text-xs font-semibold">{{errors.incident_date[0]}}</span>
                </div> 
            </div>

            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="relation" class="tw-form-label">Select Teacher<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <select class="tw-form-control w-full" v-model="teacher_id" name="teacher_id" id="teacher_id">
                            <option value="" disabled>Select Teacher</option>
                            <option v-for="teacher in teacherlist" v-bind:value="teacher.teacher_id">{{ teacher.teacher_name }}</option>
                        </select>
                        <span v-if="errors.teacher_id" class="text-red-500 text-xs font-semibold">{{errors.teacher_id[0]}}</span>
                    </div> 
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="action_taken" class="tw-form-label">Action Taken<span class="text-red-500">*</span></label>
                    </div>
                    <div class="flex">
                        <div class="w-1/2 flex items-center tw-form-control mr-2 lg:mr-8 md:mr-8"> 
                            <input type="radio" v-model="action_taken" name="action_taken" id="1" value="1">
                            <span class="text-sm mx-1">Yes</span>
                        </div>
                        <div class="w-1/2 flex items-center tw-form-control lg:mr-8 md:mr-8"> 
                            <input type="radio" v-model="action_taken" name="action_taken" id="0" value="0">
                            <span class="text-sm mx-1">No</span>
                        </div>
                    </div>
                    <span v-if="errors.action_taken" class="text-red-500 text-xs font-semibold">{{errors.action_taken[0]}}</span>
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="notify_parents" class="tw-form-label">Notify Parents<span class="text-red-500">*</span></label>
                    </div>
                    <div class="flex">
                        <div class="w-1/2 flex items-center tw-form-control mr-2 lg:mr-8 md:mr-8"> 
                            <input type="radio" v-model="notify_parents" name="notify_parents" id="1" value="1">
                            <span class="text-sm mx-1">Yes</span>
                        </div>
                        <div class="w-1/2 flex items-center tw-form-control lg:mr-8 md:mr-8"> 
                            <input type="radio" v-model="notify_parents" name="notify_parents" id="0" value="0">
                            <span class="text-sm mx-1">No</span>
                        </div>
                    </div>
                    <span v-if="errors.notify_parents" class="text-red-500 text-xs font-semibold">{{errors.notify_parents[0]}}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="incident_detail" class="tw-form-label">Incident Detail<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" class="tw-form-control w-full" v-model="incident_detail" id="incident_detail" name="incident_detail" rows="3"></textarea>
                    </div>
                    <span v-if="errors.incident_detail" class="text-red-500 text-xs font-semibold">{{errors.incident_detail[0]}}</span>
                </div>
            </div>

            <div class="tw-form-group w-full lg:w-1/3">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="attachments" class="tw-form-label">Attachments</label>
                    </div>
                    <div class="mb-2">
                        <input type="file" name="attachments" @change="OnFileSelected" id="attachments" class="tw-form-control w-full">
                    </div>
                    <span v-if="errors.attachments" class="text-red-500 text-xs font-semibold">{{errors.attachments[0]}}</span>
                </div> 
            </div>
        </div>

        <div class="my-6">
            <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="submitForm()">Submit</a>
            <a href="#" class="btn btn-reset reset-btn" @click="resetForm()">Reset</a>
        </div>
    </div>
</template>

<script> 
    import datetime from 'vuejs-datetimepicker';
    import Multiselect from 'vue-multiselect'
    // register globally
    Vue.component('multiselect', Multiselect)
    export default {
        props:['url' , 'ref_name'],
        components: {
            Multiselect,
            datetime,
        },
        data(){
            return {
                standardLinklist:[],
                studentlist:[],
                teacherlist:[],
                standardlink_id:'',
                student_id:'',
                incident_date:'',
                teacher_id:'',
                incident_detail:'',
                response:'',
                action_taken:'',
                attachments:'',
                notify_parents:'',
                is_seen:'',
                errors:[],
                success:null,
            }
        },
        methods:
        {
            getData()
            {
                axios.get('/admin/discipline/list').then(response => {
                    //this.list = response.data;
                    this.teacherlist      = response.data.teacherlist;
                    //console.log(this.teacherlist)
                    //this.setData(); 
                });
            },

            setData()
            {
                if(Object.keys(this.list).length>0)
                {
                    this.standardLinklist = this.list.standardLinklist;
                    this.studentlist      = this.list.studentlist;
                    this.teacherlist      = this.list.teacherlist;
                }
            },

            resetForm()
            {
                //this.standardlink_id='';
                //this.student_id='';
                this.incident_date='';
                this.teacher_id='';
                this.incident_detail='';
                this.response='';
                this.action_taken='';
                this.attachments='';
                this.notify_parents='';
            }, 

            OnFileSelected(event)
            {
                this.attachments = event.target.files[0];
            },

            submitForm()
            {
                this.errors=[];
                this.success=null; 

                let formData=new FormData(); 

                formData.append('ref_name',this.ref_name);
                //formData.append('standardlink_id',this.standardlink_id);
                //formData.append('student_id',this.student_id);
                formData.append('incident_date',this.incident_date);
                formData.append('teacher_id',this.teacher_id);
                formData.append('incident_detail',this.incident_detail);
                formData.append('response',this.response);
                formData.append('action_taken',this.action_taken);
                formData.append('attachments',this.attachments);
                formData.append('notify_parents',this.notify_parents);
                formData.append('is_seen',this.is_seen);

                axios.post('/admin/discipline/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {   
                    this.success = response.data.success;
                    //this.resetForm();
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },
        },
    
        created()
        {
            //
            this.getData();
        }
    }
</script>