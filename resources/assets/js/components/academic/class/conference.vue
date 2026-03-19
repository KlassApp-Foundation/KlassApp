<template>
  <div class=" overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==13?'block' :'hidden']">
    <div class="my-5">
      <div class="flex mx-2 items-center">
        <div class="w-full rounded custom-table">
          <table class="w-full">
            <thead class="bg-grey-light">
              <tr class="border-b">
                <th class="text-left text-sm px-2 py-2 text-grey-darker"> Title </th>
                <th class="text-left text-sm px-2 py-2 text-grey-darker"> Description </th>
                <th class="text-left text-sm px-2 py-2 text-grey-darker"> Staff </th>
                <th class="text-left text-sm px-2 py-2 text-grey-darker"> Subject </th>
                <th class="text-left text-sm px-2 py-2 text-grey-darker"> Scheduled at </th>
                <th class="text-left text-sm px-2 py-2 text-grey-darker"> Status </th>
              </tr>
            </thead>   
            <tbody v-if="this.conference != ''">
              <tr class="border-b" v-for="conference in conference">
                <td class="py-3 px-2">
                   <a :href="url+'/admin/video-conference/show/'+conference.id" target="_blank" >
                  <p class="font-semibold text-xs">{{conference.title}}</p>
                  </a>
                </td>
                <td class="py-3 px-2">
                  <p class="font-semibold text-xs">{{conference.description}}</p>
                </td>
                <td class="py-3 px-2">
                  <p class="font-semibold text-xs">{{conference.created_by}}</p>
                </td>
                 <td class="py-3 px-2 cursor-pointer" >
                  <p class="font-semibold text-xs">{{conference.subject}}</p>
                </td>
                <td class="py-3 px-2">
                  <p class="font-semibold text-xs">{{conference.joining_date}} {{conference.start_time}} </p>
                </td>
                <td class="py-3 px-2 cursor-pointer" >
                  <p class="font-semibold text-xs" v-if="conference.compose_status!='available'">{{conference.status}}</p>
                  <p class="font-semibold text-xs" v-else=""><a :href="url+'/admin/video-conference/recordings/'+conference.id" target="_blank" class="cursor-pointer" title="play">Play</a></p>
                </td>
              </tr>
               <paginate
              v-model="page"
              :page-count="this.page_count"
              :page-range="3"
              :margin-pages="1"
              :click-handler="getData"
              :prev-text="'<'"
              :next-text="'>'"
              :container-class="'pagination'"
              :page-class="'page-item'"
              :prev-link-class="'prev'"
              :next-link-class="'next'">
              </paginate>    
            </tbody>
            <tbody v-if="this.conference == ''">
              <tr class="border-b">
                <td colspan="6">
                  <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- <div v-for="fee in fees">
      <div v-if="show == fee['id']+'paid'" class="modal modal-mask">
        <div class="modal-wrapper px-4">
          <div class="modal-container w-full max-w-md px-8 mx-auto">
            <div class="modal-header flex justify-between items-center">
              <h2>Paid Students</h2>
              <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <table class="w-full">
                  <thead class="bg-grey-light">
                    <tr class="border-b">
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Student Name </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Paid On </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Notify Parents </th>
                    </tr>
                  </thead>   
                  <tbody>
                    <tr class="border-b" v-for="student in paidStudents[fee['id']]">
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">
                          <a :href="url+'/admin/student/show/'+student.user_name" target="_blank" v-if="mode == 'admin'">{{ student.user_fullname }}</a>
                          <a href="#" v-else>{{ student.user_fullname }}</a>
                        </p>
                      </td>
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">{{ student.paid_on }}</p>
                      </td>
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">{{ student.notify_parent }}</p>
                      </td>
                    </tr>
                  </tbody>
                  <tbody v-if="Object.keys(paidStudents[1]).length == 0">
                    <tr class="border-b">
                      <td colspan="3">
                        <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
            </div>
          </div>
        </div>
      </div> -->
   <!--    <div v-if="show == fee['id']+'unpaid'" class="modal modal-mask">
        <div class="modal-wrapper px-4">
          <div class="modal-container w-full max-w-md px-8 mx-auto">
            <div class="modal-header flex justify-between items-center">
              <h2>Unpaid Students</h2>
              <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <table class="w-full">
                  <thead class="bg-grey-light">
                    <tr class="border-b">
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Student Name </th>
                    </tr>
                  </thead>  
                  <tbody>
                    <tr class="border-b" v-for="student in unpaidStudents[fee['id']]">
                      <td class="py-3 px-2">
                        <p class="font-semibold text-xs">
                          <a :href="url+'/admin/student/show/'+student.name" target="_blank" v-if="mode == 'admin'">{{ student.fullname }}</a>
                          <a href="#" v-else>{{ student.fullname }}</a>
                        </p>
                      </td>
                    </tr>
                  </tbody>
                  <tbody v-if="Object.keys(unpaidStudents[1]).length == 0">
                    <tr class="border-b">
                      <td colspan="1">
                        <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
            </div>
          </div>
        </div>
      </div> -->
    </div>
  </div>
</template>

<script>
import { bus } from "../../../app";

export default {
  props:['url','id','mode'],
  data () {
    return {
      page_count:0,
      page:0,
      total :'',
      profile_tab:'',
      show:'',
      conference:[],
      errors:[],
      success:null,
    }
  },

  methods:{
    getData(page=1)
    {
      axios.get('/'+this.mode+'/standardLink/show/conference/'+this.id+'?page='+this.page).then(response => {
        this.conference           = response.data.data;
        this.page_count           = response.data.meta.last_page;
        this.total                = response.data.meta.total;
       
        //console.log(response.data);   
      });
    },

  },
  
  created()
  {   
    this.getData();
    
    bus.$on("dataProfileTab", data => {
      if(data!='')
      {
        this.profile_tab=data;                   
      }
    });   
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
    height: 550px;
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