<template>
  <div class="bg-white shadow px-4 py-3">
    <div>
	    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>     

<!-- start -->
<div class="flex flex-col lg:flex-row md:flex-row items-end">
      <div class="w-full lg:w-1/3 md:w-1/2">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
              <label for="type" class="tw-form-label">Postal Type<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <select class="tw-form-control w-full" id="type" v-model="type" name="type">
                <option value="" disabled>Type</option>
                <option value="dispatch">Dispatch</option>
                <option value="receive">Receive</option>
              </select>
            </div>
            <span v-if="errors.type" class="text-red-500 text-xs font-semibold">{{errors.type[0]}}</span>
          </div> 
        </div>
      </div>


     <!--radio button-->
<div class="w-full lg:w-1/3 md:w-1/2">
<div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
        <div class="lg:mr-8">
          <label for="type" class="tw-form-label">Confidential<span class="text-red-500">*</span></label>
         <!--  <div class="mb-2">
            <label for="gender" class="tw-form-label">Gender<span class="text-red-500">*</span></label>
          </div> -->

          <div class="flex tw-form-control">
            <div class="w-1/2 flex items-center  mr-2 lg:mr-8 md:mr-8"> 
              <input type="radio" v-model="confidential" name="confidential" class="tw-form-control" id="yes" value="yes">
              <span class="text-sm mx-1">Yes</span>
            </div>
            <div class="w-1/2 flex items-center lg:mr-8 md:mr-8"> 
              <input type="radio" v-model="confidential" name="confidential" class="tw-form-control" id="no" value="no">

              <span class="text-sm mx-1">No</span>
            </div>
          </div>
          <span v-if="errors.confidential" class="text-red-500 text-xs font-semibold">{{errors.confidential[0]}}</span>
        </div>
      </div>
</div>
  <!--radio button-->
  <!--start-->

   <div class="w-full lg:w-1/3 md:w-1/2">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
              <label for="post_type" class="tw-form-label">Type<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <select class="tw-form-control w-full" id="post_type" v-model="post_type" name="post_type">
                <option value="" disabled>Post Type</option>
                <option value="post">Post</option>
                <option value="courier">Courier</option>
              </select>
            </div>
            <span v-if="errors.post_type" class="text-red-500 text-xs font-semibold">{{errors.post_type[0]}}</span>
          </div> 
        </div>
      </div>


  <!--end-->
</div>

     

<!-- end -->
<!-- start -->

 <div class="flex flex-wrap">
  <div class="w-full lg:w-1/3 md:w-1/2">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
                <label for="reference_number" class="tw-form-label">Reference Number</label>
            </div>
            <div class="mb-2">
              <input type="text" name="reference_number" id="reference_number" v-model="reference_number" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.reference_number" class="text-red-500 text-xs font-semibold">{{errors.reference_number[0]}}</span>
            </div>
          </div>     
        </div>
      </div>
       <div class="w-full lg:w-1/3 md:w-1/2" v-if="this.type=='dispatch'">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
                <label for="sender_title" class="tw-form-label">Sender Name<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <input type="text" name="sender_title" id="sender_title" v-model="sender_title" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.sender_title" class="text-red-500 text-xs font-semibold">{{errors.sender_title[0]}}</span>
            </div>
          </div>     
        </div>
      </div>
       <div class="w-full lg:w-1/3 md:w-1/2" v-if="this.type=='receive'">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
                <label for="receiver_title" class="tw-form-label">Receiver Name<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <input type="text" name="receiver_title" id="receiver_title" v-model="receiver_title" class="tw-form-control w-full" rows="3">
                   <span v-if="errors.receiver_title" class="text-red-500 text-xs font-semibold">{{errors.receiver_title[0]}}</span>
            </div>
          </div>     
        </div>
      </div>
      <!-- </div> -->
