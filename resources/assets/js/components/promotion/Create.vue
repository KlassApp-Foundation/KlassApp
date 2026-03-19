<template>
    <div class="bg-white shadow px-4 pb-4 pt-1">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{ this.success }}</div>
        <div class="my-5">
            <div class="tw-form-group w-full lg:w-2/5 md:w-2/5">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="curr_academic_year_id" class="tw-form-label">Current Academic Year</label>
                    </div>
                    <div class="mb-2">
                        <select class="tw-form-control w-full" id="curr_academic_year_id" v-model="curr_academic_year_id" name="curr_academic_year_id"> 
                            <option value="" disabled>Select Academic Year</option>
                            <option v-bind:value="curr_academic_yearlist.id">{{ curr_academic_yearlist.name }}</option>
                        </select>
                    </div> 
                    <span v-if="errors.curr_academic_year_id" class="text-red-500 text-xs font-semibold">{{ errors.curr_academic_year_id[0] }}</span>
                </div>
            </div>   
        </div>
        <div class="my-5">
            <div class="tw-form-group w-full lg:w-2/5 md:w-2/5">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="curr_standardlink_id" class="tw-form-label">Current Class</label>
                    </div>
                    <div class="mb-2">
                        <select class="tw-form-control w-full" id="curr_standardlink_id" v-model="curr_standardlink_id" name="curr_standardlink_id">
                            <option value="" disabled>Select Class</option>
                            <option v-for="standardlink in standardLinklist" v-bind:value="standardlink.id">{{ standardlink.standard_section }}</option>
                        </select>
                    </div>
                    <span v-if="errors.curr_standardlink_id" class="text-red-500 text-xs font-semibold">{{ errors.curr_standardlink_id[0] }}</span>
                </div> 
            </div>
        </div>
    
        <div class="my-5">
            <div class="tw-form-group w-full lg:w-2/5 md:w-2/5">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="exam_id" class="tw-form-label">Exam</label>
                    </div>
                    <div class="mb-2">
                        <select class="tw-form-control w-full" id="exam_id" v-model="exam_id" name="exam_id">
                            <option value="" disabled>Select Exam</option>
                            <option v-for="exam in examlist[this.curr_standardlink_id]" v-bind:value="exam.id">{{ exam.name }} <span>{{ exam.subjects }}</span></option>
                        </select>
                    </div>
                    <span v-if="errors.exam_id" class="text-red-500 text-xs font-semibold">{{ errors.exam_id[0] }}</span>
                </div> 
            </div>
        </div> 

        <div class="mt-3" v-if="this.downloadpath!=null">
            <a :href="this.downloadpath" class="btn btn-submit custom-green text-white rounded px-3 py-1 mr-3 text-sm font-medium" target="_blank" @click="enableDiv('importfile')">Click Here To Download</a>
        </div>
        <div class="mt-3" v-else>
            <a href="#" id="sample" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitForm()">Save</a>
            <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>
        </div>

        <!--import-->
        <div class="hidden" id="importfile">
            <div class="my-5">
                <div class="tw-form-group w-2/5">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="exam_id" class="tw-form-label">Import File</label>
                        </div>
                        <div class="mb-2">
                            <input type="file" name="promotion_file" @change="OnFileSelected" id="promotion_file" class="tw-form-control w-full">
                        </div>
                    </div>
                    <span v-if="errors.promotion_file" class="text-red-500 text-xs font-semibold">{{ errors.promotion_file[0] }}</span>
                </div>
            </div> 

            <div class="my-5">
                <div class="tw-form-group w-2/5">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="next_academic_year_id" class="tw-form-label">Next Academic Year</label>
                        </div>
                        <div class="mb-2">
                            <select class="tw-form-control w-full" id="next_academic_year_id" v-model="next_academic_year_id" name="next_academic_year_id">  
                                <option value="" disabled>Select Academic Year</option>
                                <option v-bind:value="next_academic_yearlist.id">{{ next_academic_yearlist.name }}</option>
                            </select>
                            <span v-if="errors.next_academic_year_id" class="text-red-500 text-xs font-semibold">{{ errors.next_academic_year_id[0] }}</span>
                        </div> 
                    </div>
                </div>   
            </div>

            <div class="my-5">
                <div class="tw-form-group w-2/5">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="next_standardlink_id" class="tw-form-label">Next Class</label>
                        </div>
                        <div class="mb-2">
                            <select class="tw-form-control w-full" id="next_standardlink_id" v-model="next_standardlink_id" name="next_standardlink_id">
                                <option value="" disabled>Select Next Class</option>
                                <option v-for="standardlink in next_standardLinklist" v-bind:value="standardlink.id">{{ standardlink.standard_section }}</option>
                            </select>
                        </div>
                        <span v-if="errors.next_standardlink_id" class="text-red-500 text-xs font-semibold">{{ errors.next_standardlink_id[0] }}</span>
                    </div> 
                </div>
            </div>

            <div class="mt-3">
                <a href="#" id="import" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="importList()">Submit</a>
            </div>
        </div>
        <!--import end-->
    </div>
