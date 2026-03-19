<template>
  <div class="bg-white shadow px-4 py-3">
    <div>
	    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

<!--start-->
      <div class="my-5">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <label for="library_card_no" class="tw-form-label">Card No</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="library_card_no" v-model="library_card_no" class="tw-form-control w-full" id="library_card_no">
            <span v-if="errors.library_card_no" class="text-red-500 text-xs font-semibold">{{errors.library_card_no[0]}}</span>
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
            <label for="book_code_no" class="tw-form-label">Book Code</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="book_code_no" v-model="book_code_no" class="tw-form-control w-full" id="book_code_no">
            <span v-if="errors.book_code_no" class="text-red-500 text-xs font-semibold">{{errors.book_code_no[0]}}</span>
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
            <label for="issue_date" class="tw-form-label">Issue Date</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="date"  name="issue_date" v-model="issue_date" class="tw-form-control w-full" id="issue_date">
            <span v-if="errors.issue_date" class="text-red-500 text-xs font-semibold">{{errors.issue_date[0]}}</span>
          </div>
        </div>
        </div>
</div>

 <!-- end -->
  <!-- start -->

<div class="my-5">
         <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
        <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <label class="tw-form-label ">Status</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
        <select v-model="status" id="status" class="repeats tw-form-control w-full">
             <option value="pending">Pending</option>
              <option value="returned">Returned</option>
               <option value="cancel">Cancel</option>
              </select> 
               <span v-if="errors.status"><p class="text-danger text-xs my-1">{{errors.status[0]}}</p></span>
              </div>

          </div>
  </div>
</div>

<!--end-->
<!--start-->
    

    	<div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="updateBookLending()">Update</a>
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
        booklending:[],
        
        library_card_no:'',
        book_code_no:'',
        issue_date:'',
        status:'',
        errors:[],
        success:null,
      }
    },
        
    methods:
    {
      reset()
      {
        this.library_card_no='';
        this.book_code_no='';
        this.issue_date='';
        this.status='';
      
      }, 

      editBookLending()
      {
         //alert('kjhkjh');

            axios.get('/library/booklending/show/'+this.id).then(response => {
        

            this.booklending= response.data.data[0]; 

             //console.log(this.booklending); 

            
            this.book_code_no=this.booklending.book_code_no;
            this.library_card_no=this.booklending.library_card_no;
            this.issue_date=this.booklending.issue_date;
            this.status=this.booklending.status;
            
           });             
      },


      updateBookLending()
      {
        this.errors=[];
        this.success=null; 

        let formData=new FormData();

        formData.append('library_card_no',this.library_card_no);                 
        formData.append('book_code_no',this.book_code_no);                 
        formData.append('issue_date',this.issue_date);                 
        formData.append('status',this.status);                 
        
        axios.post('/library/booklending/update/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.success;
          //this.reset();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },


      
    },
    created()
    {
      this.editBookLending();
    }
  }
</script>