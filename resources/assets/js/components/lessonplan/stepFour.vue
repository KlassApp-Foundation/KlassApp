<template>
    <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3 bg-white shadow" v-bind:class="[this.profile_tab==4?'block' :'hidden']">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{ this.success }}</div>

        <!-- start -->
        <div class="flex flex-col lg:flex-row md:flex-row">
            <div class="tw-form-group w-full">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="modification" class="tw-form-label">Modification</label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" name="modification" v-model="modification" id="modification" class="tw-form-control w-full" rows="3" placeholder="Enter Modification"></textarea>
                    </div>
                    <span v-if="errors.modification" class="text-red-500 text-xs font-semibold">{{ errors.modification[0] }}</span> 
                </div>
            </div>
        </div>
        <!--end-->

        <!-- start -->
        <div class="flex flex-col lg:flex-row md:flex-row">
            <div class="tw-form-group w-full">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="notes" class="tw-form-label">Notes</label>
                    </div>
                    <div class="mb-2">
                        <textarea type="text" name="notes" v-model="notes" id="notes" class="tw-form-control w-full" rows="3" placeholder="Enter Notes"></textarea>
                    </div>
                    <span v-if="errors.notes" class="text-red-500 text-xs font-semibold">{{ errors.notes[0] }}</span>
                </div>
            </div>
        </div>
        <!--end-->

        <portal-target name="submit-btn"></portal-target>
        <portal to="submit-btn">
            <div class="my-6">
                <a href="#" id="submit-btn" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="setTab('3')">Back</a>
                <a href="#" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitForm()">Submit</a>
                <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="resetForm()" v-if="this.type == 'add'">Reset</a>
                <input type="submit" class="hidden" id="submit_btn" value="Submit"> 
            </div>
        </portal>
    </div>
</template>

<script>
    import { bus } from "../../app";
    import VueQuillEditor from 'vue-quill-editor'
    import 'quill/dist/quill.core.css' // import styles
    import 'quill/dist/quill.snow.css' // for snow theme
    import 'quill/dist/quill.bubble.css' // for bubble theme

    export default {
        props:['url' , 'type' , 'id'],
        data(){
            return{
                lessonplan:[],
                lesson_id:'',
                profile_tab:'',
                modification:'',
                notes:'',
                option:{
                  theme: 'snow',
                  modules: {
                    toolbar: [
                      ['bold', 'italic', 'underline'],
                      [{ 'list': 'ordered' }, { 'list': 'bullet' }]
                    ]
                  },
                  placeholder: '', 
                },
                errors:[],
                success:null,
            }
        },
        
        methods:
        {
            getData()
            {
                if(this.type == 'edit')
                {
                    axios.get('/teacher/lessonplan/edit/list/'+this.id).then(response => {
                        this.lessonplan = response.data;
                        this.setData();
                        //console.log(this.lessonplan)
                    });
                }
            },

            setData()
            {
                if(Object.keys(this.lessonplan).length > 0 )
                {     
                    this.modification         = this.lessonplan.modification; 
                    this.notes                = this.lessonplan.notes; 
                } 
            },

            setTab(val)
            {
                this.profile_tab=val;
                bus.$emit("dataProfileTab", this.profile_tab);
            },

            resetForm()
            {
                this.modification   = '';  
                this.notes          = '';     
                this.errors         = [];
            },

            submitForm()
            {
                this.errors=[];
                this.success=null; 

                let formData=new FormData();
                                  
                formData.append('modification',this.modification); 
                formData.append('notes',this.notes);          
                 
                if(this.type == 'add')  
                {            
                    axios.post('/teacher/lessonplan/add/stepFour/'+this.lesson_id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => { 
                        $('#submit_btn').click();  
                    }).catch(error => {
                        this.errors = error.response.data.errors;
                    });
                }
                else if(this.type == 'edit') 
                {
                    axios.post('/teacher/lessonplan/edit/stepFour/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {  
                        $('#submit_btn').click();  
                    }).catch(error => {
                        this.errors = error.response.data.errors;
                    });
                }
            },
        },

        created()
        {
            this.getData();
            bus.$on("dataProfileTab", data => {
                if(data!='')
                {
                    this.profile_tab=data;                   
                }
            });
            bus.$on("lessonId", data => {
                if(data!='')
                {
                    this.lesson_id=data;                   
                }
            });
            bus.$on("message", data => {
                if(data!='')
                {
                    this.success=data;                   
                }
            });
        }
    }
</script>