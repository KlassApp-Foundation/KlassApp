<template>
  <div class="bg-white shadow px-4 py-3">
    <div>
      <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

<!--start-->
      <div class="">
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-8 md:mr-8">
        <div class="mb-2">
            <label for="category" class="tw-form-label">Category</label>
        </div>
        <div class="mb-2">
            <input type="text"  name="category" v-model="category" class="tw-form-control w-full" id="category">
            <span v-if="errors.category" class="text-red-500 text-xs font-semibold">{{errors.category[0]}}</span>
          </div>
        </div>
        </div>
</div>

<!--end-->
<!--start-->



      <div class="my-4">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="updateCategory()">Update</a>
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
           category:[],
        
        category:'',
       
        errors:[],
        success:null,
        }
      },
        
      methods:
        {

      editCategory()
      {
         //alert('kjhkjh');

            axios.get('/library/bookscategory/show/'+this.id).then(response => {
        

            this.categorylist= response.data.data[0]; 

             //console.log(this.category); 

            this.category=this.categorylist.name;

           });             
      },


      updateCategory()
      {
    
        
        this.errors=[];
        this.success=null; 


        let formData=new FormData();

        formData.append('category',this.category);      
              
       axios.post('/library/bookscategory/update/'+this.id,formData).then(response => {   
        this.category = response.data;
        this.success = response.data.success;
      
        //console.log(this.exam);
        //alert(this.school_id);
        //window.location.reload();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
 
},



        },
      created()
      {
        
       this.editCategory();
      }
  }
</script>
