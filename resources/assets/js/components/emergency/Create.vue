<template>
<div>

  <div class="bg-white shadow px-4 py-3">
    <div>
	    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>     

 <!-- start -->
 <div class="mb-2">
               <label for="" class="tw-form-label">Select Receivers <span class="text-red-500">*</span></label>
            </div>
 <div class="flex flex-col lg:flex-row md:flex-row">


  <div class="flex">
        <div class="w-48 flex items-center py-2"> 
          <input type="radio" name="media" id="uploadmedia" v-model="message_type" value="teacher">
          <span class="text-sm mx-1">All Teachers</span>
        </div>
        <div class="w-48 flex items-center py-2"> 
          <input type="radio" name="media" id="studymedia" v-model="message_type" value="parent">
          <span class="text-sm mx-1">All  Parents</span>
        </div>
        <div class="w-48 flex items-center py-2"> 
          <input type="radio" name="media" id="studymedia" v-model="message_type" value="specific">
          <span class="text-sm mx-1">Specific</span>
        </div>
      </div>

   

      


      
      </div>
      <span v-if="errors.message_type"><p class="text-red-500 text-xs font-semibold">{{errors.message_type[0]}}</p></span>
<!-- end -->

<div class="w-full lg:w-1/3 md:w-1/2" v-show="this.message_type=='specific'">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
               <label for="standardLink_id" class="tw-form-label">Select Class <span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
             <select class="tw-form-control w-full" id="standardLink_id" v-model="standardLink_id" name="standardLink_id">
                <option value="" disabled>Select Class</option>
                <option v-for="standard in standardlist" v-bind:value="standard.id">{{ standard.standard_section }}</option>
              </select>
              
            </div>
            <span v-if="errors.standardLink_id"><p class="text-red-500 text-xs font-semibold">{{errors.standardLink_id[0]}}</p></span>
          </div> 
        </div>
      </div>

<!-- start -->
<!-- end -->
      <div class="w-full lg:w-1/3 md:w-1/2">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
                <label for="message" class="tw-form-label">Message</label>
            </div>
            <div class="mb-2">
              <textarea type="textarea"  name="message" v-model="message" class="tw-form-control w-full" id="message" placeholder="Enter Your message here..."></textarea>
              <span v-if="errors.message" class="text-red-500 text-xs font-semibold">{{errors.message[0]}}</span>
            </div>
          </div>
        </div>
      </div>
</div>
    	<div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="addCallLog()">Send</a>
    		<a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>	
    	</div>
	  </div>
  </div>
  </div>
</template>

<script>
	export default {
    props:['url','mode'],
    data(){
      return{
        standardlist:[],
        standardLink_id:'',
        message:'',
        message_type:'',
      
       
    
        errors:[],
        success:null,
      }
    },
        
    methods:
    {
      reset()
      {
        this.standardLink_id='';
        this.message='';
        this.message_type='';
          
      }, 

      addCallLog()
      {
        this.errors=[];
        this.success=null; 

        let formData=new FormData();
        formData.append('standardLink_id',this.standardLink_id);
        formData.append('message',this.message);          
        formData.append('message_type',this.message_type);             
                 
              
        axios.post(this.url+'/'+this.mode+'/emergency/send',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.message;
          this.reset();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

       getData()
      {
         axios.get('/'+this.mode+'/homework/list').then(response => {
                    this.standardlist = response.data.standardlist;

                });
      },

    },
    created()
    {
      this.getData();
    }
  }
</script>
