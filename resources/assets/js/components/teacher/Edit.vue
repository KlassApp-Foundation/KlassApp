<template>
  <div class="overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto " v-bind:class="[this.profile_tab==1?'block' :'hidden']">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
    <div class="bg-white shadow px-4 py-3">
    <div class="my-6">
      <div class="flex items-center">
        <img :src="user.avatar" class="img-responsive w-12 h-12 rounded-full">
        <div class="mx-2">
          <h2 class="font-semibold text-sm text-gray-700">{{ user.firstname }} {{ user.lastname }}</h2>
          <label class="tw-label cursor-pointer text-xs text-gray-600"> Change Avatar
            <input type="file" size="60" name="avatar" id="avatar" @change="OnFileSelected">
            <span v-if="errors.avatar" class="text-red-500 text-xs font-semibold">{{errors.avatar[0]}}</span>
          </label> 
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
           
            <input type="text" class="tw-form-control w-full" id="firstname" v-model="firstname" name="firstname">
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
          
            <input type="text" v-model="lastname" name="lastname" id="lastname" class="tw-form-control w-full">
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
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="employee_id" class="tw-form-label">Employee ID<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="employee_id" name="employee_id" id="employee_id" class="tw-form-control w-full">
          </div>
          <span v-if="errors.employee_id" class="text-red-500 text-xs font-semibold">{{errors.employee_id[0]}}</span>
        </div> 
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="designation" class="tw-form-label">Designation<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <select class="tw-form-control w-full" id="designation" v-model="designation" name="designation">
              <option value="" v-for="designation in designationlist" v-bind:value="designation.id">{{ designation.name }}</option>
            </select>
          </div>
          <span v-if="errors.designation" class="text-red-500 text-xs font-semibold">{{errors.designation[0]}}</span>
        </div> 
      </div>

      <div class="tw-form-group w-full lg:w-1/3" v-if="this.designation == 'others'">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="sub_designation" class="tw-form-label">Sub-Designation<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="text" v-model="sub_designation" name="sub_designation" id="sub_designation" class="tw-form-control w-full">
          </div>
          <span v-if="errors.sub_designation" class="text-red-500 text-xs font-semibold">{{errors.sub_designation[0]}}</span>
        </div> 
      </div>
    </div>

    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="gender" class="tw-form-label">Gender<span class="text-red-500">*</span></label>
          </div>
          <div class="flex">
            <div class="w-1/2 flex items-center tw-form-control mr-2 lg:mr-8 md:mr-8"> 
              <input type="radio" v-model="gender" name="gender" id="gender1" value="male">
              <span class="text-sm mx-1">Male</span>
            </div>
            <div class="w-1/2 flex items-center tw-form-control lg:mr-8 md:mr-8"> 
              <input type="radio" v-model="gender" name="gender" id="gender2" value="female">
              <span class="text-sm mx-1">Female</span>
            </div>
          </div>
          <span v-if="errors.gender" class="text-red-500 text-xs font-semibold">{{errors.gender[0]}}</span>
        </div>
      </div>

      <div class="tw-form-group w-full lg:w-1/3">
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
      
      <div class="tw-form-group w-full lg:w-1/3">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="aadhar_number" class="tw-form-label">Aadhaar Number</label>
          </div>
          <div class="mb-2">
            <input type="text" class="tw-form-control w-full" id="aadhar_number" v-model="aadhar_number" name="aadhar_number">
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
            <label for="joining_date" class="tw-form-label">Joining date<span class="text-red-500">*</span></label>
          </div>
          <div class="mb-2">
            <input type="date" name="joining_date" v-model="joining_date" id="joining_date" class="tw-form-control w-full">
          </div>
          <span v-if="errors.joining_date" class="text-red-500 text-xs font-semibold">{{errors.joining_date[0]}}</span>
        </div> 
      </div> 
    </div>

    <div class="flex flex-col lg:flex-row" >
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

      <div class="tw-form-group w-full lg:w-1/3" v-if=" this.designation != '' && this.designation != 'principal' && this.designation != 'vice_principal'">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="reporting_to" class="tw-form-label">Report To</label>
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
      <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="submitForm('2')">Submit</a>
      <a href="#" class="btn btn-reset reset-btn" @click="resetForm()">Reset</a>
    </div>
    </div>
  </div>
