<template>
    <div class="bg-white shadow px-4 py-3">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
        <div>
            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="flex">
                            <div class="w-1/2 flex items-center lg:mr-8 md:mr-8"> 
                                <input type="radio" v-model="parent" name="parent" id="add" value="add"@click="enableDiv($event)">
                                <span class="text-sm mx-1">Add Parent</span>
                            </div>
                            <div class="w-1/2 flex items-center mr-2 lg:mr-8 md:mr-8"> 
                                <input type="radio" v-model="parent" name="parent" id="select" value="select" @click="enableDiv($event)">
                                <span class="text-sm mx-1">Select Parent</span>
                            </div>
                        </div>
                        <span v-if="errors.parent" class="text-red-500 text-xs font-semibold">{{errors.parent[0]}}</span>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="hidden" id="select_parent">
            <div class="flex">
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="standardLink_id" class="tw-form-label">Class</label>
                        </div>
                        <div class="mb-2">
                            <select class="tw-form-control w-full" id="standardLink_id" v-model="standardLink_id" name="standardLink_id" @change="enableParent()">
                                <option value="" disabled>Select Class</option>
                                <option v-for="list in standardLinklist" :value="list.id">{{ list.standard_section }}</option>
                            </select>
                        </div>
                        <span v-if="errors.standardLink_id" class="text-red-500 text-xs font-semibold">{{ errors.standardLink_id[0] }}</span>
                    </div> 
                </div>
            </div>
            <div class="tw-form-group w-full lg:w-1/2" v-if="this.standardLink_id != ''">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2">
                        <label for="select_id" class="tw-form-label">Select Parent</label>
                    </div>
                    <div class="mb-2">
                        <multiselect v-model="select_id" id="ajax" name="select_id" label="fullname" track-by="fullname" placeholder="Type to search" open-direction="bottom" :options="users" :custom-label="customLabel" :show-labels="false" :multiple="true" :searchable="true" :loading="isLoading" :internal-search="true" :clear-on-select="false" :close-on-select="false" :limit-text="limitText" :max-height="600" :show-no-results="true" :hide-selected="true" @search-change="asyncFind">
                            <template v-slot:tag="{ option, remove }">
                                <span class="custom__tag">
                                    <span>{{ (option.fullname) }}</span>
                                    <span class="custom__remove" @click="remove(option)">x</span>
                                </span>
                            </template>
                            <template v-slot:clear="props">
                                <div class="multiselect__clear" v-if="select_id.length" @mousedown.prevent.stop="clearAll(props.search)"></div>
                            </template>
                            <template v-slot:option="props">
                                <div class="option__desc">
                                    <span class="option__name">{{ props.option.fullname }}</span>
                                </div>
                                <div class="option__desc">
                                    <span class="option__small"> ( {{ props.option.mobile_no }} )</span>
                                </div>
                            </template>
                            <template v-slot:noResult><span>Oops! No users found.</span></template>
                        </multiselect>
                        <span v-if="errors.select_id" class="text-red-500 text-xs font-semibold">{{errors.select_id[0]}}</span>
                    </div> 
                </div>
            </div>
            <div class="my-6">
                <a href="#" class="btn btn-primary submit-btn" @click="submitForm()">Submit</a>
            </div>
        </div>

        <div class="" id="add_parent">
            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="relation" class="tw-form-label">Relation:</label>
                        </div>
                        <div class="mb-2">
                            <select class="tw-form-control w-full" id="relation" v-model="relation" name="relation">
                                <option value="" disabled>Relationship</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Guardian">Guardian</option>
                            </select>
                        </div>
                        <span v-if="errors.relation" class="text-red-500 text-xs font-semibold">{{errors.relation[0]}}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="firstname" class="tw-form-label">First Name<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2">              
                            <input type="text" v-model="firstname" name="firstname" id="firstname" class="tw-form-control w-full" placeholder="First Name"> 
                        </div>
                        <span v-if="errors.firstname" class="text-red-500 text-xs font-semibold">{{errors.firstname[0]}}</span>
                    </div>
                </div>

                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="lastname" class="tw-form-label">Last Name</label>
                        </div>
                        <div class="mb-2">              
                            <input type="text" v-model="lastname" name="lastname" id="lastname" class="tw-form-control w-full" placeholder="Last Name"> 
                        </div>
                        <span v-if="errors.lastname" class="text-red-500 text-xs font-semibold">{{errors.lastname[0]}}</span>
                    </div>
                </div>

                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="mobile_no" class="tw-form-label">Phone Number<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2">              
                            <input type="text" v-model="mobile_no" name="mobile_no" id="mobile_no" class="tw-form-control w-full" placeholder="Phone Number (WhatsApp)"> 
                        </div>
                        <span v-if="errors.mobile_no" class="text-red-500 text-xs font-semibold">{{errors.mobile_no[0]}}</span>
                    </div>
                </div>
            </div>

            <div class="my-6">
                <a href="#" class="btn btn-primary submit-btn" @click="submitForm()">Submit</a>
            </div>
        </div>
    </div>
