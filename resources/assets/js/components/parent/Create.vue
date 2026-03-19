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

                            <template slot="tag" slot-scope="{ option, remove }">
                                <span class="custom__tag">
                                    <span>{{ (option.fullname) }}</span>
                                    <span class="custom__remove" @click="remove(option)">❌</span>
                                </span>
                            </template>

                            <template slot="clear" slot-scope="props">
                                <div class="multiselect__clear" v-if="select_id.length" @mousedown.prevent.stop="clearAll(props.search)"></div>
                            </template>
                  
                            <template slot="option" slot-scope="props">
                                <div class="option__desc">
                                    <span class="option__name">{{ props.option.fullname }}</span>
                                </div>
                                <div class="option__desc">
                                    <span class="option__small"> ( {{ props.option.mobile_no }} )</span>
                                </div>
                            </template>

                            <span slot="noResult">Oops! No users found.</span>
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
            <div class="flex">
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="relation" class="tw-form-label">Relation:</label>
                        </div>
                        <div class="mb-2">
                            <select class="tw-form-control w-full" id="relation" v-model="relation" name="relation">
                                <option value="" disabled>Relationship</option>
                                <option value="father">Father</option>
                                <option value="mother">Mother</option>
                                <option value="guardian">Guardian</option>
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
                            <input type="text" class="tw-form-control w-full" id="firstname" v-model="firstname" name="firstname" Placeholder="First Name">
                        </div>
                        <span v-if="errors.firstname" class="text-red-500 text-xs font-semibold">{{errors.firstname[0]}}</span>
                    </div> 
                </div>

                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="lastname" class="tw-form-label">Last Name </label>
                        </div>
                        <div class="mb-2">              
                            <input type="text" v-model="lastname" name="lastname" id="lastname" class="tw-form-control w-full" Placeholder="Last Name">
                        </div>
                        <span v-if="errors.lastname" class="text-red-500 text-xs font-semibold">{{errors.lastname[0]}}</span>
                    </div> 
                </div>
            </div>

            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="mobile_no" class="tw-form-label">Mobile Number<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2">              
                            <input type="text" v-model="mobile_no" name="mobile_no" id="mobile_no" class="tw-form-control w-full" placeholder="Mobile Number"> 
                        </div>
                        <span v-if="errors.mobile_no" class="text-red-500 text-xs font-semibold">{{errors.mobile_no[0]}}</span>
                    </div>
                </div>

                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="alternate_no" class="tw-form-label">Alternate Number</label>
                        </div>
                        <div class="mb-2">              
                            <input type="text" v-model="alternate_no" name="alternate_no" id="alternate_no" class="tw-form-control w-full" placeholder="Alternate Number"> 
                        </div>
                        <span v-if="errors.alternate_no" class="text-red-500 text-xs font-semibold">{{errors.alternate_no[0]}}</span>
                    </div>
                </div>

                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="email" class="tw-form-label">Email ID</label>
                        </div>
                        <div class="mb-2">             
                            <input type="text" v-model="email" name="email" id="email" class="tw-form-control w-full" placeholder="Email ID">  
                        </div>
                        <span v-if="errors.email" class="text-red-500 text-xs font-semibold">{{errors.email[0]}}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/3" v-for="(input, index) in inputs">
                    <div class="lg:mr-2 md:mr-2">
                        <div class="mb-2">
                            <label for="qualification_id" class="tw-form-label">Qualification</label>
                        </div>
                        <div class="flex items-center">
                            <div class="mb-2">
                                <select class="tw-form-control w-full" id="qualification_id" v-model="input.qualification_id" name="qualification_id[]">
                                    <option value="" disabled>Select Qualification</option>
                                    <option v-for="qualifications in qualificationlist" v-bind:value="qualifications.id">{{ qualifications.display_name }}</option>
                                </select>
                            </div>
                            <div class="mx-2">
                                <a href="#" class="btn-times" @click="deleteRow(index)">
                                    <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-4 h-4 fill-current text-red-600"><g><g><g><polygon points="353.574,176.526 313.496,175.056 304.807,412.34 344.885,413.804"></polygon> <rect x="235.948" y="175.791" width="40.104" height="237.285"></rect><polygon points="207.186,412.334 198.497,175.049 158.419,176.52 167.109,413.804"></polygon><path d="M17.379,76.867v40.104h41.789L92.32,493.706C93.229,504.059,101.899,512,112.292,512h286.74 c10.394,0,19.07-7.947,19.972-18.301l33.153-376.728h42.464V76.867H17.379z M380.665,471.896H130.654L99.426,116.971h312.474 L380.665,471.896z"></path></g></g></g> <g><g><path d="M321.504,0H190.496c-18.428,0-33.42,14.992-33.42,33.42v63.499h40.104V40.104h117.64v56.815h40.104V33.42 C354.924,14.992,339.932,0,321.504,0z"></path></g></g></svg>
                                </a>
                            </div>
                        </div>
                        <span v-if="errors['qualification_id'+index]" class="text-red-500 text-xs font-semibold">{{errors['qualification_id'+index]}}</span>
                    </div>           
                </div>
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="add_qualification" class="tw-form-label">Add New</label>
                        </div>
                        <div class="mb-2">
                            <a href="#" class="tw-form-control w-full" @click="addRow">+ </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row">
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="profession" class="tw-form-label">Occupation<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2">
                            <select class="tw-form-control w-full" id="profession" v-model="profession" name="profession">
                                <option value="" disabled>Occupation</option>
                                <option v-for="profession in professions" v-bind:value="profession.num">{{ profession.name }}</option>
                            </select>
                        </div>
                        <span v-if="errors.profession" class="text-red-500 text-xs font-semibold">{{errors.profession[0]}}</span>
                    </div> 
                </div>

                <div class="tw-form-group w-full lg:w-1/3" v-if="checkInArray(this.occupationlist,this.profession)">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="sub_occupation" class="tw-form-label">Sub-Category</label>
                        </div>
                        <div class="mb-2">
                            <input type="text" v-model="sub_occupation" name="sub_occupation" id="sub_occupation" class="tw-form-control w-full" placeholder="Sub Category">
                        </div>
                        <span v-if="errors.sub_occupation" class="text-red-500 text-xs font-semibold">{{errors.sub_occupation[0]}}</span>
                    </div> 
                </div>
            </div>

            <div class="flex flex-col lg:flex-row" v-if="checkInArray(this.occupationlist,this.profession)">
                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="designation" class="tw-form-label">Designation</label>
                        </div>
                        <div class="mb-2">
                            <input type="text" class="tw-form-control w-full" id="designation" v-model="designation" name="designation" placeholder="Designation">
                        </div>
                        <span v-if="errors.designation" class="text-red-500 text-xs font-semibold">{{errors.designation[0]}}</span>
                    </div> 
                </div>

                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="organization_name" class="tw-form-label">Organization Name</label>
                        </div>
                        <div class="mb-2">
                            <input type="text" v-model="organization_name" name="organization_name" id="organization_name" class="tw-form-control w-full" placeholder="Organization Name">
                        </div>
                        <span v-if="errors.organization_name" class="text-red-500 text-xs font-semibold">{{errors.organization_name[0]}}</span>
                    </div> 
                </div>

                <div class="tw-form-group w-full lg:w-1/3">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="annual_income" class="tw-form-label">Annual Income<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2">
                            <input type="text" v-model="annual_income" name="annual_income" id="annual_income" class="tw-form-control w-full" placeholder="Annual Income">
                        </div>
                        <span v-if="errors.annual_income" class="text-red-500 text-xs font-semibold">{{errors.annual_income[0]}}</span>
                    </div> 
                </div>
            </div>

            <portal-target name="official_address"></portal-target>

            <portal-target name="submit-btn"></portal-target>
            <portal to="submit-btn">
                <div class="my-6">
                    <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="submitForm()">Submit</a>
                    <a href="#" class="btn btn-reset reset-btn" @click="resetForm()">Reset</a>
                    <input type="submit" class="hidden" id="submit-btn">
                </div>
            </portal>
        </div>
    </div>
