<template>
  <div class="">
    <div>
	    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

      <div class="flex">
        <div class="tw-form-group w-1/2">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label for="status" class="tw-form-label">Status</label>
            </div>
            <div class="mb-2">
              <input type="text" name="status" id="status" v-model="status" class="tw-form-control w-full"
              readonly>
            </div>
            <span v-if="errors.status" class="text-red-500 text-xs font-semibold">{{errors.status[0]}}</span>
          </div>
        </div>
      </div>

      <div class="flex">
        <div class="tw-form-group w-1/2">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label for="comments" class="tw-form-label">Comments</label>
            </div>
            <div class="mb-2">
              <textarea type="text" name="comments" id="comments" v-model="comments" class="tw-form-control w-full" rows="3" placeholder="Enter Comments"></textarea>
            </div>
            <span v-if="errors.comments" class="text-red-500 text-xs font-semibold">{{errors.comments[0]}}</span>
          </div>
        </div>
      </div>
    	
      <div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitForm()">Submit</a>
    		<a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>	
      </div>
	  </div>
  </div>
</template>

<script>

export default {
  props : ['url', 'id' , 'status'],

  data(){
    return{
      comments:'',
      errors:[],
      success:null,
    }
  },
        
  methods:
  {
    submitForm()
    {
      //alert(this.status)
      this.errors=[];
      this.success=null; 

      let formData=new FormData();
                
      formData.append('status',this.status);  
      formData.append('comments',this.comments);  
      if(this.status == 'approve')
      {
        axios.post('/teacher/lessonplan/approve/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.success;
          window.location.href='/teacher/lessonplans';
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      }
      else 
      {
        axios.post('/teacher/lessonplan/reject/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.success;
          window.location.href='/teacher/lessonplans';
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      }     
    },
  },

  created()
  {
    //
  }
}
</script>