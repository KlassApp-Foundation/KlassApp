<template>
  <div class="bg-white shadow px-4 py-3">
    <div v-show="this.formshow==1">
	   
      <div class="flex flex-col lg:flex-row md:flex-row">
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label  class="tw-form-label">Staff<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
               <select name="staff_id"  v-model="staff_id" class="tw-form-control w-full">
                <option value="" disabled>Select staff</option>
                <option v-for="staff in staffs" v-bind:value="staff.id">{{staff.name}}</option>
              </select><br>
               <span v-if="errors.staff_id" class="text-red-500 text-xs font-semibold">{{errors.staff_id[0]}}</span>
            </div>
               
          </div> 
        </div>
      </div>
      <div class="flex flex-col lg:flex-row md:flex-row">
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label for="type" class="tw-form-label">Start date<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
             <input v-model="start_date" name="start_date"  value="start_date" type="date" class="tw-form-control w-full"><br>
              <span v-if="errors.start_date" class="text-red-500 text-xs font-semibold">{{errors.start_date[0]}}</span>
            </div>
            </div>
          
          </div>
        </div>
      
            
     <div class="flex flex-col lg:flex-row md:flex-row">
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label for="type" class="tw-form-label">End date<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
             <input v-model="end_date" name="end_date"  value="end_date" type="date" class="tw-form-control w-full">
            </div>
          </div>
           <span v-if="errors.end_date" class="text-red-500 text-xs font-semibold">{{errors.end_date[0]}}</span>
          </div>
        </div>
        
     
      <div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="getdetails()">Submit</a>  
      </div>  
</div>
     
      <div v-if="this.payshow == 1" class="flex flex-col w-full leading-loose">
         <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
        <div class="flex justify-between items-center flex-wrap pb-3">
               <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
               <p class="w-full lg:w-1/3 lg:ml-2">Name:</p>
               <p class="w-full lg:w-2/3 lg:ml-2">{{details.staffname}} 
               <span v-if="details.designation_name!='Others'" class="lg:ml-1">{{details.designation_name}}</span>
               <span v-else="" class="lg:ml-1">{{details.sub_designation}} </span></p><br>
               </div>
                <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">Total days:</p>
                <p class="w-full lg:w-2/3 lg:ml-2">{{workingdays}} </p>
                </div>
                 <!-- <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">Leave:</p>
                 <p class="w-full lg:w-2/3 lg:ml-2">{{leavecount}} </p>
                 </div> -->
              
                <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">Salary period :</p>
                <p class="w-full lg:w-2/3 lg:ml-2">{{start_date}} to {{end_date}}</p>
                </div>
                
                 <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">Gross Salary :</p>
                <p class="w-full lg:w-2/3 lg:ml-2">{{details.gross_salary}}</p>
                </div>
                <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">Salary Per day :</p>
                <p class="w-full lg:w-2/3 lg:ml-2">{{perday_salary}}</p>
                </div>
            

             <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
               <p class="w-full lg:w-1/3 ml-2"> Leave:</p>
               <input type="text" name="" v-model="leave" @change="lossofpay()">
               <span v-if="errors.leave" class="text-red-500 text-xs font-semibold">{{errors.leave[0]}}</span>
            </div>
             <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
            <p class="w-full lg:w-1/3 lg:ml-2">Leave duduction:</p>
                <p class="w-full lg:w-2/3 lg:ml-2"> {{loss_of_pay}} </p>
                 </div>
            <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2"> Late:</p>
                <input type="text" name="" v-model="late">
                </div>

                  <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">Salary Percentage:</p>
                <input type="text" name="" v-model="percentage" @change="lossofpay()">
                <span v-if="errors.percentage" class="text-red-500 text-xs font-semibold">{{errors.percentage[0]}}</span>
                </div>


                  <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">Total Salary:</p>
               <p class="w-full lg:w-2/3 lg:ml-2"> {{total_salary}} </p>
                </div>
