<template>
  <div class="relative">
    <div v-if="this.success!=null" class="alert alert-success mt-2" id="success-alert">{{this.success}}</div>
    <div class="flex flex-wrap lg:flex-row justify-between items-center">
      <div class="">
        <h1 class="admin-h1 my-3">Transaction</h1>
      </div>
    </div>
    <div class="">
      <div class="flex flex-wrap custom-table my-3 overflow-auto">
        <table class="w-full">
          <thead class="bg-grey-light">
            <tr class="border-b">
              <th class="text-left text-sm px-2 py-2 text-grey-darker">Voucher No</th>
             <!--  <th class="text-left text-sm px-2 py-2 text-grey-darker">Staff name</th> -->
              <th class="text-left text-sm px-2 py-2 text-grey-darker">Pay head</th>
              <th class="text-left text-sm px-2 py-2 text-grey-darker">Account</th>
              <th class="text-left text-sm px-2 py-2 text-grey-darker">Transaction date</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Amount</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Payment Method</th>
                <th class="text-left text-sm px-2 py-2 text-grey-darker">Attachment</th>
            </tr>
          </thead>   
          <tbody v-if="this.transactions != ''">
            <tr class="border-b" v-for="(transaction,k1) in transactions" :key="k=k1+1" >
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{transaction.transaction_no}}</p>
              </td>
             <!--  <td class="py-3 px-2" >
                  <p class="font-semibold text-xs">{{transaction.staffname}}</p>
              </td> -->
              <td class="py-3 px-2" >
                <p  class="font-semibold text-xs">{{transaction.paytype}}</p>
                <p v-if="transaction.paytype=='Salary'" class="font-semibold text-xs">{{transaction.payrollno}}</p>
              </td>
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{transaction.account}}</p>
               </td>
             <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{transaction.transaction_date}}</p>
               </td>
                <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{transaction.amount}}</p>
               </td>
                <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{transaction.payment_method}}</p>
                <p v-if="transaction.payment_method=='Cheque'" class="font-semibold text-xs" v-for="(value, key, index) in transaction.transaction_detail">
                  {{key}}:{{value}}
                </p>
                <p v-if="transaction.payment_method=='Bank'" class="font-semibold text-xs">Reference-number:
                  {{transaction.reference_number}}
                </p>
               </td>
               <td class="py-3 px-2">
                <a :href="transaction.attachment" target="_blank" v-if="transaction.attachment != ''" title="Attachment">
                  <svg id="Layer" enable-background="new 0 0 64 64" height="512" viewBox="0 0 64 64" width="512" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current text-gray-500 mx-1"><path d="m30.586 45.414c.39.391.902.586 1.414.586s1.023-.195 1.414-.586l10-10c.781-.781.781-2.047 0-2.828s-2.047-.781-2.828 0l-6.586 6.586v-29.172c0-1.104-.896-2-2-2s-2 .896-2 2v29.172l-6.586-6.586c-.78-.781-2.048-.781-2.828 0-.781.781-.781 2.047 0 2.828z"></path><path d="m18 56h28c3.309 0 6-2.691 6-6v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 1.103-.897 2-2 2h-28c-1.103 0-2-.897-2-2v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 3.309 2.691 6 6 6z"></path></svg>
                </a>
                <p class="font-semibold text-xs" v-else> -- </p>
              </td>
            </tr>
          </tbody>
          <tbody v-else="">
            <tr class="border-b">
              <td colspan="9">
                <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
<!--show modal  -->
     <div v-if="this.show == 1" class="modal modal-mask">
      <div class="modal-wrapper px-4">
        <div class="modal-container w-full  max-w-md px-8 mx-auto">
          <div class="modal-header flex justify-between items-center">
            <h2>Salary</h2>
            <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">
              &times;
            </button>
          </div>
          <div class="modal-body" >
            <div class="flex items-center">
         
       <table class="w-full border" >
          <thead class="bg-grey-light">
            <tr class="border-b">
              <th class="text-left text-xs px-2 py-2 text-grey-darker">Payhead </th>
              <th class="text-left text-xs px-2 py-2 text-grey-darker">Type</th>
              <th class="text-left text-xs px-2 py-2 text-grey-darker">Category</th>
              <th class="text-left text-xs px-2 py-2 text-grey-darker">Amount </th>
          
            </tr>
          </thead>   
          <tbody v-if="this.salaryitems != ''" >
            <tr class="border-b" v-for="salaryitem in salaryitems"  >
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{salaryitem.head}}</p>
              </td>
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{salaryitem.head_type}}</p>
              </td>
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{salaryitem.category}}</p>
              </td>
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{salaryitem.amount}}</p>
              </td>
              
           </tr>
         </tbody>
       </table>
            </div>
            <div class="flex justify-between items-center py-1">
               <p class="font-semibold text-xs">Total earnings: {{salary.earning}}</p>
                <p class="font-semibold text-xs">Total deductions :{{salary.deduction}}</p>
        <!--     </div>
          <div class="flex justify-between items-center py-1"> -->
               <p class="font-semibold text-xs">Net salary: {{salary.total}}</p>
                
            </div>
          </div>
         
        </div>
      </div>
    </div>
<!-- End modal -->
  </div>
</template>

<script>

export default {
  props:['url'],
  data () {
    return {
      transactions:[],
      cheque_deatails:[],
      salary:[],
      salaryitems:[],
      show:0,
      success:null,
    
    }
  },

  methods:
  {
    getlist()
    {
      axios.get('/teacher/payroll/transaction/list').then(response => {
        this.transactions = response.data.data;
        //console.log(this.templates);    
      });
    },

   
      closeModal()
      {
        this.show=0;
      },


  },
  
  created()
  {   
    this.getlist();
  }
}
</script>

<style scoped>

.modal-mask {
  position: fixed;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, .5);
  display: table;
  transition: opacity .3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
    overflow:auto;
}

.modal-container {
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
  transition: all .3s ease;
 /* height: 550px;*/
  overflow:auto;
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-body {
  margin: 10px 0;
}

.modal-default-button {
  float: right;
}

/*
 * The following styles are auto-applied to elements with
 * transition="modal" when their visibility is toggled
 * by Vue.js.
 *
 * You can easily play with the modal transition by editing
 * these styles.
 */

.modal-enter {
  opacity: 0;
}

.modal-leave-active {
  opacity: 0;
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}

.text-danger
{
  color:red;
}
</style>