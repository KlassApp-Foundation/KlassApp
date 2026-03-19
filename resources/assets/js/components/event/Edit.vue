<template>
    <div>
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
        <button class="btn btn-primary hidden" @click="editEvents()" id="edit-event-modal" dusk="edit-event-modal">Edit</button>
        <div v-if="this.showEvents">    
            <div class="modal-mask">
                <div class="modal-wrapper px-4">
                    <div class="modal-container w-full max-w-md px-8 mx-auto">
                        <div class="modal-header flex justify-between items-center">
                            <h2>Edit</h2>
                            <button class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                        </div>

                        <div class="modal-body">
                            <div>
                                <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label for="title" class="tw-form-label">Title<span class="text-red-500">*</span></label>
                                        </div>
                                        <div class="w-3/4">
                                            <input type="text" v-model="title" id="title" placeholder="Title" class="tw-form-control w-full">
                                            <span v-if="errors.title" class="text-danger text-xs my-1">{{ errors.title[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label class="tw-form-label">Description<span class="text-red-500">*</span></label>
                                        </div>
                                        <div class="w-3/4">
                                            <textarea type="textarea" v-model="description" id="description" class="tw-form-control w-full" rows="3"> </textarea>
                                            <span v-if="errors.description" class="text-danger text-xs my-1">{{ errors.description[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label class="tw-form-label">Repeats?<span class="text-red-500">*</span></label>
                                        </div>
                                        <div class="w-3/4 flex">
                                            <div class="text-sm flex items-center">
                                                <input type="radio" name="no" v-model="repeats" id="repeats" value="0"><span class="mx-1">No</span>
                                            </div>
                                            <div class="text-sm flex items-center mx-4">
                                                <input type="radio" name="yes" v-model="repeats" id="repeat" value="1"><span class="mx-1">Yes</span>
                                                <span v-if="errors.repeats" class="text-danger text-xs my-1">{{ errors.repeats[0] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
            
                                <div id="freq" v-if="this.repeats=='1'">
                                    <div class="input-group flex my-3">
                                        <div class="w-1/4">
                                            <label class="input-group-addon tw-form-label">Every</label>
                                            <span v-if="errors.freq"><p class="text-danger text-xs my-1">{{errors.freq[0]}}</p></span>
                                        </div>   
                                        <div class="w-3/4 flex">
                                            <input type="number" v-model="freq" id="freq" value="1" class="freq-a tw-form-control w-3/5">
                                            <select v-model="freq_term" id="freq_term" class="freq-b tw-form-control w-full">
                                                <option value="" disabled>Select</option>
                                                <option v-for="list in freq_term_list" v-bind:value="list.id">{{ list.name }}</option>
                                            </select>
                                            <span v-if="errors.freq_term"><p class="text-danger text-xs my-1">{{errors.freq_term[0]}}</p></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="my-3" v-if="this.select_type=='class'">
                                    <div class="tw-form-group w-full">
                                        <div class="lg:mr-8 md:mr-8 flex items-center w-full">
                                            <div class="mb-2 w-1/4">
                                                <label for="standard" class="tw-form-label">Class<span class="text-red-500">*</span></label>
                                            </div>
                                            <div class="mb-2 w-3/4">
                                                <select class="tw-form-control w-full" id="standard" v-model="standard" name="standard">
                                                    <option value="" disabled>Select Class</option>  -->
                                                    <option v-for="list in standardlist" v-bind:value="list.id">{{ list.standard_section }}</option>
                                                </select>
                                            </div>
                                            <span v-if="errors.standard" class="text-danger text-xs my-1">{{ errors.standard[0] }}</span>
                                        </div> 
                                    </div>
                                </div>

                                <div class="my-3" v-if="this.select_type=='alumni'">
                                    <div class="tw-form-group w-full">
                                        <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center w-full">
                                            <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                                                <label for="batch" class="tw-form-label">Batch</label>
                                            </div>
                                            <div class="mb-2 w-full lg:w-3/4 md:w-3/4">
                                                <select name="batch" v-model="batch" id="batch" class="tw-form-control text-xs">
                                                    <option value="" disabled="disabled">Select Batch</option>
                                                    <option v-for="i in range(start,end)" v-bind:value="i">{{i}}</option>
                                                </select>
                                            </div>
                                            <span v-if="errors.batch" class="text-danger text-xs my-1">{{ errors.batch[0] }}</span>
                                        </div> 
                                    </div>
                                </div>

                                <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label for="location" class="tw-form-label">Location<span class="text-red-500">*</span></label>
                                        </div>
                                        <div class="w-3/4">
                                            <input type="text" v-model="location" id="location" placeholder="Include A Place Or Address" class="tw-form-control w-full">
                                            <span v-if="errors.location" class="text-danger text-xs my-1">{{ errors.location[0] }}</span>
                                        </div>
                                    </div>    
                                </div>

                                <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label class="tw-form-label">Event Category<span class="text-red-500">*</span></label>
                                        </div>
                                        <div class="w-3/4 flex">
                                            <select v-model="category" class="repeats tw-form-control w-full" placeholder = "Select category" id="category">
                                                <option value="" disabled>Select Event Category</option>
                                                <option v-for="list in categorylist" v-bind:value="list.id">{{ list.name }}</option>
                                            </select> 
                                            <span v-if="errors.category" class="text-red-500 text-xs font-semibold">{{ errors.category[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label for="organised_by" class="tw-form-label">Organised By<span class="text-red-500">*</span></label>
                                        </div>
                                        <div class="w-3/4">
                                            <input type="text" v-model="organised_by" id="organised_by" class="tw-form-control w-full" placeholder="Organised By">
                                            <span v-if="errors.organised_by" class="text-danger text-xs my-1">{{ errors.organised_by[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label class="tw-form-label">Image</label>
                                        </div>   
                                        <div class="w-3/4 mb-2 flex items-center">
                                            <div class="w-2/12">
                                                <img v-bind:src="url+'/'+this.image" class="img-responsive">
                                            </div>
                                            <div class="w-10/12 pl-3">
                                                <input type="file" @change="OnFileSelected" id="image" class="tw-form-control w-full file-upload">
                                                <label for="image" class="cursor-pointer flex p-1 border border-dashed border-gray-400 items-center">
                                                    <figure><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 17" class="w-4 h-4 mx-2"><path d="M10 0l-5.2 4.9h3.3v5.1h3.8v-5.1h3.3l-5.2-4.9zm9.3 11.5l-3.2-2.1h-2l3.4 2.6h-3.5c-.1 0-.2.1-.2.1l-.8 2.3h-6l-.8-2.2c-.1-.1-.1-.2-.2-.2h-3.6l3.4-2.6h-2l-3.2 2.1c-.4.3-.7 1-.6 1.5l.6 3.1c.1.5.7.9 1.2.9h16.3c.6 0 1.1-.4 1.3-.9l.6-3.1c.1-.5-.2-1.2-.7-1.5z" fill="#3492e2"></path></svg></figure>
                                                    <span class=" text-xs font-semibold">Choose a file…</span>
                                                </label>
                                                <span v-if="errors.image" class="text-danger text-xs my-1">{{ errors.image[0] }}</span>
                                            </div>
                                        </div>
                                        <div class="w-1/3 my-3" v-if="avatar">
                                            <img :src="avatar" class="img-responsive" height="70" width="90">
                                        </div>
                                    </div>
                                </div> -->
                                <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label class="input-group-addon tw-form-label">Start Date<span class="text-red-500">*</span></label>
                                        </div>
                                        <div class="w-3/4 text-sm">
                                            <datetime format="DD-MM-YYYY h:i:s" id="start_date"  v-model="start_date" class="rounded w-full"></datetime>
                                            <span v-if="errors.start_date" class="text-danger text-xs my-1">{{ errors.start_date[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="my-3">
                                    <div class="flex">
                                        <div class="w-1/4">
                                            <label class="input-group-addon tw-form-label">End Date<span class="text-red-500">*</span></label>
                                        </div>
                                        <div class="w-3/4 text-sm">
                                            <datetime format="DD-MM-YYYY h:i:s" id="end_date" v-model="end_date" class="w-full rounded"></datetime>
                                            <span v-if="errors.end_date" class="text-danger text-xs my-1">{{ errors.end_date[0] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="my-3">
                                    <a href="#" dusk="update-btn" class="btn btn-primary submit-btn" @click="checkupdateEvents()">Update</a>
                                    <input type="submit" class="hidden" id="update-btn">
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
    import datetime from 'vuejs-datetimepicker';
    export default {
        props:['url'],
        components: { datetime },
        data() {
            return {
                events:[],
                select_type:'',
                title:'',
                description:'',
                repeats:'',
                standard:'',
                freq:0,
                freq_term:0,
                location:'',
                category:'',
                organised_by:'',
                image:'',
                avatar:'',
                start_date:'',
                end_date:'',
                showEvents:0,
                batch:'',
                start:'',
                end:'',
                categorylist:[ { id : 'education' , name : 'Education'} , { id : 'meeting' , name : 'Meeting'} , { id : 'culturals' , name : 'Culturals'} ],
                freq_term_list:[ { id : 'day' , name : 'Day'} , { id : 'week' , name : 'Week'} , { id : 'month' , name : 'Month'} , { id : 'year' , name : 'Year'} ],
                event_id:'',
                errors:[],
                success:null,
                standardlist:[],
            }
        },

        methods:
        {
            createEvents()
            {
                this.showEvents=1;
                this.event_id=$('#event_id').val();
                this.editEvents(); 
            },

            editEvents()
            {
                this.showEvents=1;
                this.event_id=$('#event_id').val();
                axios.get('/admin/events/edit/'+this.event_id).then(response => {
                    this.events= response.data.data[0]; 
                    this.select_type=this.events.select_type;
                    this.title=this.events.title;
                    this.description=this.events.description;
                    this.repeats=this.events.repeats;
                    if(this.events.freq!=null)
                    {
                        this.freq=this.events.freq;
                    }
                    else
                    {
                        this.freq=0;
                    }

                    if(this.events.freq_term!=null)
                    {
                        this.freq_term=this.events.freq_term;
                    }
                    else
                    {
                        this.freq_term=0;
                    }
                    this.standard=this.events.standard_id;
                    this.location=this.events.location;
                    this.category=this.events.category;
                    this.organised_by=this.events.organised_by;
                    this.image=this.events.image;
                    this.start_date=this.events.start_date;
                    this.end_date=this.events.end_date;              
                    this.batch=this.events.batch;              
                });      
            },
     
            updateEvents()
            {
                this.event_id=$('#event_id').val();
         
                this.errors=[];
                this.success=null; 

                let formData=new FormData();

                formData.append('title',this.title);      
                formData.append('description',this.description);
                formData.append('repeats',this.repeats);      
                formData.append('freq',this.freq); 
                formData.append('freq_term',this.freq_term);
                formData.append('standard',this.standard);
                formData.append('location',this.location);
                formData.append('category',this.category);
                formData.append('organised_by',this.organised_by);
                formData.append('image',this.image);
                formData.append('start_date',this.start_date);      
                formData.append('end_date',this.end_date);
                formData.append('batch',this.batch);

                axios.post('/admin/events/update/'+this.event_id,formData).then(response => {   
                    this.success = response.data.success;
                    this.closeModal();
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            createImage(file) 
            {
                let reader = new FileReader();
                let vm = this;
                reader.onload = (e) => {
                    vm.avatar = e.target.result;
                };
                reader.readAsDataURL(file);
            },

            checkupdateEvents()
            {
                this.showEvents=1;
                this.event_id=$('#event_id').val();
         
                this.errors=[];
                this.success=null; 

                let formData=new FormData();

                formData.append('title',this.title);      
                formData.append('description',this.description);
                formData.append('repeats',this.repeats);      
                formData.append('freq',this.freq); 
                formData.append('freq_term',this.freq_term);
                formData.append('standard',this.standard);
                formData.append('location',this.location);
                formData.append('category',this.category);
                formData.append('organised_by',this.organised_by);
                formData.append('image',this.image);
                formData.append('start_date',this.start_date);      
                formData.append('end_date',this.end_date); 
                formData.append('batch',this.batch); 

                axios.post('/admin/events/validateedit/'+this.event_id,formData).then(response => {   
                    this.updateEvents();
                    //$('#site_submit').click();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },


            getData()
            {
                axios.get('/admin/events/list').then(response => {
                    this.standardlist = response.data.standardlist;
                    //console.log(this.standardlist); 
                    this.start  = parseInt(response.data.start);
                    this.end    = parseInt(response.data.end); 
                });
            },
     
            range(max,min)
            {
                var array = [],
                j = 0;
                for(var i = max; i >= min; i--)
                {
                    array[j] = i;
                    j++;
                }
                return array;
            },

            OnFileSelected(event)
            {
                /*this.image = event.target.files[0];
                this.createImage(files);*/
                this.image=event.target.files[0];
                let files = event.target.files || event.dataTransfer.files;
                if (!files.length)
                    return;
                this.createImage(files[0]);
            },

            closeModal()
            {
                this.showEvents=0;
            },
        },
        created()
        {
            this.getData();
        },
    }
</script>

<style scoped>

    textarea{
        /*   width: 300px;*/
        height: 100px;
    }

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

    .file-upload {
        width: .1px;
        height: .1px;
        opacity: 0;
        overflow: hidden;
        position: absolute;
        z-index: -1;
    }
</style>