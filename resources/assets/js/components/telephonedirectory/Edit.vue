<template>
  <div class="bg-white shadow px-4 py-3">
    <div>
	    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

<!--start-->
      <div class="my-5">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <label for="name" class="tw-form-label">Name</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="name" v-model="name" class="tw-form-control w-full" id="name">
            <span v-if="errors.name" class="text-red-500 text-xs font-semibold">{{errors.name[0]}}</span>
          </div>
        </div>
        </div>
</div>

<!--end-->
<!--start-->

  <div class="my-5">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <label for="designation" class="tw-form-label">Designation</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="designation" v-model="designation" class="tw-form-control w-full" id="designation">
            <span v-if="errors.designation" class="text-red-500 text-xs font-semibold">{{errors.designation[0]}}</span>
          </div>
        </div>
        </div>
</div>


<!--end-->
<!--start-->
<div class="my-5">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <label for="phone_number" class="tw-form-label">Phone Number</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="phone_number" v-model="phone_number" class="tw-form-control w-full" id="phone_number">
            <span v-if="errors.phone_number" class="text-red-500 text-xs font-semibold">{{errors.phone_number[0]}}</span>
          </div>
        </div>
        </div>
</div>


<!--end-->
<!--start-->


    	<div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="updateNumber()">Update</a>
    		<a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>	
    	</div>
	  </div>
  </div>
</template>

<script>


  export default {

    props:['id','url'],


      data(){
        return{
           telephonedirectory:[],
      
        name:'',
        designation:'',
        phone_number:'',
        errors:[],
        success:null,
        }
      },
        
      methods:
        {

      editNumber()
      {
         //alert('kjhkjh');

            axios.get('/admin/phonenumber/editlist/'+this.id).then(response => {
        

            this.directory= response.data.data[0]; 

             //console.log(this.directory); 
            this.name=this.directory.name;
            this.designation=this.directory.designation;
            this.phone_number=this.directory.phone_number;

           });             
      },


      updateNumber()
      {
    
        
        this.errors=[];
        this.success=null; 


        let formData=new FormData();
    
        formData.append('name',this.name);
        formData.append('designation',this.designation);      
        formData.append('phone_number',this.phone_number);      
              
       axios.post('/admin/phonenumber/edit/'+this.id,formData).then(response => {   
        this.directory = response.data;
        this.success = response.data.success;
      
        //console.log(this.directory);
        //alert(this.school_id);
        //window.location.reload();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
 
},

        },
      created()
      {//alert('khkjh');
        
       this.editNumber();
      }
  }
</script>