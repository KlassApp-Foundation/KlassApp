<template>
    <div>
        <!-- <div v-if="parseInt(this.count)<=parseInt(this.no_of_events)"> -->
            <div>
            <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

            <div class="flex flex-wrap lg:flex-row justify-between">
                <div class="">
                    <h1 class="admin-h1 my-3">Events ({{ this.count }})</h1>
                </div>
                <div class="relative flex items-center w-8/12 lg:w-1/4 md:w-1/4 justify-end">
                    <div class="flex items-center w-full justify-end mx-2">
                        <select @change="filterBy()" class="tw-form-control w-1/2 mx-2" id="standardLink_id" v-model="standardLink_id" name="standardLink_id">
                            <option value="" disabled>Select Class</option>
                            <option v-for="list in standardlist" v-bind:value="list.id">{{ list.standard_section }}</option>
                        </select>
                        <div class="date-select date-select_none dashboard-reset mx-1 lg:mx-0 md:mx-0">
                            <a href="#" @click="resetClass()" id="do-reset" class="text-sm border bg-gray-100 text-grey-darkest py-1 px-4">Reset</a>
                        </div>
                    </div>
                    <div class="w-32 relative">
                        <a href="#" class="text-sm rounded px-2 py-1 flex items-center whitespace-no-wrap justify-between btn btn-primary submit-btn w-full" @click="showeventlink()" id="show">
                            <span>Create Event</span>
                            <svg class="w-2 h-2 fill-current text-white" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Capa_1" x="0px" y="0px" width="512px" height="512px" viewBox="0 0 451.847 451.847" style="enable-background:new 0 0 451.847 451.847;" xml:space="preserve"><g><g><path d="M225.923,354.706c-8.098,0-16.195-3.092-22.369-9.263L9.27,151.157c-12.359-12.359-12.359-32.397,0-44.751 c12.354-12.354,32.388-12.354,44.748,0l171.905,171.915l171.906-171.909c12.359-12.354,32.391-12.354,44.744,0 c12.365,12.354,12.365,32.392,0,44.751L248.292,345.449C242.115,351.621,234.018,354.706,225.923,354.706z" data-original="#000000" class="active-path" data-old_color="#000000" fill=""/></g></g> </svg>
                        </a>
                        <div class="border create_event absolute z-40 w-40 right-0 bg-white" v-bind:class="[this.show_event_link==1?'block':'hidden']">
                            <ul class="list-reset text-xs text-gray-700 leading-loose py-1">
                                <li class="px-2">
                                    <a href="#" id="class" class="whitespace-no-wrap" @click="createEvents('class')">Create Class Room Event</a>
                                </li>
                                <li class="px-2">
                                    <a href="#" id="school" @click="createEvents('school')">Create School Event</a>
                                </li>
                                <li v-if="galumniEnabled" class="px-2">
                                    <a href="#" id="alumni" @click="createEvents('alumni')">Create Alumni Event</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div v-if="this.showEvents">    
                    <div class="modal modal-mask">
                        <div class="modal-wrapper px-4">
                            <div class="modal-container w-full  max-w-md px-8 lg:mx-auto">
                                <div class="modal-header flex justify-between items-center">
                                    <h2>Add Event</h2>
                                    <button id="close-button" class="modal-default-button text-2xl py-1"  @click="closeModal()">&times;</button>
                                </div>

                                <div class="modal-body">
                                    <div>
                                        <div class="my-3">
                                            <div class="flex flex-col lg:flex-row md:flex-row">
                                                <div class="w-full lg:w-1/4 md:w-1/4">
                                                    <label for="title" class="tw-form-label">Title<span class="text-red-500">*</span></label>
                                                </div>
                                                <div class="w-full lg:w-3/4 md:w-3/4">
                                                    <input type="text" v-model="title" placeholder="Add A Short, Clear Name" class="tw-form-control w-full" id="title">
                                                    <span v-if="errors.title" class="text-red-500 text-xs font-semibold">{{ errors.title[0] }}</span>
                                                </div>
                                            </div>    
                                        </div>

                                        <div class="my-3">
                                            <div class="flex flex-col lg:flex-row md:flex-row">
                                                <div class="w-full lg:w-1/4 md:w-1/4">
                                                    <label class="tw-form-label">Description<span class="text-red-500">*</span></label>
                                                </div>
                                                <div class="w-full lg:w-3/4 md:w-3/4">
                                                    <textarea v-model='description' @keyup='remaincharCount()' maxlength="100" class="tw-form-control w-full" placeholder="Description"></textarea>
                                                    <br>
                                                    <p id="rchars" class="text-gray-700 text-xs my-1" style="text-align: right"></p>
                                                    <span v-if="errors.description" class="text-red-500 text-xs font-semibold">{{ errors.description[0] }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="my-3">
                                            <div class="flex flex-col lg:flex-row md:flex-row">
                                                <div class="w-full lg:w-1/4 md:w-1/4">
                                                    <label class="tw-form-label">Repeats?<span class="text-red-500">*</span></label>
                                                </div>
                                                <div class="w-full lg:w-3/4 md:w-3/4 flex">
                                                    <div class="text-sm flex items-center">
                                                        <input type="radio" name="no" v-model="repeats" value="0" id="repeats"><span class="mx-1">No</span>
                                                    </div>
                                                    <div class="text-sm flex items-center mx-4">
                                                        <input type="radio" name="yes" v-model="repeats" value="1" id="repeat"><span class="mx-1">Yes</span>
                                                    </div>
                                                    <span v-if="errors.repeats" class="text-red-500 text-xs font-semibold">{{ errors.repeats[0] }}</span>
                                                </div>
                                            </div>

                                            <div id="freq" v-bind:class="[this.repeats=='1' ?'block': 'hidden']">
                                                <div class="input-group flex flex-col lg:flex-row md:flex-row my-3">
                                                    <div class="w-full lg:w-1/4 md:w-1/4">
                                                        <label class="tw-form-label">Every</label>
                                                    </div>
                                                    <div class="w-full lg:w-3/4 md:w-3/4 flex">
                                                        <div class="w-2/5">
                                                            <input type="text" name="freq" v-model="freq" value="1" class="freq-a tw-form-control w-full">
                                                            <span v-if="errors.freq_term" class="text-red-500 text-xs font-semibold">{{ errors.freq[0] }}</span>
                                                        </div>
                                                        <div class="w-3/5 ml-3">
                                                            <select v-model="freq_term" id="freq_term" class="freq-b tw-form-control w-full">
                                                                <option value="" disabled>Select</option>
                                                                <option v-for="list in freq_term_list" v-bind:value="list.id">{{ list.name }}</option>
                                                            </select>
                                                            <span v-if="errors.freq_term" class="text-red-500 text-xs font-semibold">{{ errors.freq_term[0] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="my-3" v-if="this.select_type=='class'">
                                            <div class="tw-form-group w-full">
                                                <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center w-full">
                                                    <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
                                                        <label for="standard_id" class="tw-form-label">Class<span class="text-red-500">*</span></label>
                                                    </div>
                                                    <div class="mb-2 w-full lg:w-3/4 md:w-3/4">
                                                        <select class="tw-form-control w-full" id="standard_id" v-model="standard_id" name="standard_id">
                                                            <option value="" disabled>Select Class</option>
                                                            <option v-for="standard in standardlist" v-bind:value="standard.id">{{ standard.standard_section }}</option>
                                                        </select>
                                                    </div>
                                                    <span v-if="errors.standard_id" class="text-red-500 text-xs font-semibold">{{ errors.standard_id[0] }}</span>
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
                                            <div class="flex flex-col lg:flex-row md:flex-row">
                                                <div class="w-full lg:w-1/4 md:w-1/4">
                                                    <label for="location" class="tw-form-label">Location<span class="text-red-500">*</span></label>
                                                </div>
                                                <div class="w-full lg:w-3/4 md:w-3/4">
                                                    <input type="text" v-model="location" placeholder="Include A Place Or Address" class="tw-form-control w-full" id="location">
                                                    <span v-if="errors.location" class="text-red-500 text-xs font-semibold">{{ errors.location[0] }}</span>
                                                </div>
                                            </div>    
                                        </div>

                                        <div class="my-3">
                                            <div class="flex flex-col lg:flex-row md:flex-row">
                                                <div class="w-full lg:w-1/4 md:w-1/4">
                                                    <label class="tw-form-label">Event Category<span class="text-red-500">*</span></label>
                                                </div>
                                                <div class="w-full lg:w-3/4 md:w-3/4">
                                                    <select v-model="category" class="repeats tw-form-control w-full" placeholder = "Select category" id="category">
                                                        <option value="" disabled>Select Event Category</option>
                                                        <option v-for="list in categorylist" v-bind:value="list.id">{{ list.name }}</option>
                                                    </select> 
                                                    <span v-if="errors.category" class="text-red-500 text-xs font-semibold">{{errors.category[0]}}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="my-3">
                                            <div class="flex flex-col lg:flex-row md:flex-row">
                                                <div class="w-full lg:w-1/4 md:w-1/4">
                                                    <label for="organised_by" class="tw-form-label">Organised By<span class="text-red-500">*</span></label>
                                                </div>
                                                <div class="w-full lg:w-3/4 md:w-3/4">
                                                    <input type="text" v-model="organised_by" class="tw-form-control w-full" id="organised_by" placeholder="Organised By">
                                                    <span v-if="errors.organised_by" class="text-red-500 text-xs font-semibold">{{ errors.organised_by[0] }}</span>
                                                </div>
                                            </div>    
                                        </div>

                                        <!--<div class="my-3">
                                            <div class="flex">
                                                <div class="w-1/4">
                                                    <label class="tw-form-label">Image</label>
                                                </div>
                                                <div class="mb-2 w-3/4">
                                                    <input type="file" @change="OnFileSelected" id="image" class="tw-form-control w-full">
                                                    <span v-if="errors.image" class="text-red-500 text-xs font-semibold">{{ errors.image[0] }}</span>
                                                </div>
                                            </div>
                                        </div> -->

                                        <div class="my-3">
                                            <div class="flex flex-col lg:flex-row md:flex-row">
                                                <div class="w-full lg:w-1/4 md:w-1/4">
                                                    <label class="input-group-addon tw-form-label">Start Date<span class="text-red-500">*</span></label>
                                                </div>
                                                <div class="w-full lg:w-3/4 md:w-3/4 text-sm">
                                                    <datetime format="DD-MM-YYYY h:i:s" name="start_date" v-model="start_date" class="rounded w-full" id="start_date"></datetime>
                                                    <span v-if="errors.start_date" class="text-red-500 text-xs font-semibold">{{ errors.start_date[0] }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="my-3">  
                                            <div class="flex flex-col lg:flex-row md:flex-row">
                                                <div class="w-full lg:w-1/4 md:w-1/4">
                                                    <label class="input-group-addon tw-form-label">End Date<span class="text-red-500">*</span></label>
                                                </div>
                                                <div class="w-full lg:w-3/4 md:w-3/4 text-sm">
                                                    <datetime format="DD-MM-YYYY h:i:s" v-model="end_date" class="w-full rounded" id="end_date"></datetime>
                                                    <span v-if="errors.end_date" class="text-red-500 text-xs font-semibold">{{ errors.end_date[0] }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="my-3">
                                            <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="sendData()">Submit</a>
                                            <input type="submit" class="hidden" id="submit-btn">
                                        </div>
                                    </div>           
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       <!--  <div v-if="parseInt(this.count)>parseInt(this.no_of_events)">
            <a href="/pricing"> 
                <button type="submit" class="no-underline text-white  px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">Upgrade Plan to Add More Events</button>
            </a>
        </div> -->

    </div>
</template>

<script>
    import datetime from 'vuejs-datetimepicker';
    export default {
        props:['url','count','no_of_events','standard'],
        components: { datetime },
        data() {
            return {
                event:[],
                select_type:'',
                title:'',
                description:'',
                repeats:'0',
                standard_id:'',
                freq:'',
                freq_term:'',
                location:'',
                category:'',
                organised_by:'',
                image:'',
                start_date:'',
                end_date:'',
                standardlist:[],
                batch:'',
                start:'',
                end:'',
                standardLink_id:'',
                showEvents:0,
                categorylist:[ { id : 'education' , name : 'Education'} , { id : 'meeting' , name : 'Meeting'} , { id : 'culturals' , name : 'Culturals'} ],
                freq_term_list:[ { id : 'day' , name : 'Day'} , { id : 'week' , name : 'Week'} , { id : 'month' , name : 'Month'} , { id : 'year' , name : 'Year'} ],
                trans:[],
                errors:[],
                success:null,
                show_event_link:0,
                galumniEnabled: false,
            }
        },
        mounted() {
            this.galumniEnabled = window.AppConfig?.galumni_enabled ?? false;

        },

        methods:
        {
            remaincharCount()
            {
                var maxLength = 100;
                $('textarea').keyup(function() {
                    var textlen = maxLength - $(this).val().length+'/100';
                    $('#rchars').text(textlen);
                });
            },
  
            resetForm()
            {
                this.title='';
                this.description='';
                this.repeats ='';
                this.standard_id='';
                this.freq ='';
                this.freq_term = '';
                this.location = '';
                this.category = '';
                this.organised_by ='';
                this.image = '';
                this.start_date = '';
                this.end_date = '';
            },

            createEvents(selecttype)
            {
                this.select_type=selecttype;
                this.showEvents=1;
                this.showeventlink();
            },

            sendData()
            {
                this.errors=[];
                this.success=null;

                let formData=new FormData();

                formData.append('select_type',this.select_type);
                formData.append('title',this.title);  
                formData.append('description',this.description);         
                formData.append('repeats',this.repeats);          
                formData.append('standard_id',this.standard_id);          
                formData.append('freq',this.freq);          
                formData.append('freq_term',this.freq_term);          
                formData.append('location',this.location);          
                formData.append('category',this.category);
                formData.append('organised_by',this.organised_by);          
                formData.append('image',this.image);          
                formData.append('start_date',this.start_date);          
                formData.append('end_date',this.end_date);          
                formData.append('batch',this.batch);          
        
                axios.post('/admin/events/create',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => { 
                    this.success=response.data.success;
                    this.closeModal();
                    window.location.reload();
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
                    if(this.date.end != null)
                    {
                        this.end = parseInt(response.data.end);
                    }
                    else
                    {
                        this.end = null;
                    }
                });
            },
     
            range(max,min)
            {
                var array = [],
                j = 0;
                if(min != null)
                {
                    for(var i = max; i >= min; i--)
                    {
                        array[j] = i;
                        j++;
                    }
                }
                else
                {
                    array[j] = max
                }
                return array;
            },

            OnFileSelected(event)
            {
                this.image = event.target.files[0];
            },
  
            closeModal()
            {
                this.showEvents=0;
            },

            showeventlink()
            {
                this.show_event_link=!this.show_event_link;
            },

            filterBy()
            {
                window.location.href = this.url+'/admin/events?standardLink_id='+this.standardLink_id;
            }, 

            resetClass()
            {
                window.location.href = this.url+'/admin/events';
            } 
        },

        created()
        {
            this.getData();
            this.standardLink_id=this.standard;
        }
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
</style>