</template>

<script>
    import axios from 'axios';
    export default {
        props: ['url', 'ref_name'],
        data() {
            return {
                users: [],
                qualifications: [],
                parent: 'add',
                select_id: [],
                standardLink_id: '',
                firstname: '',
                lastname: '',
                mobile_no: '',
                relation: '',
                isLoading: false,
                errors: [],
                success: null,
                standardLinklist: [],
            }
        },
        methods: {
            getData(query)
            {
                axios.get('/admin/parent/get?standardLink_id='+this.standardLink_id+'&'+query).then(response => {
                    this.users = response.data.parent;
                    this.qualificationlist = response.data.qualificationlist;
                    this.standardLinklist = response.data.standardLinklist;
                    this.isLoading = false; 
                });
            },

            limitText (count) 
            {
                return `and ${count} other users`
            },

            customLabel ({ fullname, mobile_no }) 
            {
                return `${fullname} - ${mobile_no}`
            },

            clearAll () 
            {
                this.select_id = []
            },

            asyncFind (query) 
            {
                this.isLoading = true
                this.getData(query);
            },

            enableParent()
            {
                this.params = { standardLink_id:this.standardLink_id };
                this.final = this.url+'/admin/parent/get';

                Object.keys(this.params).forEach(key => {
                    this.final = this.addParam(this.final, key, this.params[key])
                });

                axios.get(this.final).then(response => {
                    this.users = response.data.parent;
                });
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

            enableDiv(e)
            {
                if(e.target.checked)
                {
                    if(e.target.value == 'add')
                    { 
                        if($('#add_parent').hasClass('hidden'))
                        {
                            $('#add_parent').addClass('block').removeClass('hidden');
                            $('#select_parent').addClass('hidden').removeClass('block');
                        }
                    }
                    else if(e.target.value == 'select')
                    {
                        if($('#select_parent').hasClass('hidden'))
                        {
                            $('#select_parent').addClass('block').removeClass('hidden');
                            $('#add_parent').addClass('hidden').removeClass('block');
                        }
                    }
                }
            },

            resetForm()
            {
                this.firstname='';
                this.lastname='';
                this.mobile_no='';
                this.relation='';
            }, 

            submitForm()
            {
                this.errors=[];
                this.success=null; 
                let formData=new FormData(); 
                formData.append('parent',this.parent); 
                formData.append('ref_name',this.ref_name);          
                formData.append('firstname',this.firstname);           
                formData.append('lastname',this.lastname);          
                formData.append('mobile_no',this.mobile_no);
                formData.append('relation',this.relation);  

                for(let j=0; j<this.select_id.length;j++)
                { 
                    if(typeof this.select_id[j]['id'] !== "undefined")
                    {
                        formData.append('select_id',this.select_id[j]['id']);
                    }
                    else
                    {
                        formData.append('select_id','');
                    }
                }

                if(this.parent == 'add')
                {
                    axios.post('/admin/parent/add/validationParent',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                        $('#submit-btn').click(); 
                    }).catch(error => {
                        this.errors = error.response.data.errors;
                    }); 
                }
                else
                {
                    axios.post('/admin/parent/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                        this.success = response.data.success; 
                    }).catch(error => {
                        this.errors = error.response.data.errors;
                    }); 
                }
            },
        },
    
        created()
        {
            this.getData();
        }
    }
</script>
