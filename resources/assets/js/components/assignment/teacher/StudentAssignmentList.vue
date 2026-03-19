<template>
    <div class="relative">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
        <portal to="assignment_list">
            <div class="flex flex-wrap lg:flex-row items-center mb-5 justify-between">
                <div class="">
                    <h1 class="admin-h1 my-3 flex items-center">
                        <a :href="url+'/teacher/assignments'" title="Back" class="rounded-full bg-gray-100 p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px" viewBox="0 0 492 492" xml:space="preserve" width="512px" height="512px" class="w-3 h-3 fill-current text-gray-700"><g><g><g><path d="M464.344,207.418l0.768,0.168H135.888l103.496-103.724c5.068-5.064,7.848-11.924,7.848-19.124    c0-7.2-2.78-14.012-7.848-19.088L223.28,49.538c-5.064-5.064-11.812-7.864-19.008-7.864c-7.2,0-13.952,2.78-19.016,7.844 L7.844,226.914C2.76,231.998-0.02,238.77,0,245.974c-0.02,7.244,2.76,14.02,7.844,19.096l177.412,177.412 c5.064,5.06,11.812,7.844,19.016,7.844c7.196,0,13.944-2.788,19.008-7.844l16.104-16.112c5.068-5.056,7.848-11.808,7.848-19.008 c0-7.196-2.78-13.592-7.848-18.652L134.72,284.406h329.992c14.828,0,27.288-12.78,27.288-27.6v-22.788    C492,219.198,479.172,207.418,464.344,207.418z" data-original="#000000" fill="" class="active-path"></path></g></g></g></svg>
                        </a>
                        <span class="mx-3">Show Assignment</span>
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
                        <div class="flex flex-row">
                            <div class="w-8 py-3">
                                <input type="checkbox" name="showCompleted" id="showCompleted" v-model="showCompleted" class="tw-form-control w-full" @change="showCompletedAssignment($event)">
                            </div>
                            <div class="lg:w-full py-3">
                                <label for="showCompleted" class="tw-form-label">Show Completed</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </portal>

        <div class="">
            <div class="flex">
                <div class="flex items-center">
                    <span class="text-gray-700 font-medium mx-2">Viewers : </span>
                </div>
                <div>
                    <a :href="url+'/teacher/assignment/viewers/'+id"> <p>{{ viewers }}</p></a>
                </div>
            </div>
            <div class="flex flex-wrap custom-table my-3 overflow-auto">              
                <table class="w-full">
                    <thead class="bg-grey-light">
                        <tr class="border-b">
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Student </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Roll Number </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Assignment File </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Obtained Marks </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Submitted On </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Comments </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Marks Given On </th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Actions </th>
                        </tr>
                    </thead>   
                    <tbody v-if="this.assignments != ''">
                        <tr class="border-b" v-for="assignment in assignments">
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs">{{ assignment.user_name }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs">{{ assignment.roll_number }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <a href="#" @click="showFile(assignment.id)" title="Assignment File" v-if="assignment.assignment_file != ''">
                                    <svg id="Layer" enable-background="new 0 0 64 64" height="512" viewBox="0 0 64 64" width="512" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current text-black-500 mx-1"><path d="m30.586 45.414c.39.391.902.586 1.414.586s1.023-.195 1.414-.586l10-10c.781-.781.781-2.047 0-2.828s-2.047-.781-2.828 0l-6.586 6.586v-29.172c0-1.104-.896-2-2-2s-2 .896-2 2v29.172l-6.586-6.586c-.78-.781-2.048-.781-2.828 0-.781.781-.781 2.047 0 2.828z"></path><path d="m18 56h28c3.309 0 6-2.691 6-6v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 1.103-.897 2-2 2h-28c-1.103 0-2-.897-2-2v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 3.309 2.691 6 6 6z"></path></svg>
                                </a>
                                <p class="font-semibold text-xs" v-else> -- </p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs" v-if="assignment.obtained_marks != ''">{{ assignment.obtained_marks }}</p>
                                <p class="font-semibold text-xs" v-else> -- </p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs">{{ assignment.submitted_on }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs" v-if="assignment.comments != ''">{{ assignment.comments }}</p>
                                <p class="font-semibold text-xs" v-else> -- </p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-semibold text-xs" v-if="assignment.marks_given_on != ''">{{ assignment.marks_given_on }}</p>
                                <p class="font-semibold text-xs" v-else> -- </p>
                            </td>
                            <td class="py-3 px-2">
                                <div class="flex items-center">
                                    <a href="#" @click="showAddModal(assignment.id)" title="Add" v-if="assignment.status == 0">
                                        <svg class="w-4 h-4 fill-current text-black-500 mx-1" height="477pt" viewBox="-26 0 477 477.96064" width="477pt" xmlns="http://www.w3.org/2000/svg"><path d="m335.769531 235.914062v-127.589843c0-2.78125-1.15625-5.433594-3.191406-7.324219l-105.84375-98.324219c-1.847656-1.71875-4.28125-2.675781-6.804687-2.675781h-175.449219c-24.289063.0273438-43.972657 19.710938-44 44v335.792969c.027343 24.289062 19.710937 43.972656 44 44h155.382812c30.578125 45.125 87.140625 64.835937 139.140625 48.488281s87.109375-64.882812 86.363282-119.386719c-.746094-54.503906-37.167969-102.0625-89.597657-116.980469zm-105.097656-202.285156 70.976563 65.9375h-46.976563c-13.246094-.015625-23.984375-10.753906-24-24zm-186.191406 370.164063c-13.25-.015625-23.984375-10.753907-24-24v-335.792969c.015625-13.25 10.75-23.984375 24-24h166.191406v55.566406c.027344 24.289063 19.710937 43.972656 44 44h61.101563v112.417969c-34.90625-3.925781-69.832032 7.210937-96.023438 30.621094-26.191406 23.410156-41.160156 56.875-41.15625 92-.015625 16.925781 3.46875 33.671875 10.234375 49.1875zm257.503906 54.207031c-57.101563 0-103.394531-46.292969-103.394531-103.394531 0-57.101563 46.292968-103.394531 103.394531-103.394531 57.105469 0 103.394531 46.292968 103.394531 103.394531-.0625 57.078125-46.316406 103.328125-103.394531 103.394531zm0 0"/><path d="m354.488281 342.945312h-40.84375v-40.84375c0-5.523437-4.476562-10-10-10-5.523437 0-10 4.476563-10 10v40.84375h-40.84375c-5.523437 0-10 4.476563-10 10 0 5.523438 4.476563 10 10 10h40.84375v46.375c0 5.523438 4.476563 10 10 10 5.523438 0 10-4.476562 10-10v-46.375h40.84375c5.523438 0 10-4.476562 10-10 0-5.519531-4.476562-10-10-10zm0 0"/></svg>
                                    </a>

                                    <a href="#" @click="showEditModal(assignment.id)" title="Edit" v-else>
                                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 477.873 477.873" xml:space="preserve" class="w-4 h-4 fill-current text-black-500 mx-1"><g><g><path d="M392.533,238.937c-9.426,0-17.067,7.641-17.067,17.067V426.67c0,9.426-7.641,17.067-17.067,17.067H51.2 c-9.426,0-17.067-7.641-17.067-17.067V85.337c0-9.426,7.641-17.067,17.067-17.067H256c9.426,0,17.067-7.641,17.067-17.067 S265.426,34.137,256,34.137H51.2C22.923,34.137,0,57.06,0,85.337V426.67c0,28.277,22.923,51.2,51.2,51.2h307.2 c28.277,0,51.2-22.923,51.2-51.2V256.003C409.6,246.578,401.959,238.937,392.533,238.937z"></path></g></g> <g><g><path d="M458.742,19.142c-12.254-12.256-28.875-19.14-46.206-19.138c-17.341-0.05-33.979,6.846-46.199,19.149L141.534,243.937 c-1.865,1.879-3.272,4.163-4.113,6.673l-34.133,102.4c-2.979,8.943,1.856,18.607,10.799,21.585 c1.735,0.578,3.552,0.873,5.38,0.875c1.832-0.003,3.653-0.297,5.393-0.87l102.4-34.133c2.515-0.84,4.8-2.254,6.673-4.13 l224.802-224.802C484.25,86.023,484.253,44.657,458.742,19.142z M434.603,87.419L212.736,309.286l-66.287,22.135l22.067-66.202 L390.468,43.353c12.202-12.178,31.967-12.158,44.145,0.044c5.817,5.829,9.095,13.72,9.12,21.955 C443.754,73.631,440.467,81.575,434.603,87.419z"></path></g></g></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr class="border-b">
                            <td colspan="8">
                                <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="this.page_count>1">
                    <paginate v-model="page" :page-count="this.page_count" :page-range="3" :margin-pages="1" :click-handler="getData" :prev-text="'<'" :next-text="'>'" :container-class="'pagination'" :page-class="'page-item'" :prev-link-class="'prev'" :next-link-class="'next'"></paginate>
                </div>
            </div>
        </div>

        <div v-if="this.show == 'add' " class="modal modal-mask">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full  max-w-md px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>Submit Assignment Marks</h2>
                        <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="flex items-center">
                            <div class="w-full lg:w-1/4"> 
                                <label for="obtained_marks" class="tw-form-label">Obtained Marks<span class="text-red-500">*</span></label>
                            </div>
                            <div class="my-2 w-full lg:w-3/4">
                                <input type="text" name="obtained_marks" v-model="obtained_marks" id="obtained_marks" class="tw-form-control w-full" placeholder="Obtained Marks">
                                <span v-if="errors.obtained_marks" class="text-red-500 text-xs font-semibold">{{ errors.obtained_marks[0] }}</span>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-full lg:w-1/4"> 
                                <label for="comments" class="tw-form-label">Comments</label>
                            </div>
                            <div class="my-2 w-full lg:w-3/4">
                                <textarea type="text" name="comments" v-model="comments" id="comments" class="tw-form-control w-full" placeholder="Comments"></textarea>
                                <span v-if="errors.comments" class="text-red-500 text-xs font-semibold">{{ errors.comments[0] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="my-6">
                        <a href="#" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitAssignment()">Submit</a>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="this.show == 'edit' " class="modal modal-mask">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full  max-w-md px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>Edit Assignment Marks</h2>
                        <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="flex items-center">
                            <div class="w-full lg:w-1/4"> 
                                <label for="obtained_marks" class="tw-form-label">Obtained Marks<span class="text-red-500">*</span></label>
                            </div>
                            <div class="my-2 w-full lg:w-3/4">
                                <input type="text" name="obtained_marks" v-model="obtained_marks" id="obtained_marks" class="tw-form-control w-full">
                                <span v-if="errors.obtained_marks" class="text-red-500 text-xs font-semibold">{{ errors.obtained_marks[0] }}</span>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-full lg:w-1/4"> 
                                <label for="comments" class="tw-form-label">Comments</label>
                            </div>
                            <div class="my-2 w-full lg:w-3/4">
                                <textarea type="text" name="comments" v-model="comments" id="comments" class="tw-form-control w-full"></textarea>
                                <span v-if="errors.comments" class="text-red-500 text-xs font-semibold">{{errors.comments[0]}}</span>
                            </div>
                        </div>
                    </div>

                    <div class="my-6">
                        <a href="#" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="updateAssignment()">Submit</a>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="this.show == 'view'" class="modal modal-mask">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full  max-w-md px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>Assignment Files</h2>
                        <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="flex">
                            <div class="w-full lg:w-1/4">
                                <label for="date" class="tw-form-label"></label>
                            </div>
                            <div class="flex flex-wrap w-full lg:w-3/4">
                                <div class="m-1" v-for="list in assignment.assignment_file">
                                    <img :src="list.path" class="w-25 h-25">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props:['url' , 'id' , 'searchquery','viewers'],
        data () {
            return {
                assignments:[],
                assignment:[],
                showCompleted:'',
                show:0,
                obtained_marks:'',
                comments:'',
                student_assignment_id:'',
                search:'',
                params:{},
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
              axios.get('/teacher/assignment/show/list/'+this.id+'?showCompleted='+this.showCompleted+'&search='+this.search+'&page='+page).then(response => {
                this.assignments    = response.data.data;
                this.page_count     = response.data.meta.last_page;
                this.total          = response.data.meta.total;
                //console.log(this.assignments);    
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

            showCompletedAssignment(e)
            {
                this.getData();
            },

            showAddModal(id)
            {
                this.student_assignment_id = id;
                this.show = 'add';
            },

            getEditData(student_assignment_id)
            {
                axios.get('/teacher/assignment/show/edit/list/'+student_assignment_id).then(response => {
                    this.obtained_marks = response.data.obtained_marks;
                    this.comments       = response.data.comments;
                });
            },

            showFile(student_assignment_id)
            {
                this.show = 'view';
                axios.get('/teacher/assignment/show/edit/list/'+student_assignment_id).then(response => {
                    this.assignment = response.data;
                });
            },

            showEditModal(id)
            {
                this.getEditData(id);
                this.student_assignment_id = id;
                this.show = 'edit';
            },

            closeModal()
            {
                this.show = 0;
            },

            submitAssignment()
            {
                this.errors=[];
                this.success=null; 

                let formData = new FormData();
                        
                formData.append('assignment_id',this.id);        
                formData.append('obtained_marks',this.obtained_marks);        
                formData.append('comments',this.comments);        
                             
                axios.post('/teacher/assignment/addMarks/'+this.student_assignment_id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                    this.closeModal();
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            updateAssignment()
            {
                this.errors=[];
                this.success=null; 

                let formData = new FormData();
                        
                formData.append('assignment_id',this.id);        
                formData.append('obtained_marks',this.obtained_marks);        
                formData.append('comments',this.comments);        
                             
                axios.post('/teacher/assignment/editMarks/'+this.student_assignment_id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                    this.closeModal();
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