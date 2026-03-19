<template>
<div>

  <div class="bg-white shadow px-4 py-3">
    <div>
      <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
<!-- start -->
<div class="flex flex-col lg:flex-row md:flex-row items-end">
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="">
            <div class="mb-2">
                <label for="name" class="tw-form-label">Visitor Name</label>
            </div>
            <div class="mb-2">
              <input type="text" name="name" id="name" v-model="name" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.name" class="text-red-500 text-xs font-semibold">{{errors.name[0]}}</span>
            </div>
          </div>     
        </div>
      </div>


    <!--radio button-->
<div class="w-full lg:w-1/3 md:w-1/3">
<div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
        <div class="">
         <!--  <div class="mb-2">
            <label for="gender" class="tw-form-label">Gender<span class="text-red-500">*</span></label>
          </div> -->

          <div class="flex tw-form-control">
            <div class="w-1/2 flex items-center  mr-2 lg:mr-8 md:mr-8"> 
              <input type="radio" v-model="relation" name="relation" class="tw-form-control" id="parent" value="parent">
              <span class="text-sm mx-1">Parent</span>
            </div>
            <div class="w-1/2 flex items-center lg:mr-8 md:mr-8"> 
              <input type="radio" v-model="relation" name="relation" class="tw-form-control" id="other" value="other">

              <span class="text-sm mx-1">Other</span>
            </div>
          </div>
          <span v-if="errors.relation" class="text-red-500 text-xs font-semibold">{{errors.relation[0]}}</span>
        </div>
      </div>
      </div>

       <div class="w-full lg:w-1/3 md:w-1/3" id="other">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="">
            <div class="mb-2">
                <label for="contact_number" class="tw-form-label">Contact Number</label>
            </div>
            <div class="mb-2">
              <input type="text" name="contact_number" id="contact_number" v-model="contact_number" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.contact_number" class="text-red-500 text-xs font-semibold">{{errors.contact_number[0]}}</span>
            </div>
          </div>     
        </div>
      </div>

</div>
  <!--radio button-->
<!-- end -->

      <div class="flex flex-col lg:flex-row md:flex-row" v-if="this.relation=='parent'">
      <div class="w-full lg:w-1/3 md:w-1/3">
          <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
            <div class="">
              <div class="mb-2">
                <label for="student_id" class="tw-form-label">Student</label>
              </div>
              <div class="mb-2">
                <select class="tw-form-control w-full" id="student_id" v-model="student_id" name="student_id">
                  <option value="" disabled>Select Student</option>
                 <option value="" v-for="student in studentlist" v-bind:value="student.id">{{student.firstname}}({{student.class}})</option>
                </select>
                  <span v-if="errors.student_id" class="text-red-500 text-xs font-semibold">{{errors.student_id[0]}}</span>
              </div>
            
            </div> 
          </div>
          </div>
           <div class="w-full lg:w-1/3 md:w-1/3" id="parent">
              <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
                <div class="">
                  <div class="mb-2">
                      <label for="relation_with_student" class="tw-form-label">Relation With Student</label>
                  </div>
                  <div class="mb-2">
                    <select class="tw-form-control w-full" id="relation_with_student" v-model="relation_with_student" name="relation_with_student">
                <option value="" disabled>Relation With Student</option>
                <option value="father">Father</option>
                <option value="mother">Mother</option>
                <option value="siblings">Siblings</option>
                <option value="others">Others</option>
               
              </select>
                         <span v-if="errors.relation_with_student" class="text-red-500 text-xs font-semibold">{{errors.relation_with_student[0]}}</span>
                  </div>
                </div>     
              </div>
            </div>

             <div class="w-full lg:w-1/3 md:w-1/3" v-if="this.relation_with_student=='others'">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="">
            <div class="mb-2">
                <label for="relation_name" class="tw-form-label">Relation Name</label>
            </div>
            <div class="mb-2">
              <input type="text" name="relation_name" id="relation_name" v-model="relation_name" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.relation_name" class="text-red-500 text-xs font-semibold">{{errors.relation_name[0]}}</span>
            </div>
          </div>     
        </div>
        </div>
     </div> 
     <!-- start -->
      <div class="flex flex-col lg:flex-row md:flex-row" v-if="this.relation=='other'">
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="">
            <div class="mb-2">
                <label for="company_name" class="tw-form-label">Company Name</label>
            </div>
            <div class="mb-2">
              <input type="text" name="company_name" id="company_name" v-model="company_name" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.company_name" class="text-red-500 text-xs font-semibold">{{errors.company_name[0]}}</span>
            </div>
          </div>     
        </div>
        </div>
    

     

      <div class="w-full lg:w-1/3 md:w-1/3" id="other">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="">
            <div class="mb-2">
                <label for="address" class="tw-form-label">Address</label>
            </div>
            <div class="mb-2">
              <textarea type="text" name="address" id="address" v-model="address" class="tw-form-control w-full" rows="3"></textarea>
                   <span v-if="errors.address" class="text-red-500 text-xs font-semibold">{{errors.address[0]}}</span>
            </div>
          </div>     
        </div>
      </div>
    </div>
  <!-- end -->
