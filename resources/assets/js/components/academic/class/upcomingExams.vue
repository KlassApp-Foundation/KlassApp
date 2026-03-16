<template>
  <div class=" overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==7?'block' :'hidden']">
    <div class="my-5" v-if="Object.keys(upcoming_exams).length > 0">
      <div class="flex mx-2 my-3 items-center" v-for="upcoming_exam in upcoming_exams">
        <div class="w-4/5 mx-4 border rounded px-2 py-2">
          <div class="flex justify-between items-center">
            <div class="flex items-center w-1/2 cursor-pointer" @click="showExams(upcoming_exam[0]['exam_id'])">
              <svg v-if="arrow == 1" id="upcoming_exam[0]['exam_id']" class="w-2 h-2 m-1 fill-current text-gray-600" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="292.359px" height="292.359px" viewBox="0 0 292.359 292.359" style="enable-background:new 0 0 292.359 292.359;" xml:space="preserve"><g><path d="M222.979,133.331L95.073,5.424C91.456,1.807,87.178,0,82.226,0c-4.952,0-9.233,1.807-12.85,5.424 c-3.617,3.617-5.424,7.898-5.424,12.847v255.813c0,4.948,1.807,9.232,5.424,12.847c3.621,3.617,7.902,5.428,12.85,5.428 c4.949,0,9.23-1.811,12.847-5.428l127.906-127.907c3.614-3.613,5.428-7.897,5.428-12.847 C228.407,141.229,226.594,136.948,222.979,133.331z"/></g></svg>
              <svg v-if="arrow != 1" id="upcoming_exam[0]['exam_id']" class="w-2 h-2 m-1 fill-current text-gray-600" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="292.362px" height="292.362px" viewBox="0 0 292.362 292.362" xml:space="preserve"><g><path d="M286.935,69.377c-3.614-3.617-7.898-5.424-12.848-5.424H18.274c-4.952,0-9.233,1.807-12.85,5.424 C1.807,72.998,0,77.279,0,82.228c0,4.948,1.807,9.229,5.424,12.847l127.907,127.907c3.621,3.617,7.902,5.428,12.85,5.428 s9.233-1.811,12.847-5.428L286.935,95.074c3.613-3.617,5.427-7.898,5.427-12.847C292.362,77.279,290.548,72.998,286.935,69.377z"></path></g></svg>
              <h4 class="font-semibold text-sm text-gray-700 mx-4">{{ upcoming_exam[0]['exam_name'] }}</h4>
            </div>
            <div class="w-1/2 flex justify-end">
              <p class="text-xs text-gray-600 flex items-center">
                <svg class="w-3 h-3 fill-current text-gray-600" height="384pt" viewBox="0 0 384 384" width="384pt" xmlns="http://www.w3.org/2000/svg"><path d="m343.59375 101.039062c-7.953125 3.847657-11.28125 13.417969-7.433594 21.367188 10.511719 21.714844 15.839844 45.121094 15.839844 69.59375 0 88.222656-71.777344 160-160 160s-160-71.777344-160-160 71.777344-160 160-160c36.558594 0 70.902344 11.9375 99.328125 34.519531 6.894531 5.503907 16.976563 4.351563 22.480469-2.566406 5.503906-6.914063 4.351562-16.984375-2.570313-22.480469-33.652343-26.746094-76-41.472656-119.238281-41.472656-105.863281 0-192 86.136719-192 192s86.136719 192 192 192 192-86.136719 192-192c0-29.335938-6.40625-57.449219-19.039062-83.527344-3.839844-7.96875-13.441407-11.289062-21.367188-7.433594zm0 0"/><path d="m192 64c-8.832031 0-16 7.167969-16 16v112c0 8.832031 7.167969 16 16 16h80c8.832031 0 16-7.167969 16-16s-7.167969-16-16-16h-64v-96c0-8.832031-7.167969-16-16-16zm0 0"/></svg>
                <span class="mx-1">Starts {{ upcoming_exam[0]['start_time'] }}</span>
              </p>
            </div>
          </div>
          <div :id="upcoming_exam[0]['exam_id']" class="flex flex-wrap custom-table mx-3 my-3 hidden">
            <table class="w-3/4">
              <thead class="bg-grey-light">
                <tr class="border-b">
                  <th class="text-left text-sm px-2 py-2 text-grey-darker"> Subject </th>
                  <th class="text-left text-sm px-2 py-2 text-grey-darker"> Start Date </th>
                </tr>
              </thead>   
              <tbody v-if="upcoming_exam != ''">
                <tr class="border-b" v-for="exam in upcoming_exam">
                  <td class="flex py-3 px-2 items-center">
                    <p class="font-semibold text-xs">{{ exam.subject_name }}</p>
                  </td>
                  <td class="py-3 px-2">
                    <p class="font-semibold text-xs">{{ exam.start_time }}</p>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr class="border-b">
                  <td colspan="4">
                    <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="my-5 items-center" v-else>
      <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
    </div>
  </div>
</template>

<script>
import { bus } from "../../../app";

export default {
  props:['url','id','mode'],
  data () {
    return {
      profile_tab:'',
      arrow:1,
      upcoming_exams:[],
      errors:[],
      success:null,
    }
  },

  methods:{
    getData()
    {
      axios.get('/'+this.mode+'/standardLink/show/upcomingExams/'+this.id).then(response => {
        this.upcoming_exams = response.data;
        //console.log(this.upcoming_exams);   
      });
    },

    showExams(id)
    {
      if($('#'+id).hasClass('hidden'))
      {
        $('#'+id).removeClass('hidden').addClass('block');
        this.arrow = 0;
      }
      else
      {
        $('#'+id).removeClass('block').addClass('hidden');
        this.arrow = 1;
      }
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