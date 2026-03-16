<template>
  <div class="form-wrapper">
    <div v-if="this.success!=null" class="alert alert-success mx-auto" id="success-alert">{{this.success}}</div>
    <div class="flex flex-col lg:flex-row md:flex-row my-6">
      <p class="w-full lg:w-64 md:w-64 py-2">Name </p>
      <div class="flex-grow relative">
        <input type="text" name="fullname" v-model="fullname" class="w-full border border-gray-500 p-2 lg:mx-3 md:mx-3 focus:outline-none leading-none">
        <div class="absolute">
          <p class="text-xs text-red-500 text-left mx-3 mt-1" v-if="errors.fullname">{{errors.fullname[0]}}</p>
        </div>
      </div>
    </div>
    <div class="flex flex-col lg:flex-row md:flex-row my-6">
      <p class="w-full lg:w-64 md:w-64 py-2">School / Organization Name</p>
      <div class="flex-grow relative">
        <input type="text" name="serve_at" v-model="serve_at" class="w-full  border border-gray-500 p-2 lg:mx-3 md:mx-3 focus:outline-none leading-none">
        <div class="absolute">
          <p class="text-xs text-red-500 text-left mx-3 mt-1" v-if="errors.serve_at">{{errors.serve_at[0]}}</p>
        </div>
      </div>
    </div>
    <div class="flex flex-col lg:flex-row md:flex-row my-6">
      <p class="w-full lg:w-64 md:w-64 py-2">Role / Designation </p>
      <div class="flex-grow relative">
        <input type="text" name="role" v-model="role" class="w-full border border-gray-500 p-2 lg:mx-3 md:mx-3 focus:outline-none leading-none">
        <div class="absolute">
          <p class="text-red-500 text-xs text-left mx-3 mt-1" v-if="errors.role">{{errors.role[0]}}</p>
        </div>
      </div>
    </div>
    <div class="flex flex-col lg:flex-row md:flex-row my-6">
      <p class="w-full lg:w-64 md:w-64 py-2">Phone</p>
      <div class="flex-grow relative">
        <input type="text" name="contact_no" v-model="contact_no" class="w-full border border-gray-500 p-2 lg:mx-3 md:mx-3 focus:outline-none leading-none">
        <div class="absolute">
          <p class="text-xs text-red-500 text-left mx-3 mt-1" v-if="errors.contact_no">{{errors.contact_no[0]}}</p>
        </div>
      </div>
    </div>
    <div class="flex flex-col lg:flex-row mf:flex-row my-6">
      <p class="w-64 py-2">Email</p>
      <div class="flex-grow relative">
        <input type="text" name="emailid" v-model="emailid" class="w-full border border-gray-500 p-2 lg:mx-3 md:mx-3 focus:outline-none leading-none">
        <div class="absolute">
          <p class="text-xs text-red-500 text-left mx-3 mt-1" v-if="errors.emailid">{{errors.emailid[0]}}</p>
        </div>
      </div>
    </div>
    <div class="flex flex-col lg:flex-row md:flex-row my-6">
      <p class="w-full lg:w-64 md:flex-row py-2">Where did you hear about GegoK12 ?</p>
      <div class="flex-grow relative">
        <select name="select" v-model="select" class="block w-full border border-gray-500 p-2 lg:mx-3 md:mx-3 focus:outline-none leading-none " id="grid-state">
          <option value="" disabled>Please select</option>
          <option v-for="selects in selectlist" v-bind:value="selects.id">{{ selects.name }}</option>
        </select>
        <div class="absolute">
          <p class="text-xs text-red-500 text-left mx-3 mt-1 bounce" v-if="errors.select">{{errors.select[0]}}</p>
        </div>
      </div>
    </div>
    <div class="flex flex-col lg:flex-row md:flex-row my-6">
      <p class="w-full lg:w-64 md:w-64 py-2">Query</p>
      <div class="flex-grow relative">
        <textarea name="message"  v-model="message" class="w-full border border-gray-500 p-2 lg:mx-3 md:mx-3 focus:outline-none leading-none"></textarea>
        <div class="absolute">
          <p class="text-xs text-red-500 text-left mx-3 mt-1" v-if="errors.message">{{errors.message[0]}}</p>
        </div>
      </div>
    </div>
    <div class="flex my-8">
      <a class="lg:ml-64 md:ml-64 btn bg-red-600 px-4 py-2 text-base text-white rounded" @click="checkForm()">Submit</a>
    </div>
  </div>
</template>

<script> 
  export default {
    props:['url'],
    data(){
      return {
        fullname:'',
        serve_at:'',
        role:'',
        contact_no:'',
        emailid:'',
        select:'',
        message:'',
        selectlist:[{ id : 'socialmedia' , name : 'Social media'} , { id : 'conference' , name : 'Conference'}  , { id : 'wordofmouth' , name : 'Word of mouth'}  , { id : 'search-engine' , name : 'Search Engines'}  , { id : 'press-release' , name : 'Press Ads / News'}   , { id : 'others' , name : 'Others'} ],
        errors:[],
        success:null,
      }
    },
    methods:
    {
      resetForm()
      {
        this.fullname='';
        this.serve_at='';
        this.role='';
        this.contact_no='';
        this.emailid='';
        this.select='';
        this.message='';
      }, 

      checkForm()
      {
        this.errors=[];
        this.success=null;    
        let formData=new FormData();
          formData.append('fullname',this.fullname);  
          formData.append('serve_at',this.serve_at);  
          formData.append('role',this.role);  
          formData.append('contact_no',this.contact_no);  
          formData.append('emailid',this.emailid);  
          formData.append('select',this.select);            
          formData.append('message',this.message);            
        axios.post(this.url+'/contact',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {  
          this.success = response.data.success;
          this.resetForm();   
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },
    },
  }
</script>

<style>
  @keyframes bounce {
  0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
  40% {transform: translateY(-30px);}
  60% {transform: translateY(-15px);}
}
.bounce {
  animation-name: bounce;
}

</style>

