<template>
  <div class="bg-white shadow px-4 py-3">
    <div>
	    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

<!--start-->
      <div class="my-5">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <label for="title" class="tw-form-label">Title</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="title" v-model="title" class="tw-form-control w-full" id="title">
            <span v-if="errors.title" class="text-red-500 text-xs font-semibold">{{errors.title[0]}}</span>
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
            <label for="author" class="tw-form-label">Author</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="author" v-model="author" class="tw-form-control w-full" id="author">
            <span v-if="errors.author" class="text-red-500 text-xs font-semibold">{{errors.author[0]}}</span>
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
              <label for="category_id" class="tw-form-label">Select Category</label>
            </div>
            <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
              <select class="tw-form-control w-full" id="category_id" v-model="category_id" name="category_id">
                <option value="" disabled>Select Category</option>
               <option value="" v-for="category in categorylist" v-bind:value="category.id">{{category.name}}</option>
              </select>
                <span v-if="errors.category_id" class="text-red-500 text-xs font-semibold">{{errors.category_id[0]}}</span>
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
            <label for="book_code" class="tw-form-label">Book Code</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="book_code" v-model="book_code" class="tw-form-control w-full" id="book_code">
            <span v-if="errors.book_code" class="text-red-500 text-xs font-semibold">{{errors.book_code[0]}}</span>
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
            <label for="isbn_number" class="tw-form-label">ISBN Number</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="isbn_number" v-model="isbn_number" class="tw-form-control w-full" id="isbn_number">
            <span v-if="errors.isbn_number" class="text-red-500 text-xs font-semibold">{{errors.isbn_number[0]}}</span>
          </div>
        </div>
        </div>
</div>


<!--end-->
<!--start-->

 <div class="my-5">
 <div  class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="flex flex-col lg:flex-row md:flex-row">
            <div class="w-full lg:w-1/4">
                <label for="cover_image" v-model="cover_image" class="tw-form-label">Upload Cover Image</label>
            </div>
            <div class="w-full lg:w-1/4 my-2">
              <input type="file" name="cover_image" @change="OnImageSelected" id="cover_image" class="tw-form-control w-full">
          <span v-if="errors.cover_image" class="text-red-500 text-xs font-semibold">{{errors.cover_image[0]}}</span>
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
            <label for="availability" class="tw-form-label">Availability</label>
        </div>
        <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
            <input type="text"  name="availability" v-model="availability" class="tw-form-control w-full" id="availability">
            <span v-if="errors.availability" class="text-red-500 text-xs font-semibold">{{errors.availability[0]}}</span>
          </div>
        </div>
        </div>
</div>

<!--end-->
<!--start-->    

    	<div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="checkForm()">Submit</a>
    		<a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>	
    	</div>
	  </div>
  </div>
</template>

<script>
	export default {
    props:[],
    data(){
      return{
        book:[],
        categorylist:[],
        category_id:'',
        title:'',
        author:'',
        isbn_number:'',
        book_code:'',
        cover_image:'',
        availability:'',
        errors:[],
        success:null,
      }
    },
        
    methods:
    {
      reset()
      {
        this.category_id='';
        this.title='',
        this.author='',
        this.isbn_number='';  
        this.book_code='';  
        this.cover_image='';  
        this.availability='';  
      }, 

      checkForm()
      {
        this.errors=[];
        this.success=null; 

        let formData=new FormData();

        formData.append('category_id',this.category_id);                 
        formData.append('title',this.title);                 
        formData.append('author',this.author);                 
        formData.append('isbn_number',this.isbn_number);          
        formData.append('book_code',this.book_code);          
        formData.append('cover_image',this.cover_image);          
        formData.append('availability',this.availability);          
              
        axios.post('/library/books/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.success;
          this.reset();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

      OnImageSelected(event)
          {
            this.cover_image = event.target.files[0];
          },

      getData()
      {
        axios.get('/library/bookscategory/list').then(response => {
          this.categorylist = response.data.data;
          
        });
      },
      
    },
    created()
    {
      this.getData();
    }
  }
</script>