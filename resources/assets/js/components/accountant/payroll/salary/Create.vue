<template>
  <div class="bg-white shadow px-4 py-3 my-3">
    <div>
	    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
      <div class="flex flex-wrap">
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-6 md:mr-6">
            <div class="mb-2">
              <label  class="tw-form-label">Staff<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
               <select name="staff_id"  v-model="staff_id" class="tw-form-control w-full">
                <option value="" disabled>Select staff</option>
                <option v-for="staff in staffs" v-bind:value="staff.id">{{staff.name}}</option>
              </select>
            </div>
                <span v-if="errors.staff_id" class="text-red-500 text-xs font-semibold">{{errors.staff_id[0]}}</span>
          </div> 
        </div>
      
      
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-6 md:mr-6">
            <div class="mb-2">
              <label for="type" class="tw-form-label">Salary Template<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
              <select name="template_id"  v-model="template_id" class="tw-form-control w-full" @change="typechange()">
                <option value="" disabled>Select template</option>
                <option v-for="template in templates" v-bind:value="template.id">{{template.name}}</option>
              </select>
            </div>
            </div>
           <span v-if="errors.template_id" class="text-red-500 text-xs font-semibold">{{errors.template_id[0]}}</span>
          </div>
        
      
 
            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Gross salary</label>
              <div class="my-1">
                      <input v-model="gross_salary" name="gross_salary"  value="gross_salary" type="text" class="tw-form-control w-full" @change="basicsalary" ><span v-if="errors.gross_salary" class="text-red-500 text-xs font-semibold">{{errors.gross_salary[0]}}</span>
              </div>
              </div>
              </div>

            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Effective date</label>
              <div class="my-1">
                      <input v-model="effective_date" name="effective_date"  value="effective_date" type="date" class="tw-form-control w-full"><span v-if="errors.effective_date" class="text-red-500 text-xs font-semibold">{{errors.effective_date[0]}}</span>
              </div>
              </div>
              </div>
           </div>
     

      <!-- <div class="mb-2">
        <label for="title" class="tw-form-label">Option<span class="text-red-500">*</span></label>
      </div>  -->
      <div class="w-full">
        
        <div class="flex flex-col lg:flex-row md:flex-row">
            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Basic  Percentage</label>
              <div class="my-1">
                      <input v-model="basic_percentage" name="basic_percentage"  value="basic_percentage" type="text" class="tw-form-control w-full" @change="basicsalary" ><span v-if="errors.basic_percentage" class="text-red-500 text-xs font-semibold">{{errors.basic_percentage[0]}}</span>
              </div>
              </div>
              </div>
      </div>           

        <div v-for="(payroll,k1) in payrolls" :key="k1" class="w-full lg:w-1/2 lg:pr-8 md:pr-8 py-3">
          <div class="flex flex-col lg:flex-row items-center">
 <label for="title" :class="[payroll.head_type=='earning' ? 'text-green-700' : 'text-red-700']" class="w-full lg:w-1/3 mb-1 text-sm font-semibold">{{payroll.head}}({{payroll.category}})<span class="text-red-500">*</span></label>
             <div class="w-full lg:w-2/3">
           <div v-show="payroll.category_id!=4" class="">
              <input  type="text"  v-model="payroll.amount" class="tw-form-control w-full ml-2" > <br>
               <span v-if="errors['amount'+k1]" class="text-red-500 text-xs font-semibold ml-2">{{errors['amount'+k1][0]}}</span> 
            </div>  
            
            <div v-show="payroll.category_id==4" class="flex-col w-1/2 px-2">
              <label for="title" class="tw-form-label">{{payroll.category_value}}</label>
               <span v-if="errors['category_value'+k1]" class="text-red-500 text-xs font-semibold ml-2">{{errors['category_value'+k1][0]}}</span>
            </div>
            </div>
        </div>
      </div>
      
      <div class="my-6"  v-show="this.payrolls!=''" >
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="check()">Submit</a>
    		<a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>	
      </div>
	  </div>
  </div>
  </div>
</template>

