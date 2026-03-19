<template>
  <div class="bg-white shadow px-4 py-3 my-3">
    <div>
      <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
      <div class="flex flex-col lg:flex-row md:flex-row">
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label  class="tw-form-label">Name<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <input v-model="name" name="name"  value="name" type="text" placeholder="Template Name" class="tw-form-control w-full">
            </div>
            <span v-if="errors.name" class="text-red-500 text-xs font-semibold">{{errors.name[0]}}</span>
            <span v-if="errors.template_id" class="text-red-500 text-xs font-semibold">{{errors.template_id[0]}}</span>
          </div> 
        </div>
      </div>
      <div class="flex flex-col lg:flex-row md:flex-row">
        <div class="tw-form-group w-full">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label for="type" class="tw-form-label">Status<span class="text-red-500">*</span></label>
            </div>
             <input type="checkbox" v-model="status" v-bind:true-value="1" v-bind:false-value="0" name="status"><span class="mx-2 text-sm">Active</span>
            </div>
           <span v-if="errors.type" class="text-red-500 text-xs font-semibold">{{errors.status[0]}}</span>
          </div>
        </div>
      </div>

     

      <!-- <div class="mb-2">
        <label for="title" class="tw-form-label">Option<span class="text-red-500">*</span></label>
      </div>  -->
      <div class="w-full">
        <div v-for="(payroll,k1) in payrolls" :key="k1" class="w-full lg:w-1/2 lg:pr-8 md:pr-8 py-3">
          <div class="flex flex-col lg:flex-row items-center">
 <label for="title" :class="[payroll.head_type=='earning' ? 'text-green-700' : 'text-red-700']" class="mb-1 text-sm font-semibold w-full lg:w-1/3">{{payroll.head}}({{payroll.head_key}})<span class="text-red-500">*</span></label>
 <div class="lg:w-2/3 flex">
            <div class="w-full ">
            
               <select  v-model="payroll.category_id"   class="tw-form-control w-full" :disabled="payroll.head_key=='BA'">
                <option value="" disabled>Select one</option>
                <option v-for="category in category" v-bind:value="category.id">{{category.name}}</option>
              </select>  <br>
               <span v-if="errors['category_id'+k1]" class="text-red-500 text-xs font-semibold">{{errors['category_id'+k1][0]}}</span>
            </div>      
           <div v-show="payroll.category_id==4" class="w-full pl-1">
              <input  type="text"  v-model="payroll.category_value" class="tw-form-control w-full">  <br>
               <span v-if="errors['category_value'+k1]" class="text-red-500 text-xs font-semibold whitespace-no-wrap">{{errors['category_value'+k1][0]}}</span>
            </div> 
            </div> 
        </div>
      </div>
      
      <div class="my-6"  v-show="this.payrolls!=''" >
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="check()">Submit</a>
       <!--  <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a> -->  
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    props:['url','template_id'],
    data(){
      return{
        name:'',
        status:0,
        heading:[],
        category:[],
        template:[],
        editdata:[],
        payrolls: [],
        errors:[],
        success:null,
      }
    },
        
    methods:
    {
       getdetails()
      {
        axios.get(this.url+'/accountant/payroll/template/showlist').then(response => {
         // this.heading    = response.data.heading;
          this.category    = response.data.category;    
        });
       
      },
      getList()
      {
        axios.get(this.url+'/accountant/payroll/template/'+this.template_id+'/editshow').then(response => {
          this.template   = response.data.data;
          this.editdata   = this.template.payrollitems;
          this.name       = this.template.name;
          this.status       = this.template.status;
          console.log(response); 

           for (var i =0;i<this.editdata.length;i++) {
         // if(this.editdata[i].item_id==this.heading[i].id){
        this.payrolls.push({ 
          head:this.editdata[i].head,
          head_id:this.editdata[i].head_id,
          head_key:this.editdata[i].head_key,
          head_type:this.editdata[i].head_type,
          category_id:this.editdata[i].category_id,
          category_value:this.editdata[i].category_value
        });
         //}
        }
             
        });
       
      },

      check()
      {
        
        var formData = new FormData();

        formData.append('template_id',this.template_id);
        formData.append('name',this.name);
        formData.append('status',this.status);
        formData.append('payrolls',this.payrolls);
        formData.append('payrollscount',this.payrolls.length);

        for(let i=0;i<this.payrolls.length;i++)
        {
          if(typeof this.payrolls[i]['category_id'] !== "undefined")
          {
            formData.append('category_id'+i,this.payrolls[i]['category_id']);
          }
          else
          {
            formData.append('category_id'+i,'');
          }
        }
        for(let i=0;i<this.payrolls.length;i++)
        {
          if(typeof this.payrolls[i]['head_id'] !== "undefined")
          {
            formData.append('head_id'+i,this.payrolls[i]['head_id']);
          }
          else
          {
            formData.append('head_id'+i,'');
          }
        }
        for(let i=0;i<this.payrolls.length;i++)
        {
          if(typeof this.payrolls[i]['category_value'] !== "undefined")
          {
            formData.append('category_value'+i,this.payrolls[i]['category_value']);
          }
          else
          {
            formData.append('category_value'+i,'');
          }
        }


        axios.post(this.url+'/accountant/payroll/template/'+this.template_id+'/update',formData).then(response => {
          this.success=response.data.success;
          this.errors=[];
          //location.reload();
        }).catch(error=>{
          this.errors=error.response.data.errors;
        }); 
      },

      typechange(id)
      {
        if(id!=4)
        {
        
            this.payrolls[id].category_value='';
          
        }
        
      },

     
    },

    created()
    {
      this.getList();
      this.getdetails();
    }
  }
</script>