<template>
    <div class="relative">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{ this.success }}</div>
        <portal to="student_homework_list">
            <div class="flex flex-wrap lg:flex-row items-center mb-5 justify-between">
                <div class="">
                    <h1 class="admin-h1 my-3 flex items-center">
                        <a :href="url+'/teacher/homeworks'" title="Back" class="rounded-full bg-gray-100 p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" xml:space="preserve" width="512px" height="512px" class="w-3 h-3 fill-current text-gray-700"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124    c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844 L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412 c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008 c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788    C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" fill="" class="active-path"></path></g></g></g></svg>
                        </a>
                        <span class="mx-3">Show Home Work</span>
                    </h1>
                </div>
         
                <div class="relative flex items-center w-8/12 lg:w-3/4 md:w-1/4 justify-end">
                    <div class="flex items-center">
                        <div class="">
                            <div class="flex items-center mx-2">
                                <div class="search relative mx-2">
                                    <input type="text" name="search" v-model="search" class="border px-10 py-1 text-sm border-gray-400 rounded bg-white shadow" placeholder="Search">  
                                    <span class="input-group-btn absolute left-0 px-3 py-2 top-0">                  
                                        <a href="#" @click="searchList()">
                                            <svg class="w-4 h-4 fill-current text-gray-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30.239px" height="30.239px" viewBox="0 0 30.239 30.239" style="enable-background:new 0 0 30.239 30.239;" xml:space="preserve"><g><path d="M20.194,3.46c-4.613-4.613-12.121-4.613-16.734,0c-4.612,4.614-4.612,12.121,0,16.735 c4.108,4.107,10.506,4.547,15.116,1.34c0.097,0.459,0.319,0.897,0.676,1.254l6.718,6.718c0.979,0.977,2.561,0.977,3.535,0 c0.978-0.978,0.978-2.56,0-3.535l-6.718-6.72c-0.355-0.354-0.794-0.577-1.253-0.674C24.743,13.967,24.303,7.57,20.194,3.46z M18.073,18.074c-3.444,3.444-9.049,3.444-12.492,0c-3.442-3.444-3.442-9.048,0-12.492c3.443-3.443,9.048-3.443,12.492,0 C21.517,9.026,21.517,14.63,18.073,18.074z"/></g></svg>
                                        </a>
                                    </span>
                                </div>
                                <div class="date-select date-select_none dashboard-reset mx-1 lg:mx-0 md:mx-0">
                                    <a href="#" @click="resetForm()" id="do-reset" class="text-sm border bg-gray-100 text-grey-darkest py-1 px-4">Reset</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </portal>
        <div class="">
            <ul class="flex flex-col lg:flex-row md:flex-row list-reset leading-loose my-2 text-sm" v-if="Object.keys(this.homework).length > 0">
                <li class="flex lg:px-4 md:px-2 py-1">
                    <div class="flex items-center">
                        <span class="text-gray-700 font-medium mx-2">Description : </span>
                    </div>
                    <div>
                        <p v-html="homework.description">{{ homework.description }}</p>
                    </div>
                </li>
            </ul>
            <ul class="flex flex-col lg:flex-row md:flex-row list-reset leading-loose my-2 text-sm" v-if="Object.keys(this.homework).length > 0">
                <li class="flex lg:px-4 md:px-2 py-1">
                    <div class="flex items-center">
                        <span class="text-gray-700 font-medium mx-2">Class : </span>
                    </div>
                    <div>
                        <p>{{ homework.standardLink_name }}</p>
                    </div>
                </li>
                <li class="flex lg:px-4 md:px-2 py-1">
                    <div class="flex items-center">
                        <span class="text-gray-700 font-medium mx-2">Date : </span>
                    </div>
                    <div>
                        <p>{{ homework.show_date }}</p>
                    </div>
                </li>
                <li class="flex lg:px-4 md:px-2 py-1">
                    <div class="flex items-center">
                        <span class="text-gray-700 font-medium mx-2">Attachment : </span>
                    </div>
                    <div>
                        <a :href="homework.attachment" target="_blank" v-if="homework.attachment != ''" title="Attachment">
                            <svg class="w-5 h-5 fill-current text-black-500 mx-1" id="Layer" enable-background="new 0 0 64 64" height="512" viewBox="0 0 64 64" width="512" xmlns="http://www.w3.org/2000/svg"><path d="m30.586 45.414c.39.391.902.586 1.414.586s1.023-.195 1.414-.586l10-10c.781-.781.781-2.047 0-2.828s-2.047-.781-2.828 0l-6.586 6.586v-29.172c0-1.104-.896-2-2-2s-2 .896-2 2v29.172l-6.586-6.586c-.78-.781-2.048-.781-2.828 0-.781.781-.781 2.047 0 2.828z"/><path d="m18 56h28c3.309 0 6-2.691 6-6v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 1.103-.897 2-2 2h-28c-1.103 0-2-.897-2-2v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 3.309 2.691 6 6 6z"/></svg>
                        </a>
                        <p class="font-semibold text-xs" v-else> -- </p>
                    </div>
                </li>
                <li class="flex lg:px-4 md:px-2 py-1">
                    <div class="flex items-center">
                        <span class="text-gray-700 font-medium mx-2">Pending Count : </span>
                    </div>
                    <div>
                        <p>{{ homework.pending_count }}</p>
                    </div>
                </li>
                <li class="flex lg:px-4 md:px-2 py-1">
                    <div class="flex items-center">
                        <span class="text-gray-700 font-medium mx-2">Viewers : </span>
                    </div>
                    <div>
                       <a :href="url+'/'+mode+'/homework/viewers/'+id"> <p>{{ homework.viewers }}</p></a>
                    </div>
                </li>
            </ul>
            <div class="flex flex-wrap custom-table my-3 overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-grey-light">
                        <tr class="border-b">
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> User Name </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Homework File </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Status </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Submitted On </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Checked By </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Checked On </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker" width="5%"> Comments </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker" width="5%"> Student Reply </th>
                        </tr>
                    </thead>   
                    <tbody v-if="Object.keys(this.studenthomeworklist).length > 0">
                        <tr class="border-b" v-for="studenthomework in studenthomeworklist">
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs">
                                    <a :href="'/admin/student/show/'+studenthomework.user_name" target="_blank" v-if="mode == 'admin'">{{ studenthomework.user_fullname }}</a>
                                    <a href="#" v-else>{{ studenthomework.user_fullname }}</a>
                                </p>
                            </td>
                            <td class="py-3 px-2">
                                <a href="#" class="font-semibold text-xs" @click="show(studenthomework.id)">View Files</a>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs">{{ studenthomework.status }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs">{{ studenthomework.submitted_on }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <div v-if="mode == 'admin'">
                                    <p class="font-semibold text-xs" v-if="studenthomework.status != 'Unchecked'"><a :href="url+'/'+mode+'/teacher/show/'+studenthomework.checked_by_name">{{ studenthomework.checked_by }}</a></p>
                                    <p class="font-semibold text-xs" v-else>--</p>
                                </div>
                                <div v-else>
                                    <p class="font-semibold text-xs" v-if="studenthomework.status != 'Unchecked'">{{ studenthomework.checked_by }}</p>
                                    <p class="font-semibold text-xs" v-else>--</p>
                                </div>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs" v-if="studenthomework.status != 'Unchecked'">{{ studenthomework.checked_on }}</p>
                                <p class="font-semibold text-xs" v-else>--</p>
                            </td>
                            <td class="py-3 px-2">
                                <div class="flex items-center">
                                    <a href="#" @click="showEdit(studenthomework.id)" title="Edit" v-if="studenthomework.status == 'Unchecked'">
                                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 477.873 477.873" xml:space="preserve" class="w-4 h-4 fill-current text-black-500 mx-1"><g><g><path d="M392.533,238.937c-9.426,0-17.067,7.641-17.067,17.067V426.67c0,9.426-7.641,17.067-17.067,17.067H51.2 c-9.426,0-17.067-7.641-17.067-17.067V85.337c0-9.426,7.641-17.067,17.067-17.067H256c9.426,0,17.067-7.641,17.067-17.067 S265.426,34.137,256,34.137H51.2C22.923,34.137,0,57.06,0,85.337V426.67c0,28.277,22.923,51.2,51.2,51.2h307.2 c28.277,0,51.2-22.923,51.2-51.2V256.003C409.6,246.578,401.959,238.937,392.533,238.937z"></path></g></g> <g><g><path d="M458.742,19.142c-12.254-12.256-28.875-19.14-46.206-19.138c-17.341-0.05-33.979,6.846-46.199,19.149L141.534,243.937 c-1.865,1.879-3.272,4.163-4.113,6.673l-34.133,102.4c-2.979,8.943,1.856,18.607,10.799,21.585 1.735,0.578,3.552,0.873,5.38,0.875c1.832-0.003,3.653-0.297,5.393-0.87l102.4-34.133c2.515-0.84,4.8-2.254,6.673-4.13 l224.802-224.802C484.25,86.023,484.253,44.657,458.742,19.142z M434.603,87.419L212.736,309.286l-66.287,22.135l22.067-66.202 L390.468,43.353c12.202-12.178,31.967-12.158,44.145,0.044c5.817,5.829,9.095,13.72,9.12,21.955 C443.754,73.631,440.467,81.575,434.603,87.419z"></path></g></g></svg>
                                    </a>
                                    <p v-if="studenthomework.status == 'Checked'">{{ studenthomework.comments }}</p>
                                </div>
                            </td>
                             <td class="py-3 px-2">
                                <p class="font-semibold text-xs" v-if="studenthomework.reply_comment != ''">{{ studenthomework.reply_comment }}</p>
                                <p class="font-semibold text-xs" v-else>--</p>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr class="border-b">
                            <td colspan="9">
                                <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-for="studenthomework in studenthomeworklist">
            <div v-if="tab == studenthomework.id+'_edit'" class="modal modal-mask">
                <div class="modal-wrapper px-4">
                    <div class="modal-container w-full max-w-md px-8 mx-auto">
                        <div class="modal-header flex justify-between items-center">
                            <h2>Edit Student Homework</h2>
                            <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="flex flex-col lg:flex-row">
                                <div class="w-full lg:w-1/4">
                                    <label for="comments" class="tw-form-label">Comments</label>
                                </div>
                                <div class="w-full lg:w-3/4">
                                    <textarea type="text" name="comments" v-model="comments" class="tw-form-control w-full" id="comments" placeholder="Comments" rows="3"></textarea>
                                    <span v-if="errors.comments" class="text-red-500 text-xs font-semibold">{{errors.comments[0]}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="my-6">
                            <a href="#" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitStatus(studenthomework.id)">Submit</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="tab == 'files'" class="modal modal-mask">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full max-w-md px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>View Homework Files</h2>
                        <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="flex">
                            <div class="flex flex-wrap w-full lg:w-3/4">
                                <div class="m-1" v-for="list in this.attachments">
                                    <img :src="list.path" class="w-30 h-30">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="this.page_count>1">
            <paginate v-model="page" :page-count="this.page_count" :page-range="3" :margin-pages="1" :click-handler="getData" :prev-text="'<'" :next-text="'>'" :container-class="'pagination'" :page-class="'page-item'" :prev-link-class="'prev'" :next-link-class="'next'"></paginate>
        </div>
    </div>
</template>

<script>
    export default {
        props:['url' , 'id','mode'],
        data () {
            return {
                studenthomeworklist:[],
                homework:[],
                tab:0,
                attachments:[],
                comments:'',
                search:'',
                errors:[],
                success:null, 
                total: 0,
                page: 1,
                page_count: 0,
            }
        },

        methods:
        {
            getData(page=1)
            {
                axios.get('/'+this.mode+'/homework/edit/list/'+this.id).then(response => {
                    this.homework           = response.data;
                    //console.log(this.homework);
                });

                axios.get('/'+this.mode+'/studenthomeworks/'+this.id+'?page='+page+'&search='+this.search).then(response => {
                    this.studenthomeworklist = response.data.data;
                    this.page_count     = response.data.meta.last_page;
                    this.total          = response.data.meta.total;
                    //console.log(this.studenthomeworklist);     
                });
            },

            searchList()
            {
                this.getData();
            },

            resetForm()
            {
                this.search = '';
                this.getData();
            },

            getStudentHomework(id)
            {
                axios.get('/'+this.mode+'/studenthomework/show/'+id).then(response => {
                    this.attachments        = response.data.attachments;
                    //console.log(this.attachments);     
                });
            },

            show(id)
            {
                this.getStudentHomework(id);
                this.tab = 'files';
            },

            showEdit(id)
            {
                this.tab = id+'_edit';
            },

            closeModal()
            {
                this.tab = 0;
            },

            submitStatus(id)
            {
                this.errors=[];
                this.success=null;

                let formData=new FormData();

                formData.append('comments',this.comments);    

                axios.post('/'+this.mode+'/studenthomework/edit/'+id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                    this.tab=0;
                    window.location.reload();
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
        /* height: 550px;*/
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
</style>