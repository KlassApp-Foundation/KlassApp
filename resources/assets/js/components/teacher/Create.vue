<template>
  <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3 bg-white shadow" v-bind:class="[this.profile_tab==1?'block' :'hidden']">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="firstname" class="tw-form-label">First Name<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
          
            <input type="text" class="tw-form-control w-full " id="firstname" v-model="firstname" name="firstname" Placeholder="First Name">
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
            
            <input type="text" v-model="lastname" name="lastname" id="lastname" class="tw-form-control w-full " Placeholder="Last Name">
          </div>
          <span v-if="errors.lastname" class="text-red-500 text-xs font-semibold">{{errors.lastname[0]}}</span>
        </div> 
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="date_of_birth" class="tw-form-label">Date Of Birth<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="date" v-model="date_of_birth" name="date_of_birth" id="date_of_birth" class="tw-form-control w-full">
          </div>
          <span v-if="errors.date_of_birth" class="text-red-500 text-xs font-semibold">{{errors.date_of_birth[0]}}</span>
        </div> 
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/4">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="employee_id" class="tw-form-label">Employee ID<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="employee_id" name="employee_id" id="employee_id" class="tw-form-control w-full" placeholder="Employee ID">
          </div>
          <span v-if="errors.employee_id" class="text-red-500 text-xs font-semibold">{{errors.employee_id[0]}}</span>
        </div> 
      </div>

      <div class="tw-form-group w-full lg:w-1/4">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="designation" class="tw-form-label">Designation<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" id="designation" v-model="designation" name="designation" >
              <option value="" disabled>Select Designation</option>
              <option value="" v-for="designation in designationlist" v-bind:value="designation.id">{{ designation.name }}</option>
            </select>
          </div>
          <span v-if="errors.designation" class="text-red-500 text-xs font-semibold">{{errors.designation[0]}}</span>
        </div> 
      </div>

      <div class="tw-form-group w-full lg:w-1/4" v-if="this.designation == 'others'">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="sub_designation" class="tw-form-label">Sub-Designation<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="sub_designation" name="sub_designation" id="sub_designation" class="tw-form-control w-full" placeholder="Sub Designation">
          </div>
          <span v-if="errors.sub_designation" class="text-red-500 text-xs font-semibold">{{errors.sub_designation[0]}}</span>
        </div> 
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="mobile_no" class="tw-form-label">Mobile Number<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            
            <input type="text" v-model="mobile_no" name="mobile_no" id="mobile_no" class="tw-form-control w-full " placeholder="Mobile Number"> 
          </div>
          <span v-if="errors.mobile_no" class="text-red-500 text-xs font-semibold">{{errors.mobile_no[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="email" class="tw-form-label">Email ID <span v-if="this.staff=='teaching'" class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
           
            <input type="text" v-model="email" name="email" id="email" class="tw-form-control w-full " placeholder="Email ID">  
          </div>
          <span v-if="errors.email" class="text-red-500 text-xs font-semibold">{{errors.email[0]}}</span>
        </div>
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/5">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="gender" class="tw-form-label">Gender<span class="text-red-500">*</span></label>
          </div>

          <div class="flex tw-form-control">
            <div class="w-1/2 flex items-center  mr-2 lg:mr-8 md:mr-8"> 
              <input type="radio" v-model="gender" name="gender" class="tw-form-control" id="gender1" value="male">
              <span class="text-sm mx-1">Male</span>
            </div>
            <div class="w-1/2 flex items-center lg:mr-8 md:mr-8"> 
              <input type="radio" v-model="gender" name="gender" class="tw-form-control" id="gender2" value="female">

              <span class="text-sm mx-1">Female</span>
            </div>
          </div>
          <span v-if="errors.gender" class="text-red-500 text-xs font-semibold">{{errors.gender[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-2/5">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="blood_group" class="tw-form-label">Blood Group<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" id="blood_group" v-model="blood_group" name="blood_group">
              <option value="" disabled>Select Blood Group</option>
              <option v-for="blood_group in blood_groups" v-bind:value="blood_group.num">{{ blood_group.name }}</option>
            </select>
          </div>
          <span v-if="errors.blood_group" class="text-red-500 text-xs font-semibold">{{errors.blood_group[0]}}</span>
        </div> 
      </div>
      
      <div class="tw-form-group w-full lg:w-2/5">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="aadhar_number" class="tw-form-label">Aadhaar Number</label>
          </div>
          <div class="mb-2">
            <input type="text" class="tw-form-control w-full" id="aadhar_number" v-model="aadhar_number" name="aadhar_number" Placeholder="Aadhar Number">
          </div>
          <span v-if="errors.aadhar_number" class="text-red-500 text-xs font-semibold">{{errors.aadhar_number[0]}}</span>
        </div> 
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="marital_status" class="tw-form-label">Marital Status<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select name="marital_status" v-model="marital_status" id="marital_status" class="tw-form-control w-full">
              <option value="" disabled>Select Marital Status</option>
              <option v-for="marital in maritalList" v-bind:value="marital.id">{{ marital.name }}</option>
            </select>
          </div>
          <span v-if="errors.marital_status" class="text-red-500 text-xs font-semibold">{{errors.marital_status[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="avatar" class="tw-form-label">Avatar</label>
          </div>
          <div class="mb-2 flex">
            <!-- <input type="file" name="avatar" @change="OnFileSelected" id="avatar" class="tw-form-control w-full"> -->
            <div class="" v-if="avatar != ''">
              <img :src="avatar" style="width: 100px;height: 100px;" @load="uploadImage">
            </div>
            <div class="" v-else>
              <img :src='url+"/uploads/user/avatar/default-user.jpg"' style="width: 100px;height: 100px;">
            </div>
            <VueImageUploadCroppie :defaultImage.sync="avatar" :height="100" :width="100" :trans="trans"></VueImageUploadCroppie>
          </div>
          <span v-if="errors.avatar" class="text-red-500 text-xs font-semibold">{{errors.avatar[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="joining_date" class="tw-form-label">Joining date<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="date" name="joining_date" v-model="joining_date" id="joining_date" class="tw-form-control w-full">
          </div>
          <span v-if="errors.joining_date" class="text-red-500 text-xs font-semibold">{{errors.joining_date[0]}}</span>
        </div> 
      </div> 
    </div>

    <div class="flex flex-col lg:flex-row">
        <div class="tw-form-group w-full lg:w-1/3">
            <div class="lg:mr-8 md:mr-8">
                <div class="mb-2">
                    <label for="job_type" class="tw-form-label">Job Type<span class="text-red-500">*</span></label>
                </div>
                <div class="flex tw-form-control">
                    <div class="w-1/2 flex items-center  mr-2 lg:mr-8 md:mr-8"> 
                        <input type="radio" v-model="job_type" name="job_type" class="tw-form-control" id="full_time" value="full_time">
                        <span class="text-sm mx-1">Full Time</span>
                    </div>
                    <div class="w-1/2 flex items-center lg:mr-8 md:mr-8"> 
                        <input type="radio" v-model="job_type" name="job_type" class="tw-form-control" id="part_time" value="part_time">
                        <span class="text-sm mx-1">Part Time</span>
                    </div>
                </div>
                <span v-if="errors.job_type" class="text-red-500 text-xs font-semibold">{{errors.job_type[0]}}</span>
            </div> 
        </div>

        <div class="tw-form-group w-full lg:w-1/3">
            <div class="lg:mr-8 md:mr-8">
                <div class="mb-2">
                    <label for="interested_in" class="tw-form-label">Interested In</label>
                </div>
                <div class="mb-2">
                    <textarea type="text" name="interested_in" v-model="interested_in" id="interested_in" class="tw-form-control w-full" placeholder="Interested In"></textarea>
                </div>
                <span v-if="errors.interested_in" class="text-red-500 text-xs font-semibold">{{ errors.interested_in[0] }}</span>
            </div> 
        </div>

      <div class="tw-form-group w-full lg:w-1/3" v-if=" this.designation != '' && this.designation != 'principal' && this.designation != 'vice_principal' ">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="reporting_to" class="tw-form-label">Report Tocc</label>
          </div>
          <div class="mb-2" v-if="this.designation == 'head_of_the_department' ">
            <select name="reporting_to" v-model="reporting_to" id="reporting_to" class="tw-form-control w-full">
              <option value="" disabled>Report To</option>
              <option v-for="list in HODList" v-bind:value="list.id">{{ list.fullname }}</option>
            </select>
          </div>
          <div class="mb-2" v-else>
            <select name="reporting_to" v-model="reporting_to" id="reporting_to" class="tw-form-control w-full">
              <option value="" disabled>Report To</option>
              <option v-for="list in principalList" v-bind:value="list.id">{{ list.fullname }}</option>
            </select>
          </div>
          <span v-if="errors.reporting_to" class="text-red-500 text-xs font-semibold">{{errors.reporting_to[0]}}</span>
        </div>
      </div>
    </div>

    <div class="my-6">
      <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="submitForm('2')">Submit</a>
      <a href="#" class="btn-reset reset-btn" @click="resetForm()">Reset</a>
    </div>
  </div>
</template>

<script> 
  import { bus } from "../../app";
  import PortalVue from "portal-vue";
  import VueImageUploadCroppi from 'vue-image-upload-croppie'
  export default {
    components: 
    { 
      VueImageUploadCroppi,
    },
    props:['url','staff'],
    data(){
      return {
        profile_tab:'1',
        user:[],
        academic_year_id:'',
        firstname:'',
        lastname:'',
        mobile_no:'',
        email:'',
        gender:'',
        date_of_birth:'',
        blood_group:'',
        aadhar_number:'',
        employee_id:'',
        joining_date:'',
        designation:'',
        sub_designation:'',
        job_type:'',
        avatar:'',
        marital_status:'',
        reporting_to:'',
        interested_in:'',
        errors:[],
        success:null,
        HODList:[],
        principalList:[],
        maritalList:[],
        blood_groups:[],
        designationlist:[],
        trans: { 
          'cropImage': 'Choose File', 
          'chooseImage':'Choose File', 
          'confirmCutting': 'Save File'
        },
      }
    },

    methods:
    {
      getData()
      {
        axios.get('/admin/teacher').then(response => {
          this.user = response.data;
          this.setData();   
        });
      },

      setData()
      {
        if(Object.keys(this.user).length>0)
        {
          this.academic_year_id   = this.user.academic_year_id;
          if(this.academic_year_id == null)
          {
            alert("Add Academic Year")
          }
          else
          {
            this.date_of_birth    = this.user.date_of_birth;
            this.employee_id      = this.user.employee_id;
            this.blood_groups     = this.user.blood_groups;
            this.maritalList      = this.user.maritalList;
            this.HODList          = this.user.HODList;
            this.principalList    = this.user.principalList;
            if(this.staff=="non_teaching")
            {
             this.designationlist  = this.user.nonteachinglist;
            }
            else
            {
              this.designationlist  = this.user.designationlist;
            }
          }
        }
      },

      setProfileTab(val)
      {
        this.profile_tab=val;
        bus.$emit("dataProfileTab", this.profile_tab);
      },

      resetForm()
      {
        this.firstname='';
        this.lastname='';
        this.mobile_no='';
        this.email='';
        this.gender='';
        this.date_of_birth='';
        this.blood_group='';
        this.aadhar_number='';
        this.joining_date='';
        //this.employee_id='';
        this.designation='';
        this.sub_designation='';
        this.avatar='';
        this.marital_status='';
        this.interested_in='';
      }, 

      uploadImage()
      {
        this.errors=[];
        this.success=null; 

        let formData = new FormData(); 

        formData.append('avatar',this.avatar);

        axios.post('/admin/teacher/add/validationAvatar',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {
          //this.success = response.data.success; 
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

      submitForm(val)
      {
        this.errors=[];
        this.success=null; 

        let formData = new FormData(); 

        formData.append('staff_status',this.staff);
        formData.append('firstname',this.firstname);          
        formData.append('lastname',this.lastname);
        formData.append('mobile_no',this.mobile_no);          
        formData.append('email',this.email);           
        formData.append('gender',this.gender);          
        formData.append('date_of_birth',this.date_of_birth);       
        formData.append('blood_group',this.blood_group);
        formData.append('aadhar_number',this.aadhar_number);  
        formData.append('joining_date',this.joining_date);  
        formData.append('employee_id',this.employee_id);  
        formData.append('designation',this.designation);  
        formData.append('sub_designation',this.sub_designation); 
        //formData.append('avatar',this.avatar);
        formData.append('marital_status',this.marital_status);
        formData.append('reporting_to',this.reporting_to);
        formData.append('job_type',this.job_type);
        formData.append('interested_in',this.interested_in);

        axios.post('/admin/teacher/add/validationProfile',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.setProfileTab(val); 
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

      OnFileSelected(event)
      {
        this.avatar = event.target.files[0];
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
    }
  }

</script>