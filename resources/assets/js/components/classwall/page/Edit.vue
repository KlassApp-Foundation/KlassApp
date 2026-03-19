<template>
    <div class="bg-white shadow px-4 py-3">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

        <div class="my-6">
            <div class="">
                <img :src="this.cover_image_display" class="img-responsive w-32 h-32">
                <div class="mx-2">
                    <label class="tw-label cursor-pointer text-xs text-gray-600"> Change Cover Photo
                        <input type="file" size="60" name="cover_image" id="cover_image" @change="OnFileSelected">
                    </label> 
                </div>
                <span v-if="errors.cover_image" class="text-red-500 text-xs font-semibold">{{errors.cover_image[0]}}</span>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row w-full lg:w-3/5">
            <div class="tw-form-group w-full lg:w-3/4">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="page_name" class="tw-form-label">Page Name<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <input type="text" class="tw-form-control w-full" id="page_name" v-model="page_name" name="page_name" Placeholder="Page Name" :maxlength="25">
                        <div class="text-gray-700 text-xs my-1" v-text="(25 - page_name.length)+'/'+25" style="text-align: right"></div>
                    </div>
                    <span v-if="errors.page_name" class="text-red-500 text-xs font-semibold">{{errors.page_name[0]}}</span>
                </div> 
            </div>
        </div>

        <div class="flex flex-col lg:flex-row w-full lg:w-3/5">
            <div class="tw-form-group w-full lg:w-3/4">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="category" class="tw-form-label">Category<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <select v-model="category" name="category" id="category" class="tw-form-control w-full">
                            <option value="" disabled>Select Category</option>
                            <option v-for="item in categorylist" v-bind:value="item.name">{{ item.display_name }}</option>
                        </select>
                    </div>
                    <span v-if="errors.category" class="text-red-500 text-xs font-semibold">{{errors.category[0]}}</span>
                </div> 
            </div>
        </div>

        <div class="flex flex-col lg:flex-row w-full lg:w-3/5">
            <div class="tw-form-group w-full lg:w-3/4">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="description" class="tw-form-label">Description<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <quill-editor ref="myQuillEditor" v-model="description" name="description" :options="option"/>
                        <div class="text-gray-700 text-xs my-1" v-text="(500 - description.length)+'/'+500" style="text-align: right"></div>
                    </div>
                    <span v-if="errors.description" class="text-red-500 text-xs font-semibold">{{errors.description[0]}}</span>
                </div> 
            </div>
        </div>
        <input type="hidden" v-if="this.description != null" name="description" :value="this.description">

        <div class="my-6">
            <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="submitForm()">Submit</a>
            <a href="#" class="btn btn-reset reset-btn" @click="resetForm()">Reset</a>
        </div>
    </div>
</template>

<script>
    import VueQuillEditor from 'vue-quill-editor'
    import 'quill/dist/quill.core.css' // import styles
    import 'quill/dist/quill.snow.css' // for snow theme
    import 'quill/dist/quill.bubble.css' // for bubble theme 
    export default {
        props:['url' , 'id' , 'mode'],
        data(){
            return {
                page:[],
                page_name:'',
                description:'',
                category:'',
                cover_image:'',
                cover_image_display:'',
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
                categorylist:[],
                errors:[],
                success:null,
            }
        },

        methods:
        {
            getData()
            {
                axios.get(this.url+'/'+this.mode+'/classwall/page/editList/'+this.id).then(response => {
                    this.page = response.data;
                    this.setData();
                });

                axios.get(this.url+'/'+this.mode+'/classwall/pageCategory/list').then(response => {
                    this.categorylist = response.data.data;
                });
            },

            setData()
            {
                if(Object.keys(this.page).length > 0)
                {
                    this.page_name            = this.page.page_name;
                    this.description          = this.page.description;
                    this.category             = this.page.category;
                    this.cover_image_display  = this.page.cover_image;
                }
            },

            submitForm()
            {
                this.errors=[];
                this.success=null;

                let formData=new FormData(); 

                formData.append('page_name',this.page_name);          
                formData.append('category',this.category);
                formData.append('description',this.description);          
                formData.append('cover_image',this.cover_image);

                axios.post(this.url+'/'+this.mode+'/classwall/page/edit/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            OnFileSelected(event)
            {
                this.cover_image = event.target.files[0];
            },
        },
      
        created()
        {
            this.getData();
        }
    }
</script>

<style scoped>
    .tw-label{
        color:#3492e2;
    }

    .tw-label input[type="file"] {
        display: none;
    }
</style>