</template>

<script> 
    import Multiselect from 'vue-multiselect'
    // register globally
    Vue.component('multiselect', Multiselect)
    export default {
        props:['url' , 'ref_name' ],
        components: {
            Multiselect
        },
        data(){
            return {
                users:[],
                parent:'add',
                select_id:[],
                standardLink_id:'',
                firstname:'',
                lastname:'',
                email:'',
                mobile_no:'',
                alternate_no:'',
                qualification_id:'',
                inputs: [{
                    qualification_id:'',
                }],
                profession:'',
                sub_occupation:'',
                designation:'',
                organization_name:'',
                relation:'',
                annual_income:'',
                qualificationlist:[],
                standardLinklist:[],
                occupationlist:['business','central_government_employee','private','state_government_employee','others'],
                professions:[{num:'business' , name:'Business'} , {num:'central_government_employee' , name:'Central Government Employee'} , {num:'private' , name:'Private'} , {num:'home_maker' , name:'Home Maker'} , {num:'state_government_employee' , name:'State Government Employee'} , {num:'others' , name:'Others'} ],
                isLoading: false,
                errors:[],
                success:null,
            }
        },

        methods:
        {
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
                return `${fullname} – ${mobile_no}`
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
                this.email='';
                this.mobile_no='';
                this.alternate_no='';
                this.qualification_id='';
                this.profession='';
                this.sub_occupation='';
                this.designation='';
                this.organization_name='';
                this.relation='';
                this.annual_income='';
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
                formData.append('email',this.email);       
                formData.append('mobile_no',this.mobile_no);
                formData.append('alternate_no',this.alternate_no);
                formData.append('profession',this.profession);          
                formData.append('sub_occupation',this.sub_occupation);          
                formData.append('designation',this.designation);
                formData.append('organization_name',this.organization_name);  
                formData.append('relation',this.relation);  
                formData.append('annual_income',this.annual_income);
                formData.append('count',this.inputs.length);

                for(let i=0; i<this.inputs.length;i++)
                { 
                    if(typeof this.inputs[i]['qualification_id'] !== "undefined")
                    {
                        formData.append('qualification_id'+i,this.inputs[i]['qualification_id']);
                    }
                    else
                    {
                        formData.append('qualification_id'+i,'');
                    }
                }

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

            checkInArray(array,value) 
            {
                if( array.includes(value) )
                {
                    return true;
                }
            },

            addRow() 
            {
                this.inputs.push({
                    qualification_id:'',
                });
            },

            deleteRow(index) 
            {
                this.inputs.splice(index,1);
            }
        },
    
        created()
        {
            //
            this.getData();
        }
    }
</script>