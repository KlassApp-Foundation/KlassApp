<template>
<div>

  <div class="bg-white shadow px-4 py-3">
    <div>
      <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>     

 <!-- start -->
 <div class="flex flex-col lg:flex-row md:flex-row">
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label for="calling_purpose" class="tw-form-label">Calling Purpose</label>
            </div>
            <div class="mb-2">
              <select class="tw-form-control w-full" id="calling_purpose" v-model="calling_purpose" name="calling_purpose">
                <option value="" disabled>Calling Purpose</option>
                <option value="study">Study</option>
                <option value="fee reminder">Fee Reminder</option>
                <option value="transport">Transport</option>
                <option value="circular">Circular</option>
              </select>
            </div>
            <span v-if="errors.calling_purpose" class="text-red-500 text-xs font-semibold">{{errors.calling_purpose[0]}}</span>
          </div> 
        </div>
      </div>


       <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label for="call_type" class="tw-form-label">Call Type</label>
            </div>
            <div class="mb-2">
              <select class="tw-form-control w-full" id="call_type" v-model="call_type" name="call_type">
                <option value="" disabled>Call Type</option>
                <option value="incoming">Incoming</option>
                <option value="outgoing">Outgoing</option>
              </select>
            </div>
            <span v-if="errors.call_type" class="text-red-500 text-xs font-semibold">{{errors.call_type[0]}}</span>
          </div> 
        </div>
      </div>
      </div>
<!-- end -->
<!-- start -->
<div class="flex flex-col lg:flex-row md:flex-row">
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
                <label for="name" class="tw-form-label">Name</label>
            </div>
            <div class="mb-2">
              <input type="text" name="name" id="name" v-model="name" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.name" class="text-red-500 text-xs font-semibold">{{errors.name[0]}}</span>
            </div>
          </div>     
        </div>
      </div>

       <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
                <label for="incoming_number" class="tw-form-label">Incoming Number</label>
            </div>
            <div class="mb-2">
              <input type="text" name="incoming_number" id="incoming_number" v-model="incoming_number" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.incoming_number" class="text-red-500 text-xs font-semibold">{{errors.incoming_number[0]}}</span>
            </div>
          </div>     
        </div>
      </div>

       <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
                <label for="outgoing_number" class="tw-form-label">Outgoing Number</label>
            </div>
            <div class="mb-2">
              <input type="text" name="outgoing_number" id="outgoing_number" v-model="outgoing_number" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.outgoing_number" class="text-red-500 text-xs font-semibold">{{errors.outgoing_number[0]}}</span>
            </div>
          </div>     
        </div>
      </div>
      </div>
<!-- end -->
<!-- start -->
<div class="flex flex-col lg:flex-row md:flex-row">
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
                <label for="call_date" class="tw-form-label">Date</label>
            </div>
            <div class="mb-2">
              <input type="date"  name="call_date" v-model="call_date" class="tw-form-control w-full" id="call_date">
              <span v-if="errors.call_date" class="text-red-500 text-xs font-semibold">{{errors.call_date[0]}}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
                <label for="start_time" class="tw-form-label">Start Time</label>
            </div>
            <div class="mb-2">
              <input type="time"  name="start_time" v-model="start_time" class="tw-form-control w-full" id="start_time">
              <span v-if="errors.start_time" class="text-red-500 text-xs font-semibold">{{errors.start_time[0]}}</span>
            </div>
          </div>
        </div>
      </div>

        <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
                <label for="end_time" class="tw-form-label">End Time</label>
            </div>
            <div class="mb-2">
              <input type="time"  name="end_time" v-model="end_time" class="tw-form-control w-full" id="end_time">
              <span v-if="errors.end_time" class="text-red-500 text-xs font-semibold">{{errors.end_time[0]}}</span>
            </div>
          </div>
        </div>
      </div>
      </div>
<!-- end -->
      <div class="w-full lg:w-1/3 md:w-1/3">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
                <label for="description" class="tw-form-label">Description</label>
            </div>
            <div class="mb-2">
              <textarea type="textarea"  name="description" v-model="description" class="tw-form-control w-full" id="description"></textarea>
              <span v-if="errors.description" class="text-red-500 text-xs font-semibold">{{errors.description[0]}}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="addCallLog()">Save</a>
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
        calllog:[],
      
        calling_purpose:'',
        call_type:'',
        name:'',
        incoming_number:'',
        outgoing_number:'',
        call_date:'',
        start_time:'',
        end_time:'',
        description:'',
    
        errors:[],
        success:null,
      }
    },
        
    methods:
    {
      reset()
      {
        this.calling_purpose='';
        this.call_type='';
        this.name='';
        this.incoming_number='';  
        this.outgoing_number='';  
        this.call_date='';  
        this.start_time='';  
        this.end_time='';  
        this.description='';  
          
      }, 

      addCallLog()
      {
        this.errors=[];
        this.success=null; 

        let formData=new FormData();

        formData.append('calling_purpose',this.calling_purpose);                 
        formData.append('call_type',this.call_type);                 
        formData.append('name',this.name);                 
        formData.append('incoming_number',this.incoming_number);                 
        formData.append('outgoing_number',this.outgoing_number);          
        formData.append('call_date',this.call_date);          
        formData.append('start_time',this.start_time);          
        formData.append('end_time',this.end_time);          
        formData.append('description',this.description);                    
                 
              
        axios.post('/teacher/calllog/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.success;
          this.reset();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

    },
    created()
    {
      
    }
  }
</script>