<script>
  export default {
    props:['url'],
    data(){
      return{
        staff_id:'',
        template_id:'',
        gross_salary:'',
        effective_date:'',
        staffs:[],
        templates:[],
        payrollitems:[],
        payrolls: [],
        errors:[],
        success:null,
        payrollskey:[],
        formulaerr:'',
        basic_percentage:45,
        
      }
    },
        
    methods:
    {
      getList()
      {
        axios.get(this.url+'/accountant/payroll/salary/showlist').then(response => {
          this.staffs    = response.data.staff;
          this.templates    = response.data.template;
         
          console.log(response);    
        });
       
      },

      check()
      {
        this.payrollskey=[];
        for (var i =0;i<this.payrolls.length;i++) {
           var obj = {};
           if(this.payrolls[i].category_id==4)
           {
           obj[this.payrolls[i].head_key] ='no data';
           }else{
           obj[this.payrolls[i].head_key] = this.payrolls[i].amount;
         }
           this.payrollskey.push(obj);
      }
        var formData = new FormData();
        formData.append('staff_id',this.staff_id);
        formData.append('template_id',this.template_id);
        formData.append('gross_salary',this.gross_salary);
        formData.append('effective_date',this.effective_date);
        formData.append('payrollskey',JSON.stringify(this.payrollskey));
        formData.append('payrolls',JSON.stringify(this.payrolls));
        formData.append('payrollscount',this.payrolls.length);

        for(let i=0;i<this.payrolls.length;i++)
        {
          if(typeof this.payrolls[i]['template_item'] !== "undefined")
          {
            formData.append('template_item'+i,this.payrolls[i]['template_item']);
          }
          else
          {
            formData.append('template_item'+i,'');
          }
        }

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
          if(typeof this.payrolls[i]['category_value'] !== "undefined")
          {
            formData.append('category_value'+i,this.payrolls[i]['category_value']);
          }
          else
          {
            formData.append('category_value'+i,'');
          }
        }
      
        for(let i=0;i<this.payrolls.length;i++)
        {
          if(typeof this.payrolls[i]['amount'] !== "undefined")
          {
            formData.append('amount'+i,this.payrolls[i]['amount']);
          }
          else
          {
            formData.append('amount'+i,'');
          }
        }


        axios.post(this.url+'/accountant/payroll/salary/add',formData).then(response => {
          this.success=response.data.success;
          //this.reset();
         // window.location.href = this.url+'/accountant/payroll/salary';

          location.reload();
        }).catch(error=>{
          this.errors=error.response.data.errors;
          for(let i=0;i<this.payrolls.length;i++)
          {
          if(this.errors['amount'+i]==''){
            this.formulaerr=true;
          }
        }
        }); 
      },

      basicsalary(){
        this.payrolls[0].amount=(this.basic_percentage*this.gross_salary)/100;
        //alert(this.gross_salary);
        for (var i =0;i<this.payrolls.length;i++) {
           if(this.payrolls[i].head_key=='ESI')
           {
           this.payrolls[i].amount=(this.gross_salary*0.75)/100;
           }
      }

      },

      typechange()
      {
        this.resetpayroll();
        axios.get(this.url+'/accountant/payroll/template/'+this.template_id+'/itemlist').then(response => {
          //this.template   = response.data;
          this.payrollitems   = response.data;
          console.log(response); 

           for (var i =0;i<this.payrollitems.length;i++) {
          /*if(this.payrollitems[i].paycategory_id!=1 && this.payrollitems[i].paycategory_id!=4){*/
        this.payrolls.push({
          template_item:this.payrollitems[i].id, 
          head:this.payrollitems[i].payrollitem.name,
          head_id:this.payrollitems[i].payrollitem.id,
          head_type:this.payrollitems[i].payrollitem.type,
          head_key:this.payrollitems[i].payrollitem.key,
          category:this.payrollitems[i].paycategory.name,
          category_id:this.payrollitems[i].paycategory_id,
          category_value:this.payrollitems[i].category_value,
          amount:''
        });
         // }
        }
             
        });
        
      },

      
      resetpayroll()
      {
          this.payrolls=[];
          

      },

      reset()
      {
        this.errors=[];
        this.staff_id='';
        this.template_id='';
        this.effective_date='';
          
      },


    },

    created()
    {
      this.getList();
    }
  }
</script>