<!-- start -->    
    <div class="flex flex-col lg:flex-row md:flex-row items-center">
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 w-full">
            <div class="mb-2">
                <label for="number_of_visitors" class="tw-form-label">Visitor Count</label>
            </div>
            <div class="mb-2">
              <input type="number" name="number_of_visitors" id="number_of_visitors" v-model="number_of_visitors" class="tw-form-control w-full">
                   <span v-if="errors.number_of_visitors" class="text-red-500 text-xs font-semibold">{{errors.number_of_visitors[0]}}</span>
            </div>
          </div>     
        </div>
      </div>

      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 w-full">
            <div class="mb-2">
              <label for="visiting_purpose" class="tw-form-label">Visiting Purpose</label>
            </div>
            <div class="mb-2">
              <select class="tw-form-control w-full" id="visiting_purpose" v-model="visiting_purpose" name="visiting_purpose">
                <option value="" disabled>Visiting Purpose</option>
                <option value="enquiry">Enquiry</option>
                <option value="admission">Admission</option>
                <option value="feepayment">Fee Payment</option>
                <option value="parentsmeeting">Parents Meeting</option>
                <option value="tc">Transfer Certificate</option>
                <option value="complaint">Complaint</option>
                <option value="business">Business</option>
                <option value="others">Others</option>
              </select>
            </div>
            <span v-if="errors.visiting_purpose" class="text-red-500 text-xs font-semibold">{{errors.visiting_purpose[0]}}</span>
          </div> 
        </div>
      </div>


      <div class="w-full lg:w-1/3 md:w-1/3 ">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 w-full">
            <div class="mb-2">
              <label for="employee_id" class="tw-form-label">Whom To Meet</label>
            </div>
            <div class="mb-2">
              <select class="tw-form-control w-full" id="employee_id" v-model="employee_id" name="employee_id">
                <option value="" disabled>Select Staff</option>
               <option value="" v-for="teacher in teacherlist" v-bind:value="teacher.id">{{teacher.name}}</option>
              </select>
                <span v-if="errors.employee_id" class="text-red-500 text-xs font-semibold">{{errors.employee_id[0]}}</span>
            </div>
          
          </div> 
        </div>
      </div>
