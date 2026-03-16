<template>
  <div class="relative">
    <div v-if="this.success!=null" class="alert alert-success mt-2" id="success-alert">{{this.success}}</div>
    <div class="flex flex-wrap lg:flex-row justify-between items-center">
      <div class="">
        <h1 class="accountant-h1 my-3">Payroll</h1>
      </div>
    </div>
    <div class="">
      <div class="flex flex-wrap custom-table my-3 overflow-auto">
        <table class="w-full">
          <thead class="bg-grey-light">
            <tr class="border-b">
              <th class="text-left text-sm px-2 py-2 text-grey-darker">No</th>
              <!-- <th class="text-left text-sm px-2 py-2 text-grey-darker">Staff name</th> -->
              <th class="text-left text-sm px-2 py-2 text-grey-darker">Salary period</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Net Salary</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Status</th>
               <th class="text-left text-sm px-2 py-2 text-grey-darker">Action</th>
            </tr>
          </thead>   
          <tbody v-if="this.payslips != ''">
            <tr class="border-b" v-for="(payslip,k1) in payslips" :key="k=k1+1" >
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{payslip.payrollno}}</p>
              </td>
             <!--  <td class="py-3 px-2" >
                  <p class="font-semibold text-xs">{{payslip.staffname}}</p>
              </td> -->
              <td class="py-3 px-2" >
                <p  class="font-semibold text-xs">{{payslip.start_date}} to {{payslip.end_date}}</p>
              </td>
             <td class="py-3 px-2" >
               <p class="font-semibold text-xs">{{payslip.total}}</p>
              </td>
              <td class="py-3 px-2" >
               <p class="font-semibold text-xs">{{payslip.status}}</p>
              </td>
              <td class="py-3 px-2">
                <div class="flex items-center">                
                  

                   <a :href="url+'/teacher/payroll/payslip/'+payslip.id+'/view'" target="blank" class="cursor-pointer" title="View">
                    <svg id="Layer" enable-background="new 0 0 64 64" height="512" viewBox="0 0 64 64" width="512" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current text-gray-500 mx-1"><path d="m30.586 45.414c.39.391.902.586 1.414.586s1.023-.195 1.414-.586l10-10c.781-.781.781-2.047 0-2.828s-2.047-.781-2.828 0l-6.586 6.586v-29.172c0-1.104-.896-2-2-2s-2 .896-2 2v29.172l-6.586-6.586c-.78-.781-2.048-.781-2.828 0-.781.781-.781 2.047 0 2.828z"></path><path d="m18 56h28c3.309 0 6-2.691 6-6v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 1.103-.897 2-2 2h-28c-1.103 0-2-.897-2-2v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 3.309 2.691 6 6 6z"></path></svg>
                  </a>

                  <a  :href="url+'/teacher/payroll/payslip/'+payslip.id+'/show'" class="cursor-pointer">
                    <svg height="512pt" viewBox="-27 0 512 512" width="512pt" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current text-gray-500 mx-2"><path d="m188 492c0 11.046875-8.953125 20-20 20h-88c-44.113281 0-80-35.886719-80-80v-352c0-44.113281 35.886719-80 80-80h245.890625c44.109375 0 80 35.886719 80 80v191c0 11.046875-8.957031 20-20 20-11.046875 0-20-8.953125-20-20v-191c0-22.054688-17.945313-40-40-40h-245.890625c-22.054688 0-40 17.945312-40 40v352c0 22.054688 17.945312 40 40 40h88c11.046875 0 20 8.953125 20 20zm117.890625-372h-206c-11.046875 0-20 8.953125-20 20s8.953125 20 20 20h206c11.042969 0 20-8.953125 20-20s-8.957031-20-20-20zm20 100c0-11.046875-8.957031-20-20-20h-206c-11.046875 0-20 8.953125-20 20s8.953125 20 20 20h206c11.042969 0 20-8.953125 20-20zm-226 60c-11.046875 0-20 8.953125-20 20s8.953125 20 20 20h105.109375c11.046875 0 20-8.953125 20-20s-8.953125-20-20-20zm355.472656 146.496094c-.703125 1.003906-3.113281 4.414062-4.609375 6.300781-6.699218 8.425781-22.378906 28.148437-44.195312 45.558594-27.972656 22.324219-56.757813 33.644531-85.558594 33.644531s-57.585938-11.320312-85.558594-33.644531c-21.816406-17.410157-37.496094-37.136719-44.191406-45.558594-1.5-1.886719-3.910156-5.300781-4.613281-6.300781-4.847657-6.898438-4.847657-16.097656 0-22.996094.703125-1 3.113281-4.414062 4.613281-6.300781 6.695312-8.421875 22.375-28.144531 44.191406-45.554688 27.972656-22.324219 56.757813-33.644531 85.558594-33.644531s57.585938 11.320312 85.558594 33.644531c21.816406 17.410157 37.496094 37.136719 44.191406 45.558594 1.5 1.886719 3.910156 5.300781 4.613281 6.300781 4.847657 6.898438 4.847657 16.09375 0 22.992188zm-41.71875-11.496094c-31.800781-37.832031-62.9375-57-92.644531-57-29.703125 0-60.84375 19.164062-92.644531 57 31.800781 37.832031 62.9375 57 92.644531 57s60.84375-19.164062 92.644531-57zm-91.644531-38c-20.988281 0-38 17.011719-38 38s17.011719 38 38 38 38-17.011719 38-38-17.011719-38-38-38zm0 0"></path></svg>
                  </a>

                  
                </div>
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
              <th class="text-left text-xs px-2 py-2 text-grey-darker">Amount </th>
          
            </tr>
          </thead>   
          <tbody v-if="this.payslipitems != ''" >
            <tr class="border-b" v-for="payslipitem in payslipitems"  >
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{payslipitem.head}}</p>
              </td>
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{payslipitem.head_type}}</p>
              </td>
              <td class="py-3 px-2" >
                <p class="font-semibold text-xs">{{payslipitem.amount}}</p>
              </td>
              
           </tr>
         </tbody>
       </table>
            </div>
            <div class="flex justify-between items-center py-1">
               <p class="font-semibold text-xs">Total earnings: {{salary.earning}}</p>
              <p class="font-semibold text-xs">Total deductions:{{salary.deduction}}</p>
           <!--  </div>
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
      payslips:[],
      salary:[],
      payslipitems:[],
      show:0,
      success:null,
    
    }
  },

  methods:
  {
    getlist()
    {
      axios.get('/teacher/payroll/payslip/list').then(response => {
        this.payslips = response.data.data;
        //console.log(this.templates);    
      });
    },

   
    detail(id)
      {
        
        this.show=1;
        axios.get(this.url+'/teacher/payroll/payslip/'+id+'/show').then(response => {
         
          this.salary   = response.data.data;
          this.payslipitems   = this.salary.payslipitems;

          console.log(response); 
             
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