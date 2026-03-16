<template>
  <div class="mb-2">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
    <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Today's Absentees (Students)</h1>
    <div>
      <div class="flex border h-24 overflow-hidden">
      <div class="w-24 border-r flex items-center justify-center">
        <img :src="url+'/uploads/attendance.jpg'" class="w-20 h-20"> <!-- w-1/5 lg:w-1/5 md:w-1/5  -->
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
              <h2>Absent Students</h2>
              <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
         <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center w-full">
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <select class="tw-form-control w-full" id="standardLink_id" v-model="standardLink_id" name="standardLink_id" @change="classChange">
                                <option value="" disabled>Select Class</option>
                                <option v-for="standard in standardLinklist" v-bind:value="standard.id">{{standard.standard_name}}-{{standard.section_name}}</option>
                            </select>
                        </div>
                    </div> 
                </div>
                <div class="flex">
                <div class="w-1/2 flex items-center tw-form-control mr-1 lg:mr-4 md:mr-4"> 
                  <input type="radio" v-model="session" name="session" id="forenoon" value="forenoon" @change="classChange">
                  <span class="text-sm mx-1">Forenoon</span>
                </div>
                <div class="w-1/2 flex items-center tw-form-control"> 
                  <input type="radio" v-model="session" name="session" id="afternoon" value="afternoon" @change="classChange">
                  <span class="text-sm mx-1">Afternoon</span>
                </div>
                </div>
            </div>

              <div class="flex items-center">
                <table class="w-full">
                  <thead class="bg-grey-light">
                    <tr class="border-b">
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Student Name </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Class </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Session </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Reason </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Remark </th>
                    </tr>
                  </thead>   
                  <tbody>
                    <tr class="border-b" v-for="student in students">
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">
                          <a :href="url+'/admin/student/show/'+student.user_name">{{ student.user_fullname }}</a>
                        </p>
                      </td>
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">
                          <a :href="url+'/admin/standardLink/show/'+student.class_id">{{ student.class }}</a>
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
      standardLinklist:[],
      absentees:[],
      students:[],
      show:0,
      errors:[],
      success:null,
      standardLink_id:'',
      session:'forenoon'
    }
  },

  methods:{
    getData()
    {
      axios.get('/admin/absentees/student').then(response => {
        this.absentees = response.data.studentAbsentees;
        this.standardLinklist = response.data.standardLinklist;
        //console.log(this.standardLinklist);   
      });
    },
    getStudents()
    {
      axios.get('/admin/absentees/student/list?standardLink_id='+this.standardLink_id+'&session='+this.session).then(response => {
        this.students = response.data.data;
        //console.log(this.students);   
      });
    },

    classChange()
    {
      //alert(this.standardLink_id);
       this.getStudents();
    },

    showAbsentees()
    {
      this.show = 1 ;
      this.getStudents();
    },
    closeModal()
    {
      this.show = 0 ;
      this.standardLink_id='';
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