<!-- end -->
<!-- start -->
<!-- <div class="flex flex-col lg:flex-row md:flex-row"> -->
       <div class="w-full lg:w-1/3 md:w-1/2" v-if="this.type=='dispatch'">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
                <label for="sender_address" class="tw-form-label">Sender Address<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <textarea type="text" name="sender_address" id="sender_address" v-model="sender_address" class="tw-form-control w-full" rows="3"></textarea>
                   <span v-if="errors.sender_address" class="text-red-500 text-xs font-semibold">{{errors.sender_address[0]}}</span>
            </div>
          </div>     
        </div>
      </div>

      

       <div class="w-full lg:w-1/3 md:w-1/2" v-if="this.type=='receive'">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
                <label for="receiver_address" class="tw-form-label">Receiver Address<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <textarea type="text" name="receiver_address" id="receiver_address" v-model="receiver_address" class="tw-form-control w-full" rows="3"></textarea>
                   <span v-if="errors.receiver_address" class="text-red-500 text-xs font-semibold">{{errors.receiver_address[0]}}</span>
            </div>
          </div>     
        </div>
      </div>
      <!-- </div> -->
<!-- end -->
<!-- start -->
<!-- <div class="flex flex-col lg:flex-row md:flex-row"> -->

  <div class="w-full lg:w-1/3 md:w-1/2">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
                <label for="description" class="tw-form-label">Description<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <textarea type="textarea"  name="description" v-model="description" class="tw-form-control w-full" id="description"></textarea>
              <span v-if="errors.description" class="text-red-500 text-xs font-semibold">{{errors.description[0]}}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="w-full lg:w-1/3 md:w-1/2">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8">
            <div class="mb-2">
                <label for="postal_date" class="tw-form-label">Postal Date<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <input type="date"  name="postal_date" v-model="postal_date" class="tw-form-control w-full" id="postal_date">
              <span v-if="errors.postal_date" class="text-red-500 text-xs font-semibold">{{errors.postal_date[0]}}</span>
            </div>
          </div>
        </div>
      </div>


    

        <div class="w-full lg:w-1/3 md:w-1/2">
        <div class="tw-form-group w-full lg:w-3/4 md:w-5/6">
          <div class="lg:mr-8 ">
            <div class="mb-2">
                <label for="attachment" v-model="attachment" class="tw-form-label">Attachment</label>
            </div>
            <div class="mb-2">
              <input type="file" name="attachment" @change="OnFileSelected" id="attachment" class="tw-form-control w-full">
                 <span v-if="errors.attachment" class="text-red-500 text-xs font-semibold">{{errors.attachment[0]}}</span>
            </div>
         
          </div>
        </div>
      </div>
      </div>
<!-- end -->
    	<div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="addPostalRecord()">Save</a>
    		<a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>	
    	</div>
	  </div>
  </div>
</template>

<script>
	export default {
    props:['url','mode','postal_date','sender_address'],
    data(){
      return{
        postalrecord:[],
      
        type:'',
        post_type:'',
        reference_number:'',
        confidential:'',
        sender_title:'',
        sender_address:'',
        receiver_title:'',
        receiver_address:'',
        postal_date:'',
        description:'',
        attachment:'',

        errors:[],
        success:null,
      }
    },
        
    methods:
    {
      reset()
      {
        this.type='';
        this.post_type='';
        this.reference_number='';
        this.confidential='';
        this.sender_title='';  
        this.sender_address='';  
        this.receiver_title='';  
        this.receiver_address='';  
        this.postal_date='';  
        this.description='';  
        this.attachment='';  
          
      }, 

      addPostalRecord()
      {
        this.errors=[];
        this.success=null; 

        let formData=new FormData();

        formData.append('type',this.type);                 
        formData.append('post_type',this.post_type);                 
        formData.append('reference_number',this.reference_number);                 
        formData.append('confidential',this.confidential);                 
        formData.append('sender_title',this.sender_title);                 
        formData.append('sender_address',this.sender_address);          
        formData.append('receiver_title',this.receiver_title);          
        formData.append('receiver_address',this.receiver_address);          
        formData.append('postal_date',this.postal_date);          
        formData.append('description',this.description);                    
        formData.append('attachment',this.attachment);                    
                 
              
        axios.post(this.url+'/'+this.mode+'/postalrecord/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.success;
          this.reset();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

       OnFileSelected(event)
      {
        this.attachment = event.target.files[0];
      },
      getData()
      {
        axios.get(this.url+'/'+this.mode+'/visitorlog/list').then(response => {
          this.studentlist = response.data.studentlist;
          this.classlists = response.data.standardlist;
          this.postal_date=this.postal_date;
          this.sender_address=this.sender_address;
          this.teacherlist  = response.data.teacherlist;
          //console.log(this.list);
        
        });
      },

    },
    created()
    {
      this.getData();
    }
  }
</script>
