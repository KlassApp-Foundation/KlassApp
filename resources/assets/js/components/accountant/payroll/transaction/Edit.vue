<template>
  <div class="bg-white shadow px-4 py-3 my-3">
    <div>
      <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
      <div class="flex flex-wrap">
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-6 md:mr-6">
            <div class="mb-2">
              <label for="type" class="tw-form-label">Pay Head<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
             <select name="paytype_id"  v-model="paytype_id" class="tw-form-control w-full" disabled="">
                <option value="" disabled>Select paytype</option>
                <option v-for="paytype in paytypes" v-bind:value="paytype.id" >{{paytype.name}}</option>
              </select>
            </div>
            </div>
           <span v-if="errors.paytype_id" class="text-red-500 text-xs font-semibold">{{errors.paytype_id[0]}}</span>
          </div>
       
     
     
        <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
          <div class="lg:mr-6 md:mr-6">
            <div class="mb-2">
              <label  class="tw-form-label">Staff<span class="text-red-500">*</span></label>
            </div>
            <div class="mb-2">
               <select name="staff_id"  v-model="staff_id" class="tw-form-control w-full" disabled="" >
                <option value="" disabled>Select staff</option>
                <option v-for="staff in staffs" v-bind:value="staff.id">{{staff.name}}</option>
              </select>
            </div>
                <span v-if="errors.staff_id" class="text-red-500 text-xs font-semibold">{{errors.staff_id[0]}}</span>
          </div> 
        </div>
      
       </div>

      <div v-if="this.paytype_id==1" class="flex flex-col lg:flex-row md:flex-row">
            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Payroll number </label>
              <p>{{editdata.payroll.payrollno}}</p>
              </div>
              </div>
      </div> 

      <div class="flex flex-wrap">
            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Account</label>
              <div class="my-1">
              <select name="account_id"  v-model="account_id" disabled="" class="tw-form-control w-full">
                <option value="" disabled>Select Account</option>
                <option v-for="account in accounts" v-bind:value="account.id">{{account.name}}</option>
              </select>
              <span v-if="errors.account_id" class="text-red-500 text-xs font-semibold">{{errors.account_id[0]}}</span>
              </div>
              </div>
              </div>
             

     
            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Transaction date</label>
              <div class="my-1">
                      <input v-model="transaction_date" name="transaction_date"  type="date" class="tw-form-control w-full"><span v-if="errors.transaction_date" class="text-red-500 text-xs font-semibold">{{errors.transaction_date[0]}}</span>
              </div>
              </div>
              </div>
            
     

            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Amount </label>
              <div class="my-1">
                      <input v-model="amount" name="amount"  type="text" class="tw-form-control w-full"><span v-if="errors.amount" class="text-red-500 text-xs font-semibold">{{errors.amount[0]}}</span>
              </div>
              </div>
              </div>
   
      
            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Payment method </label>
              <div class="my-1">
                      <select name="payment_method"  v-model="payment_method" class="tw-form-control w-full" >
                <option value="" disabled>Select Payment method</option>
                <option v-for="method in payment_methods" v-bind:value="method">{{method}}</option>
              </select>
               <span v-if="errors.payment_method" class="text-red-500 text-xs font-semibold">{{errors.payment_method[0]}}</span>
              </div>
              </div>
              </div>
       
      </div>

      <div v-if="this.payment_method=='Cheque'">
       <!-- *** -->
               <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8 mb-2">
                    <div class="mb-2">
                    <label for="" class="tw-form-label">Cheque Number</label>
                    </div>
                      <input v-model="cheque_number" name="cheque_number"  value="cheque_number" type="text" placeholder="Cheque Number" class="tw-form-control w-full"><span v-if="errors.cheque_number" class="text-red-500 text-xs font-semibold">{{errors.cheque_number[0]}}</span>
                    </div>
                   </div>
                    <div class="w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8 mb-2">
                    <div class="mb-2">
                    <label for="" class="tw-form-label">Cheque Date</label>
                    </div>
                      <input v-model="cheque_date" name="cheque_date"  value="cheque_date" type="date"  class="tw-form-control w-full"><span v-if="errors.cheque_date" class="text-red-500 text-xs font-semibold">{{errors.cheque_date[0]}}</span>
                    </div>
                    </div>
                    </div>
                  <!-- *** -->

                  <!-- *** -->
               <div class="flex flex-col lg:flex-row">
                    <div class="w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8 mb-2">
                    <div class="mb-2">
                    <label for="" class="tw-form-label">Cheque Bank detail</label>
                    </div>
                      <input v-model="cheque_bank" name="cheque_bank"  value="cheque_bank" type="text" placeholder="Bank detail" class="tw-form-control w-full"><span v-if="errors.cheque_bank" class="text-red-500 text-xs font-semibold">{{errors.cheque_bank[0]}}</span>
                    </div>
                   </div>
                    <div class="w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8 mb-2">
                    <div class="mb-2">
                    <label for="" class="tw-form-label">Cheque Clearing date</label>
                    </div>
                      <input v-model="clearnig_date" name="clearnig_date"  value="clearnig_date" type="date"  class="tw-form-control w-full"><span v-if="errors.clearnig_date" class="text-red-500 text-xs font-semibold">{{errors.clearnig_date[0]}}</span>
                    </div>
                    </div>
                    </div>
                  <!-- *** -->
      </div>

      <div v-if="this.payment_method=='Bank'" class="w-full lg:w-1/2">
                    <div class="lg:mr-8 md:mr-8 mb-2">
                    <div class="mb-2">
                    <label for="" class="tw-form-label">Reference Number</label>
                    </div>
                      <input v-model="reference_number" name="reference_number"  value="reference_number" type="text"  class="tw-form-control w-full"><span v-if="errors.reference_number" class="text-red-500 text-xs font-semibold">{{errors.reference_number[0]}}</span>
                    </div>
                    </div>

       <div class="flex flex-col lg:flex-row md:flex-row">
            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
               <label for="attachment" v-model="attachment" class="tw-form-label">Attachment</label>
              <div class="my-1">
                      <input type="file" name="attachment" @change="OnFileSelected" id="attachment" class="tw-form-control w-full">
                      <span v-if="errors.attachment" class="text-red-500 text-xs font-semibold">{{errors.attachment[0]}}</span>
              </div>
              </div>
              </div>
      </div>  

         <div class="flex flex-col lg:flex-row md:flex-row">
            <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
              <div class="lg:mr-6 md:mr-6">
              <label class="tw-form-label">Remarks </label>
              <div class="my-1">
                      <input v-model="remark" name="remark"  type="text" class="tw-form-control w-full"><span v-if="errors.remark" class="text-red-500 text-xs font-semibold">{{errors.remark[0]}}</span>
              </div>
              </div>
              </div>
      </div>       
      <div class="w-full">
        
      
      <div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="check()">Submit</a>
        <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>  
      </div>
    </div>
  </div>
  </div>
