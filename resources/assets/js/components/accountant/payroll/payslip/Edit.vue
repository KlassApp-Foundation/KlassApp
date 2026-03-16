<template>
  <div class="bg-white shadow px-4 py-3">
    <div>
      <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
      </div>
            
     
        <div class="flex justify-between items-center w-full flex-wrap leading-loose pb-3"> 
              
               <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
               <span class="w-full lg:w-1/3 lg:ml-2">Name:</span>
               <p class="w-full lg:w-2/3 lg:ml-2">{{staff_name}}
               <span v-if="editdata.designation_name!='Others'" class="lg:ml-1">{{editdata.designation_name}}</span>
               <span v-else="" class="lg:ml-1">{{editdata.sub_designation}}</span>
               </p>
                </div>
                 <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
               <p class="w-full lg:w-1/3 lg:ml-2">Todal days:</p>
               <p class="w-full lg:w-2/3 lg:ml-2">{{editdata.totaldays}}</p>
              </div>
                <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">salary period :</p>
                <p class="w-full lg:w-2/3 lg:ml-2">{{start_date}} to {{end_date}}</p>
                </div>
  
                <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
                <p class="w-full lg:w-1/3 lg:ml-2">Salary Per day :</p>
                <p class="w-full lg:w-2/3 lg:ml-2">{{perday_salary}}</p>
                </div>
              <div class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-1">
               <p class="w-full lg:w-1/3 lg:ml-2"> Leave:</p>
               <input type="text" name="" v-model="leave" @change="lossofpay()">
               <span v-if="errors.leave" class="text-red-500 text-xs font-semibold">{{errors.leave[0]}}</span>
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
                <p class="w-full lg:w-1/3 lg:ml-2"> Total Salary:</p>
                <p>{{total_salary}}</p>
                </div>
 </div>

      <div class="w-full">
        <div v-for="(payroll,k1) in payrolls" :key="k1" class="w-full lg:w-1/2 lg:pr-8 md:pr-8 py-3">
          <div class="flex flex-col lg:flex-row items-center">
 <label for="title" :class="[payroll.head_type=='earning' ? 'text-green-700' : 'text-red-700']" class="w-full lg:w-1/3 mb-1 text-sm font-semibold">{{payroll.head}}<span>({{payroll.category}})</span></label>
           <!--  <div class="flex-col w-1/2">
            
            </div>   -->    
           <div  class="w-full lg:w-2/3">
              <input  type="text"  v-model="payroll.amount" class="tw-form-control w-full ml-2">
              <br>
               <span v-if="errors['amount'+k1]" class="text-red-500 text-xs font-semibold ml-2">{{errors['amount'+k1][0]}}</span> 
            </div>  
        </div>
      </div>
       <div class="flex justify-between items-center w-full"> 
               <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
               <span class="w-full lg:w-1/3">Gross_salary:</span>
               <span class="w-full lg:w-2/3 lg:ml-2">{{editdata.gross_salary}}</span>
               </p>
                <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
                <span class="w-full lg:w-1/3">Leave deduction :</span>
                <span class="w-full lg:w-2/3 lg:ml-2">{{loss_of_pay}}</span>
                </p>
          </div>

      <div class="flex justify-between items-center w-full"> 
               <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
               <span class="w-full lg:w-1/3">Earnings:</span>
               <span class="w-full lg:w-2/3 lg:ml-2">{{editdata.earning}}</span>
               </p>
                <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
                <span class="w-full lg:w-1/3"> Deduction :</span>
                <span class="w-full lg:w-2/3 lg:ml-2">{{editdata.deduction}}</span>
                </p>
          </div>
     <div class="flex justify-between items-center w-full"> 
              
                <!-- <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
                <span class="w-full lg:w-1/3">Net salary :</span>
                <span class="w-full lg:w-2/3 lg:ml-2">{{editdata.total}}</span>
                </p> -->
                <p class="font-semibold text-sm flex items-center lg:w-1/2 md:w-1/2 lg:pr-8 md:pr-8 py-2">
                <span class="w-full lg:w-1/3">Status :</span>
                <span class="w-full lg:w-2/3 lg:ml-2">{{editdata.status}}</span>
                </p>
          </div>
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
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="check()">Submit</a>
        <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>  
      </div>


    </div>
  </div>
</template>

<script>
  export default {
    props:['url','payrollid'],
    data(){
      return{
        staff_id:'',
        start_date:'',
        end_date:'',
        staff_name:'',
        comments:'',
        status:'',
        percentage:0,
        leave:0,
        late:0,
        loss_of_pay:0,
        perday_salary:0,
        total_salary:0,
        payrollitems:[],
        editpayrollitems:[],
        payrolls: [],
        errors:[],
        editdata:[],
        success:null,
      }
    },
        
    methods:
    {
      

    getList()
      {
        axios.get(this.url+'/accountant/payroll/payslip/'+this.payrollid+'/editshow').then(response => {
         this.editdata=response.data.data;
         this.staff_id=this.editdata.staff_id;
         this.staff_name=this.editdata.staffname;
         this.start_date=this.editdata.start_date;
         this.end_date=this.editdata.end_date;
         this.status=this.editdata.status;
         this.leave=this.editdata.leave;
         this.late=this.editdata.late;
         this.percentage=this.editdata.percentage;
         this.loss_of_pay=this.editdata.leave_deduction;
         this.perday_salary=this.editdata.per_day_salary;
         this.comments=this.editdata.comments;
         this.editpayrollitems=this.editdata.payslipitems;
         this.total_salary=this.editdata.total_salary;

           for (var i=0;i<this.editpayrollitems.length;i++) {
          
         this.payrolls.push({
          salary_item:this.editpayrollitems[i].salary_item,
          head:this.editpayrollitems[i].head,
          head_key:this.editpayrollitems[i].head_key,
          head_type:this.editpayrollitems[i].head_type,
          category:this.editpayrollitems[i].category,
          amount:this.editpayrollitems[i].amount
        });
    
        }

         
        });
      },

       lossofpay()
      { 
        this.loss_of_pay=this.leave*this.perday_salary;
        //alert(this.loss_of_pay);
        this.loss_of_pay=Math.round(this.leave*this.perday_salary);
        this.total_salary=Math.round(((this.editdata.gross_salary-this.loss_of_pay)*this.percentage)/100);

        this.payrolls[0].amount=Math.round((45*this.total_salary)/100);
        //alert(this.gross_salary);
        for (var i =0;i<this.payrolls.length;i++) {
           if(this.payrolls[i].head_key=='ESI')
           {
           this.payrolls[i].amount=Math.round((this.total_salary*0.75)/100);
           }
         }

          for (var i =0;i<this.payrolls.length;i++) {
           if(this.payrolls[i].head_key=='PF')
           {
           this.payrolls[i].amount=Math.round((((this.total_salary*45)/100)*12)/100);
           }
         }

      },

      check()
      {
        
        var formData = new FormData();
       // formData.append('staff_id',this.staff_id);
       // formData.append('start_date',this.start_date);
        formData.append('leave',this.leave);
        formData.append('late',this.late);
        formData.append('loss_of_pay',this.loss_of_pay);
        formData.append('percentage',this.percentage);
        formData.append('status',this.status);
        formData.append('comments',this.comments);
        formData.append('payrolls',this.payrolls);
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


        axios.post(this.url+'/accountant/payroll/payslip/'+this.payrollid+'/update',formData).then(response => {
          this.success=response.data.success;
          this.errors=[];
          //location.reload();
        }).catch(error=>{
          this.errors=error.response.data.errors;
        }); 
      },

     
     


    },

    created()
    {
      this.getList();
    }
  }
</script>