</div>
        
     
           <div v-for="(payroll,k1) in payrolls" :key="k1" class="w-full lg:w-1/2 lg:pr-8 md:pr-8 py-3">
          <div class="flex flex-col lg:flex-row items-center ">
 <label for="title" :class="[payroll.head_type=='earning' ? 'text-green-700' : 'text-red-700']" class="w-full lg:w-1/3 mb-1 text-sm font-semibold">{{payroll.head}} {{payroll.category}}<span class="text-red-500">*</span></label>
            
           <div  class="w-full lg:w-2/3 px-2">
             <div v-show="payroll.category_id!=4" class="">
              <input  type="text"  v-model="payroll.amount" class="tw-form-control w-full ml-2" > <br>
               <span v-if="errors['amount'+k1]" class="text-red-500 text-xs font-semibold ml-2">{{errors['amount'+k1][0]}}</span> 
             </div>
            
            <div v-show="payroll.category_id==4" class="flex-col w-1/2">
               <!-- <label for="title" class="tw-form-label">{{payroll.amount}}</label> -->
              <label for="title" class="tw-form-label text-blue-500">{{payroll.category_value}}</label>
            </div>
             </div> 
        </div>
      </div>
      
<!-- 
         <div class="flex justify-between items-center">
               <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
               <span class="w-full lg:w-1/3">Total earnings:</span>
               <span class="w-full lg:w-2/3 lg:ml-2"> {{details.earning}}</span>
               </p>
                <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
                <span class="w-full lg:w-1/3">Total deductions :</span>
                <span class="w-full lg:w-2/3 lg:ml-2">{{details.deduction}}</span>
                </p>
            </div>
          <div class="flex justify-between items-center">
               <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
               <span class="w-full lg:w-1/3">Net salary:</span>
               <span class="w-full lg:w-2/3 lg:ml-2">  {{details.total}}</span>
               </p>
                
            </div> -->
            
             <div class="flex flex-col lg:flex-row md:flex-row">
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-8 md:mr-8">
            <div class="mb-2">
              <label for="type" class="tw-form-label">Comments</label>
            </div>
            <div class="mb-2">
             <input v-model="comments" name="comments"  value="comments" type="text" class="tw-form-control w-full">
            </div>
          </div>
           <span v-if="errors.comments" class="text-red-500 text-xs font-semibold">{{errors.comments[0]}}</span>
          </div>
        </div>

      <div class="my-6"  v-show="this.payrolls!=''" >
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="check()">Save</a>
        <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="resetform()">Reset</a>  
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
        start_date:'',
        end_date:'',
        salary_id:'',
        comments:'',
        staffs:[],
        details:[],
        payrollitems:[],
        payrolls: [],
        errors:[],
        success:null,
        payshow:0,
        formshow:1,
        payroll_no:'',
        workingdays:0,
        leavecount:0,
        leave:0,
        late:0,
        perday_salary:0,
        sumdeduction:0,
        sumdeduction:0,
        loss_of_pay:0,
        percentage:100,
        total_salary:0,
        payrollskey:[],
        
      }
    },
        
    methods:
    {
      getList()
      {
        axios.get(this.url+'/accountant/payroll/salary/showlist').then(response => {
          this.staffs    = response.data.staff;   
        });
       
      },

      getpayrollnumber()
      {
        axios.get(this.url+'/accountant/payroll/payslip/getpayrollno').then(response => {
          this.payroll_no    = response.data;   
        });
        
      },

      lossofpay()
      { 
        this.loss_of_pay=Math.round(this.leave*this.perday_salary);
        this.total_salary=Math.round(((this.details.gross_salary-this.loss_of_pay)*this.percentage)/100);

        this.payrolls[0].amount=Math.round((45*this.total_salary)/100);
        //alert(this.gross_salary);
        for (var i =0;i<this.payrolls.length;i++) {
           if(this.payrolls[i].head_key=='ESI')
           {
           this.payrolls[i].amount=Math.round((this.total_salary*0.75)/100);
           }
      }
        //alert(this.loss_of_pay);

      },

      getdetails()
      {
        axios.post(this.url+'/accountant/payroll/payslip/details',{
          staff_id:this.staff_id,
          start_date:this.start_date,
          end_date:this.end_date,
        }).then(response => {
          this.payshow=1;
          this.formshow=0;
          console.log(response.data); 
          this.workingdays=response.data.totaldays;
          //this.leavecount=response.data.leavecount;
          this.perday_salary=response.data.perday_salary;
          this.details   = response.data.salary;
          this.payrollitems=this.details.salaryitems;
          this.salary_id=this.details.id;
          this.total_salary=this.details.gross_salary;
         

          

           for (var i =0;i<this.payrollitems.length;i++) {
           /* if(this.payrollitems[i].head_key=='BA'){     
        this.payrolls.push({ 
          salary_item:this.payrollitems[i].id,
          head:this.payrollitems[i].head,
          head_type:this.payrollitems[i].head_type,
          head_key:this.payrollitems[i].head_key,
          category_id:this.payrollitems[i].category_id,
          category:this.payrollitems[i].category,
          category_value:this.payrollitems[i].category_value,
          amount:(this.payrollitems[i].amount/this.workingdays)*(this.workingdays-this.leavecount),
        });
      }
       else if(this.payrollitems[i].category_id==3){     
        this.payrolls.push({ 
          salary_item:this.payrollitems[i].id,
          head:this.payrollitems[i].head,
          head_type:this.payrollitems[i].head_type,
          head_key:this.payrollitems[i].head_key,
          category_id:this.payrollitems[i].category_id,
          category:this.payrollitems[i].category,
          category_value:this.payrollitems[i].category_value,
          amount:this.payrollitems[i].amount*this.workingdays,
        });
      }
      else{*/
        this.payrolls.push({ 
          salary_item:this.payrollitems[i].id,
          head:this.payrollitems[i].head,
          head_type:this.payrollitems[i].head_type,
          head_key:this.payrollitems[i].head_key,
          category_id:this.payrollitems[i].category_id,
          category:this.payrollitems[i].category,
          category_value:this.payrollitems[i].category_value,
          amount:this.payrollitems[i].amount,
        });
    //  }

        /*if(this.payrollitems[i].head_type=="earning"){
         this.sumearning  = this.sumearning + parseInt(this.payrollitems[i].amount);
        }
        if(this.payrollitems[i].head_type=="deduction"){
         this.sumdeduction  = this.sumdeduction + parseInt(this.payrollitems[i].amount);
        }*/
      }
       
        }).catch(error=>{
          this.errors=error.response.data.errors;
        }); 
      },


      check()
      {
        this.payrollskey=[];
        for (var i =0;i<this.payrolls.length;i++) {
           var obj = {};
           obj[this.payrolls[i].head_key] = this.payrolls[i].amount;
           this.payrollskey.push(obj);
      }
      



        axios.get(this.url+'/accountant/payroll/payslip/getpayrollno').then(response => {
          this.payroll_no    = response.data;   
        });
        
        var formData = new FormData();
        formData.append('payroll_no',this.payroll_no);
        formData.append('staff_id',this.staff_id);
        formData.append('start_date',this.start_date);
        formData.append('end_date',this.end_date);
        formData.append('percentage',this.percentage);
        formData.append('leave',this.leave);
        formData.append('late',this.late);
        formData.append('loss_of_pay',this.loss_of_pay);
        formData.append('comments',this.comments);
        formData.append('salary_id',this.salary_id);
        formData.append('payrollskey',JSON.stringify(this.payrollskey));
        formData.append('payrolls',JSON.stringify(this.payrolls));
        formData.append('payrollscount',this.payrolls.length);
        for(let i=0;i<this.payrolls.length;i++)
        {
          if(typeof this.payrolls[i]['salary_item'] !== "undefined")
          {
            formData.append('salary_item'+i,this.payrolls[i]['salary_item']);
          }
          else
          {
            formData.append('salary_item'+i,'');
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
      


        axios.post(this.url+'/accountant/payroll/payslip/add',formData).then(response => {
          this.success=response.data.success;
          this.reset();
          location.reload();
        }).catch(error=>{
          this.errors=error.response.data.errors;
          this.payrollskey=[];
        }); 
      },

      resetform()
      {
       /* this.payshow=0;
        this.formshow=1;
        this.payrolls=[];
        this.sumdeduction=0;
        this.sumearning=0;*/
        location.reload();
      },

      
      reset()
      {
        this.errors=[];
        //this.staff_id='';
        //this.template_id='';
        //this.effective_date='';
          
      },


    },

    created()
    {
      this.getList();
      this.getpayrollnumber();
    }
  }
</script>