<template>
  <div class="mb-2">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
    <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Today's Absentees (Staffs)</h1>
    <div>
      <div class="flex border h-24 overflow-hidden">
      <div class="w-24 border-r flex items-center justify-center">
        <img :src="url+'/uploads/attendance.jpg'" class="w-20 h-20">
        </div>
        <ul class="list-reset px-3 w-4/5 lg:w-4/5 md:w-4/5">
          <li class="flex items-center py-2" v-if="this.absentees > 0"> 
            <p class="text-xl text-gray-800"> Total No. Of Absentees : 
              <span class="text-xl text-gray-800">
                <a href="#" @click="showAbsentees()">{{ absentees }}</a>
              </span>
            </p>
          </li>
          <li class="flex items-center py-2" v-else> 
            <p class="font-semibold text-sm" style="text-align: center">No Records Found</p>
          </li>
        </ul>
      </div>
    </div>
    <div class="overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3">
      <div v-if="show == 1" class="modal modal-mask">
        <div class="modal-wrapper px-4">
          <div class="modal-container w-full max-w-md px-8 mx-auto">
            <div class="modal-header flex justify-between items-center">
              <h2>Absent staffs</h2>
              <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
              <div class="flex items-center">
                <table class="w-full">
                  <thead class="bg-grey-light">
                    <tr class="border-b">
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Staff Name </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Session </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Reason </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Remark </th>
                    </tr>
                  </thead>   
                  <tbody>
                    <tr class="border-b" v-for="student in staffs">
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">
                          <a :href="url+'/admin/teacher/show/'+student.user_name">{{ student.user_fullname }}</a>
                        </p>
                      </td>
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">{{ student.session }}</p>
                      </td>
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">{{ student.reason }}</p>
                      </td>
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs" v-if="student.remarks != null ">{{ student.remarks }}</p>
                        <p class="font-semibold text-xs" v-else>--</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  props:['url'],
  data () {
    return {
      absentees:[],
      staffs:[],
      show:0,
      errors:[],
      success:null,
    }
  },

  methods:{
    getData()
    {
      axios.get('/admin/absentees/staff').then(response => {
        this.absentees = response.data.studentAbsentees;
        //console.log(this.absentees);   
      });
    },
    getStaffs()
    {
      axios.get('/admin/absentees/staff/list').then(response => {
        this.staffs = response.data.data;
        console.log(this.staffs);   
      });
    },
    showAbsentees()
    {
      this.show = 1 ;
      this.getStaffs();
    },
    closeModal()
    {
      this.show = 0 ;
    },
  },
  
  created()
  {   
    this.getData();
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
  margin: 20px 0;
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