</div>
   <!--  end -->
   <!-- start -->
   <div class="flex flex-col lg:flex-row md:flex-row items-center">
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 w-full">
            <div class="mb-2">
                <label for="date_of_visit" class="tw-form-label">Date of visit</label>
            </div>
            <div class="mb-2">
              <input type="date"  name="date_of_visit" v-model="date_of_visit" class="tw-form-control w-full" id="date_of_visit">
              <span v-if="errors.date_of_visit" class="text-red-500 text-xs font-semibold">{{errors.date_of_visit[0]}}</span>
            </div>
          </div>
        </div>
      </div>

        <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 w-full">
            <div class="mb-2">
                <label for="entry_time" class="tw-form-label">Entry Time</label>
            </div>
            <div class="mb-2">
              <input type="time"  name="entry_time" v-model="entry_time" class="tw-form-control w-full" id="entry_time">
              <span v-if="errors.entry_time" class="text-red-500 text-xs font-semibold">{{errors.entry_time[0]}}</span>
            </div>
          </div>
        </div>
      </div>

        <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 w-full">
            <div class="mb-2">
                <label for="exit_time" class="tw-form-label">Exit Time</label>
            </div>
            <div class="mb-2">
              <input type="time"  name="exit_time" v-model="exit_time" class="tw-form-control w-full" id="exit_time">
              <span v-if="errors.exit_time" class="text-red-500 text-xs font-semibold">{{errors.exit_time[0]}}</span>
            </div>
          </div>
        </div>
      </div>
      </div>
    <!-- end -->
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 w-full">
            <div class="mb-2">
                <label for="remark" class="tw-form-label">Remarks</label>
            </div>
            <div class="mb-2">
              <textarea type="textarea"  name="remark" v-model="remark" class="tw-form-control w-full" id="remark"></textarea>
              <span v-if="errors.remark" class="text-red-500 text-xs font-semibold">{{errors.remark[0]}}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="addVisitorLog()">Submit</a>
        <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>  
      </div>
    </div>
  </div>
  </div>
</template>

<script>
	export default {
    props:[],
    data(){
      return{
        visitorlog:[],
        studentlist:[],
        teacherlist:[],
        name:'',
        relation:'',
        company_name:'',
        contact_number:'',
        address:'',
        student_id:'',
        relation_with_student:'',
        relation_name:'',
        number_of_visitors:'',
        visiting_purpose:'',
        employee_id:'',
        date_of_visit:'',
        entry_time:'',
        exit_time:'',
        remark:'',
        errors:[],
        success:null,
      }
    },
        
    methods:
    {
      reset()
      {
        this.student_id='';
        this.employee_id='';
        this.visiting_purpose='';
        this.number_of_visitors='';  
        this.relation_with_student='';  
        this.relation_name='';  
        this.company_name='';  
        this.contact_number='';  
        this.address='';    
        this.entry_time='';  
        this.date_of_visit='';  
        this.exit_time='';  
        this.remark='';  
      }, 

      addVisitorLog()
      {
        this.errors=[];
        this.success=null; 

        let formData=new FormData();

        formData.append('student_id',this.student_id);                 
        formData.append('employee_id',this.employee_id);                 
        formData.append('name',this.name);                 
        formData.append('relation',this.relation);                 
        formData.append('visiting_purpose',this.visiting_purpose);                 
        formData.append('number_of_visitors',this.number_of_visitors);          
        formData.append('relation_with_student',this.relation_with_student);          
        formData.append('relation_name',this.relation_name);          
        formData.append('company_name',this.company_name);          
        formData.append('contact_number',this.contact_number);          
        formData.append('address',this.address);                    
        formData.append('date_of_visit',this.date_of_visit);          
        formData.append('entry_time',this.entry_time);          
        formData.append('exit_time',this.exit_time);          
        formData.append('remark',this.remark);          
              
        axios.post('/teacher/visitorlog/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.success;
          this.reset();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

      getData()
      {
        axios.get('/teacher/visitorlog/list').then(response => {
          this.list = response.data;
          //console.log(this.list);
          this.setData();
        });
      },
      setData()
      {
        if(Object.keys(this.list).length > 0)
        {
          this.studentlist = this.list.studentlist;
          //console.log(this.studentlist);
          this.teacherlist  = this.list.teacherlist;
          //console.log(this.teacherlist);
        }
      },
    },
    created()
    {
      this.getData();
    }
  }
</script>

<!-- <script type="text/javascript">
  $('#relation1').on('click', function(){
   
      $('#parent').removeClass('hidden').addClass('block');
      $('#other').removeClass('block').addClass('hidden');
    
  });
</script>

<script type="text/javascript">
  $('#relation2').on('click', function(){
   
      $('#other').removeClass('hidden').addClass('block');
      $('#parent').removeClass('block').addClass('hidden');
    
  });
</script> -->