</template>

<script> 
  import { bus } from "../../app";
  import PortalVue from "portal-vue";
  export default {
    props:['url','teacher_name','staff'],
    data(){
      return {
        profile_tab:'1',
        user:[],
        firstname:'',
        lastname:'',
        gender:'',
        date_of_birth:'',
        blood_group:'',
        aadhar_number:'',
        employee_id:'',
        joining_date:'',
        designation:'',
        sub_designation:'',
        job_type:'',
        interested_in:'',
        avatar:'',
        marital_status:'',
        reporting_to:'',
        errors:[],
        success:null,
        maritalList:[],
        blood_groups:[],
        designationlist:[],
        HODList:[],
        principalList:[],
      }
    },
    methods:
    {
      getData()
      {
        axios.get('/admin/teacher/editTeacher/'+this.teacher_name).then(response => {
          this.user = response.data;
          this.setData();
          console.log(this.user)   
        });
      },

      setData()
      {
        if(Object.keys(this.user).length>0)
        {
          this.avatar             = this.user.avatar;
          this.firstname          = this.user.firstname;
          this.lastname           = this.user.lastname;
          this.date_of_birth      = this.user.date_of_birth;
          this.employee_id        = this.user.employee_id;
          this.designation        = this.user.designation;
          this.sub_designation    = this.user.sub_designation;
          this.job_type           = this.user.job_type;
          this.interested_in      = this.user.interested_in;
          this.gender             = this.user.gender;
          this.blood_group        = this.user.blood_group;
          this.aadhar_number      = this.user.aadhar_number;
          this.marital_status     = this.user.marital_status;
          this.joining_date       = this.user.joining_date;
          this.reporting_to       = this.user.reporting_to;
          //this.designationlist    = this.user.designationlist;
          this.blood_groups       = this.user.blood_groups;
          this.maritalList        = this.user.maritalList;
          this.HODList            = this.user.HODList;
          this.principalList      = this.user.principalList;
           if(this.staff=="teaching")
            {
              //alert('hi');
              this.designationlist  = this.user.designationlist;
            }
            else
            {
              this.designationlist  = this.user.nonteachinglist;
            }
        }
      },

      submitForm(val)
      {
        this.errors=[];
        this.success=null;  

        let formData=new FormData();

        formData.append('teacher_name',this.teacher_name);          
        formData.append('firstname',this.firstname);          
        formData.append('lastname',this.lastname);          
        formData.append('gender',this.gender);          
        formData.append('date_of_birth',this.date_of_birth);       
        formData.append('blood_group',this.blood_group);
        formData.append('aadhar_number',this.aadhar_number);  
        formData.append('joining_date',this.joining_date);  
        formData.append('employee_id',this.employee_id);  
        formData.append('designation',this.designation);  
        formData.append('sub_designation',this.sub_designation);
        formData.append('job_type',this.job_type);
        formData.append('interested_in',this.interested_in);
        formData.append('avatar',this.avatar);
        formData.append('marital_status',this.marital_status);
        formData.append('reporting_to',this.reporting_to);

        axios.post('/admin/teacher/edit/validationProfile/'+this.teacher_name,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.setProfileTab(val); 
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

      OnFileSelected(event)
      {
        this.avatar = event.target.files[0];
      },

      setProfileTab(val)
      {
        this.profile_tab=val;
        bus.$emit("dataProfileTab", this.profile_tab);
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

<style scoped>
  .tw-label{
    color:#3492e2;
  }
  .tw-label input[type="file"] {
    display: none;
  }
</style>