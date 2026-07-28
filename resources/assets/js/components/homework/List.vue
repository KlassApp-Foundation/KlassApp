<template>
    <div class="relative">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
        <portal to="add_homework">
            <div class="flex flex-wrap lg:flex-row justify-between">
                <div class="">
                    <h1 class="admin-h1 my-3">Home Work</h1>
                </div>
                <div class="relative flex items-center w-8/12 lg:w-1/4 md:w-1/4 justify-end">
                    <div class="flex items-center w-full justify-end">
                        <div class="flex items-center lg:mx-3">
                            <div class="w-8">
                                <input type="checkbox" name="showPast" id="showPast" v-model="showPast" class="tw-form-control w-full" @change="showPastHomework($event)">
                            </div>
                            <div class="">
                                <label for="showPast" class="tw-form-label">Show Past</label>
                            </div>
                        </div>
                        <a :href="url+'/'+mode+'/homework/add'" class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">
                            <span class="mx-1 text-sm font-semibold">Add</span>
                            <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 409.6 409.6" xml:space="preserve" class="w-3 h-3 fill-current text-white"><g><g><path d="M392.533,187.733H221.867V17.067C221.867,7.641,214.226,0,204.8,0s-17.067,7.641-17.067,17.067v170.667H17.067 C7.641,187.733,0,195.374,0,204.8s7.641,17.067,17.067,17.067h170.667v170.667c0,9.426,7.641,17.067,17.067,17.067 s17.067-7.641,17.067-17.067V221.867h170.667c9.426,0,17.067-7.641,17.067-17.067S401.959,187.733,392.533,187.733z"></path></g></g></svg>
                        </a> 
                    </div>
                </div>
            </div>
        </portal>
        <div class="">
            <div class="ds-table-wrap">
                <table class="ds-table-ledger ds-table-card-mobile">
                    <thead>
                        <tr>
                            <th v-if="hidecolumns == 'false'">Class</th>
                            <th>Description</th>
                            <th>Assigned Date</th>
                            <th>Submission Date</th>
                            <th>Attachment</th>
                            <th>Pending</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody v-if="Object.keys(this.homeworks).length > 0">
                        <tr v-for="homework in homeworks">
                            <td data-label="Class" v-if="hidecolumns == 'false'">
                                <p class="font-semibold text-xs">{{ homework.class_name }}</p>
                            </td>
                            <td data-label="Description">
                                <div class="font-semibold text-xs" v-html="trim(homework.description)"></div>
                            </td>
                            <td data-label="Assigned Date">
                                <p class="font-semibold text-xs">{{ homework.date }}</p>
                            </td>
                             <td data-label="Submission Date">
                                <p class="font-semibold text-xs">{{ homework.submission_date }}</p>
                            </td>
                            <td data-label="Attachment">
                                <a :href="homework.attachment" target="_blank" v-if="homework.attachment != ''" title="Attachment" class="dt-action-btn">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                </a>
                                <span v-else class="text-xs text-gray-400">--</span>
                            </td>
                            <td data-label="Pending">
                                <p class="font-semibold text-xs">{{ homework.pending_count }}</p>
                            </td>
                            <td data-label="Actions">
                                <div class="flex items-center gap-1">
                                    <a :href="url+'/'+mode+'/homework/edit/'+homework.id" title="Edit" v-if="hidecolumns == 'false'" class="dt-action-btn">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                    <a :href="url+'/'+mode+'/homework/show/'+homework.id" title="View" class="dt-action-btn">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="7" class="text-center text-gray-400 py-8">No homework found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div  v-if="Object.keys(this.homeworks).length > 0">
            <div v-for="list in homeworks">
                <div v-if="show == list.id+'_show'" class="modal modal-mask">
                    <div class="modal-wrapper px-4">
                        <div class="modal-container w-full max-w-md px-4 mx-auto">
                            <div class="modal-header flex justify-between items-center">
                                <h2>View Homework</h2>
                                <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="flex">
                                    <div class="w-full lg:w-1/4">
                                        <label for="standardLink_name" class="tw-form-label">Class</label>
                                    </div>
                                    <div class="w-full lg:w-3/4">
                                        <p class="tw-form-control w-full">{{ homework.standardLink_name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-body">
                                <div class="flex">
                                    <div class="w-full lg:w-1/4">
                                        <label for="description" class="tw-form-label">Description</label>
                                    </div>
                                    <div class="w-full lg:w-3/4">
                                        <p class="tw-form-control w-full" v-html="homework.description"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-body">
                                <div class="flex">
                                    <div class="w-full lg:w-1/4">
                                        <label for="date" class="tw-form-label">Date</label>
                                    </div>
                                    <div class="w-full lg:w-3/4">
                                        <p class="tw-form-control w-full">{{ homework.date }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-body" v-if="homework.attachment != ''">
                                <div class="flex">
                                    <a :href="homework.attachment" target="_blank">View Attachment</a>
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
        props:['url' , 'scope' , 'hidecolumns', 'searchquery' , 'mode'],
        data () {
            return {
                homeworks:[],
                homework:[],
                showPast:'',
                show:'',
                params:{},
                errors:[],
                success:null, 
            }
        },

        methods:{
            getData()
            {
                axios.get('/'+this.mode+'/homework/show/approved/list/?standardLink_id='+this.scope).then(response => {
                    this.homeworks  = response.data.data;    
                    //console.log(this.homeworks);    
                    //console.log(this.hidecolumns);    
                });
            },

            trim(string) 
            {
                return string.substring(0,140) + '...';
            },

            showModal(id)
            {
                this.show = id+'_show';
                axios.get('/'+this.mode+'/homework/edit/list/'+id).then(response => {
                    this.homework = response.data;
                });
            },

            closeModal()
            {
                this.show = 0;
            },

            showPastHomework(e)
            {
                if (e.target.checked) 
                {
                    this.params = { showPast:this.showPast };

                    this.final = this.url+'/'+this.mode+'/homework/show/list/?'+this.searchquery;

                    Object.keys(this.params).forEach(key => {
                      this.final = this.addParam(this.final, key, this.params[key])
                    });

                    axios.get(this.final).then(response => {
                      this.homeworks = response.data.data;
                    });
                }
                else if (!e.target.checked)
                {
                    this.getData();
                }
            },

            addParam(url, param, value) 
            {
                param = encodeURIComponent(param);
                var r = "([&?]|&amp;)" + param + "\\b(?:=(?:[^&#]*))*";
                var a = document.createElement('a');
                var regex = new RegExp(r);
                var str = param + (value ? "=" + encodeURIComponent(value) : ""); 
                a.href = url;
                var q = a.search.replace(regex, "$1"+str);
                if (q === a.search) 
                {
                    a.search += (a.search ? "&" : "") + str;
                } 
                else 
                {
                    a.search = q;
                }
                return a.href ;
            },

            deleteHomework(id) 
            {
                var thisswal = this;
                Swal.fire({
                    title: 'Are you sure',
                    text: 'Do you want to delete this Home work ?',
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    
                }).then(function(result) {
                    if (result.isConfirmed) 
                    {
                        axios.get(thisswal.url+ '/'+thisswal.mode+'/homework/delete/'+ id).then(response => {
                            thisswal.success = response.data.success;
                            window.location.reload();
                        }); 
                    }
                    else 
                    {
                        Swal.fire("Cancelled");
                    }
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

    .modal-container-new {
        margin: 0px auto;
        /*padding: 20px 30px;*/
        background-color: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
        transition: all .3s ease;
        height: 500px;
        overflow:auto;
    }

    .modal-container {
        margin: 0px auto;
        /*padding: 20px 30px;*/
        background-color: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
        transition: all .3s ease;
        /*height: 500px;*/
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