</template>

<script>
  export default {
    props:['url','transaction_id'],
    data(){
      return{
        staff_id:'',
        paytype_id:'',
        account_id:'',
        payroll_id:'',
        transaction_date:'',
        cheque_number:'',
        cheque_bank:'',
        cheque_date:'',
        clearnig_date:'',
        reference_number:'',
        remark:'',
        amount:'',
        attachment:'',
        payment_method:'',
        payment_methods:['Cash','Cheque','Bank'],
        paytypes:[],
        staffs:[],
        accounts:[],
        payrollitems:[],
        editdata:[],
        errors:[],
        success:null,
      
      }
    },
        
    methods:
    {
      getList()
      {
        axios.get(this.url+'/accountant/payroll/transaction/showlist').then(response => {
          this.staffs    = response.data.staff; 
          this.paytypes   =response.data.paytype;
          this.accounts   =response.data.accounts;   
        });
       
      },
       getdetails()
      {
        axios.get(this.url+'/accountant/payroll/transaction/'+this.transaction_id+'/editshow').then(response => {
          this.editdata   =  response.data;
          this.staff_id       = this.editdata.staff_id;
          this.paytype_id       = this.editdata.paytype_id;
          this.account_id       = this.editdata.account_id;
          this.payroll_id       = this.editdata.payroll_id;
          this.transaction_date       = this.editdata.transaction_date;
          this.payment_method       = this.editdata.payment_method;
          this.amount       = this.editdata.amount;

         if(this.editdata.payment_method == "Cheque")
          {
          this.cheque_number   = this.editdata.transaction_detail.cheque_number;
          this.cheque_date     = this.editdata.transaction_detail.cheque_date;
          this.clearnig_date   = this.editdata.transaction_detail.clearnig_date;
          this.cheque_bank     = this.editdata.transaction_detail.cheque_bank;
          }
           if(this.editdata.payment_method == 'Bank')
          {
          this.reference_number       = this.editdata.reference_number;
        }
          this.remark       = this.editdata.remarks;
          console.log(response); 

          
             
        });
       
      },

      check()
      {
        var formData = new FormData();
        formData.append('staff_id',this.staff_id);
        formData.append('paytype',this.paytype_id);
        formData.append('account_id',this.account_id);
        formData.append('payroll_id',this.payroll_id);
        formData.append('transaction_date',this.transaction_date);
        formData.append('payment_method',this.payment_method);
        formData.append('amount',this.amount);
        formData.append('attachment',this.attachment);
        formData.append('cheque_number',this.cheque_number);
        formData.append('cheque_date',this.cheque_date);
        formData.append('clearnig_date',this.clearnig_date);
        formData.append('cheque_bank',this.cheque_bank);
        formData.append('reference_number',this.reference_number);
        formData.append('remark',this.remark);

       

        axios.post(this.url+'/accountant/payroll/transaction/'+this.transaction_id+'/update',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {
          this.success=response.data.success;
        }).catch(error=>{
          this.errors=error.response.data.errors;
        }); 
      },

      typechange()
      {
        if(this.staff_id!='' ){

        axios.get(this.url+'/accountant/payroll/user/'+this.staff_id+'/detail').then(response => {
          this.payrollitems   = response.data;
          console.log(response); 
             
        });

      }
        
      },

    OnFileSelected(event)
    {
      this.attachment = event.target.files[0];
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
      this.getdetails();
    }
  }
</script>