<template>
    <div class="bg-white shadow px-4 py-3">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
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
                            <option v-for="item in categorylist" v-bind:value="item.id">{{ item.display_name }}</option>
                        </select>
                    </div>
                    <span v-if="errors.category" class="text-red-500 text-xs font-semibold">{{errors.category[0]}}</span>
                </div> 
            </div>
            <div class="py-12">
                <a href=# class="bg-blue-500 rounded text-sm text-white px-2 py-1 whitespace-no-wrap" @click="showCategory()">Add New Category</a>
            </div>
        </div>

        <div v-if="this.show == 'add'" class="modal modal-mask">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full max-w-md px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>Add Category</h2>
                        <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="flex flex-col">
                            <div class="w-full lg:w-1/4"> 
                                <label for="name" class="tw-form-label">Name<span class="text-red-500">*</span></label>
                            </div>
                            <div class="my-2 w-full">
                                <input type="text" class="tw-form-control w-full" id="name" v-model="name" name="name" Placeholder="Name">
                                <span v-if="errors.name" class="text-red-500 text-xs font-semibold">{{errors.name[0]}}</span>
                            </div>
                        </div>
                    </div>
                    <div class="my-6">
                        <a href="#" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="addCategory()">Submit</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row w-full lg:w-3/5">
            <div class="tw-form-group w-full lg:w-3/4">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="cover_image" class="tw-form-label">Cover Photo<span class="text-red-500">*</span></label>
                    </div>
                    <div class="mb-2">
                        <input type="file" name="cover_image" @change="OnFileSelected" id="cover_image" class="tw-form-control w-full">
                    </div>
                    <span v-if="errors.cover_image" class="text-red-500 text-xs font-semibold">{{errors.cover_image[0]}}</span>
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
                        <div class="text-gray-700 text-xs my-1" v-text="(300 - description.length)+'/'+300" style="text-align: right"></div>
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
        props:['url' , 'mode'],
        data(){
            return {
                user:[],
                page_name:'',
                description:'',
                category:'',
                cover_image:'',
                name:'',
                show:'',
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
                axios.get(this.url+'/'+this.mode+'/classwall/pageCategory/list').then(response => {
                    this.categorylist = response.data.data;
                });
            },

            resetForm()
            {
                this.page_name='';
                this.category='';
                this.description='';
                this.cover_image='';
            }, 

            showCategory()
            {
                this.show = 'add';
            },

            closeModal()
            {
                this.show = 0;
            },

            addCategory()
            {
                this.errors=[];
                this.success=null;

                let formData=new FormData(); 

                formData.append('name',this.name);

                axios.post(this.url+'/'+this.mode+'/classwall/pageCategory/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                    this.show = 0;
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
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

                axios.post(this.url+'/'+this.mode+'/classwall/page/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                    this.resetForm();
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
            //
            this.getData();
        }
    }
</script>

<style scoped>

    .modal-mask {
        position: fixed;
        z-index: 9998;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, .5);
        display: table;
        transition: opacity .3s ease;
    }

    .modal-wrapper {
        display: table-cell;
        vertical-align: middle;
        overflow:auto;
    }

    .modal-container {
        margin: 0px auto;
        padding: 20px 30px;
        background-color: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
        transition: all .3s ease;
        /*height: 550px;*/
        overflow:auto;
    }

    .modal-header h3 {
        margin-top: 0;
        color: #42b983;
    }

    .modal-body {
        margin: 20px 0;
    }

    .modal-default-button {
        float: right;
    }

    /*
     * The following styles are auto-applied to elements with
     * transition="modal" when their visibility is toggled
     * by Vue.js.
     *
     * You can easily play with the modal transition by editing
     * these styles.
     */

    .modal-enter {
        opacity: 0;
    }

    .modal-leave-active {
        opacity: 0;
    }

    .modal-enter .modal-container,
    .modal-leave-active .modal-container {
        -webkit-transform: scale(1.1);
        transform: scale(1.1);
    }

    .text-danger
    {
        color:red;
    }
</style>