</template>


<script>
    export default {
        props:['url'],
        data(){
            return{
                list:[],
                standardLinklist:[],
                next_standardLinklist:[],
                curr_academic_yearlist:[],
                next_academic_yearlist:[],
                examlist:[],
                curr_standardlink_id:'',
                curr_academic_year_id:'',
                exam_id:'',
                downloadpath:null,
                next_academic_year_id:'',
                next_standardlink_id:'',
                promotion_file:'',
                errors:[],
                success:null,
            }
        },

        created()
        {
            this.getData();
        },
        
        methods:
        { 
            getData()
            {
                axios.get('/admin/promotion/list').then(response => {
                    this.list = response.data;
                    //console.log(this.list)
                    this.setData();   
                });
            }, 

            setData()
            {
                if(Object.keys(this.list).length>0)
                {
                    if(this.list.next_academic_yearlist == null)
                    {
                        alert("Please Create Next Academic Year");
                    }
                    this.curr_academic_year_id  = this.list.curr_academic_yearlist.id;
                    this.curr_academic_yearlist = this.list.curr_academic_yearlist;
                    this.next_academic_year_id  = this.list.next_academic_yearlist.id;
                    this.next_academic_yearlist = this.list.next_academic_yearlist;
                    this.standardLinklist       = this.list.standardLinklist;
                    this.next_standardLinklist  = this.list.next_standardLinklist;
                    this.examlist               = this.list.examlist;
                }
            },

            reset()
            {
                this.curr_standardlink_id='';
                this.exam_id='';        
            }, 

            submitForm()
            {
                this.errors=[];
                this.success=null; 

                let formData=new FormData();

                formData.append('curr_academic_year_id',this.curr_academic_year_id);
                formData.append('curr_standardlink_id',this.curr_standardlink_id);               
                formData.append('exam_id',this.exam_id);          
                       
                axios.post('/admin/promotion/export',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {
                    this.downloadpath = response.data.path;
                    this.success = response.data.success;
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            }, 

            enableDiv(id)
            {
                $('#'+id).removeClass('hidden').addClass('block');
            },

            OnFileSelected(event)
            {
                this.promotion_file = event.target.files[0];
            },  

            importList()
            {
                this.errors=[];
                this.success=null; 

                let formData=new FormData();

                formData.append('promotion_file',this.promotion_file);
                formData.append('curr_academic_year_id',this.curr_academic_year_id);
                formData.append('curr_standardlink_id',this.curr_standardlink_id);               
                formData.append('exam_id',this.exam_id);
                formData.append('next_academic_year_id',this.next_academic_year_id);
                formData.append('next_standardlink_id',this.next_standardlink_id); 

                axios.post('/admin/promotion/import',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {  
                    this.success = response.data.success;
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            }, 
        }, 
    }
</script>