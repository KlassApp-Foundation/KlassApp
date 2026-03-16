<template>
  <div class=" overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==8?'block' :'hidden']">
    <div class="my-5" v-if="Object.keys(past_exams).length > 0">
      <div class="flex mx-2 my-3 items-center" v-for="past_exam in past_exams">
        <div class="w-full lg:w-4/5 lg:mx-4 border rounded px-2 py-2">
          <div class="flex justify-between items-center">
            <div class="flex items-center w-1/2 cursor-pointer" @click="showExams(past_exam[0]['exam_id'])">
              <svg v-if="arrow == 1" id="past_exam[0]['exam_id']" class="w-2 h-2 fill-current text-gray-600" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="292.359px" height="292.359px" viewBox="0 0 292.359 292.359" style="enable-background:new 0 0 292.359 292.359;" xml:space="preserve"><g><path d="M222.979,133.331L95.073,5.424C91.456,1.807,87.178,0,82.226,0c-4.952,0-9.233,1.807-12.85,5.424 c-3.617,3.617-5.424,7.898-5.424,12.847v255.813c0,4.948,1.807,9.232,5.424,12.847c3.621,3.617,7.902,5.428,12.85,5.428 c4.949,0,9.23-1.811,12.847-5.428l127.906-127.907c3.614-3.613,5.428-7.897,5.428-12.847 C228.407,141.229,226.594,136.948,222.979,133.331z"/></g></svg>
              <svg v-if="arrow != 1" id="past_exam[0]['exam_id']" version="1.1"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="292.362px" height="292.362px" viewBox="0 0 292.362 292.362" xml:space="preserve" class="w-2 h-2 fill-current text-gray-600"><g><path d="M286.935,69.377c-3.614-3.617-7.898-5.424-12.848-5.424H18.274c-4.952,0-9.233,1.807-12.85,5.424 C1.807,72.998,0,77.279,0,82.228c0,4.948,1.807,9.229,5.424,12.847l127.907,127.907c3.621,3.617,7.902,5.428,12.85,5.428 s9.233-1.811,12.847-5.428L286.935,95.074c3.613-3.617,5.427-7.898,5.427-12.847C292.362,77.279,290.548,72.998,286.935,69.377z"></path></g></svg>
              <h4 class="font-semibold text-sm text-gray-700 mx-4">{{ past_exam[0]['exam_name'] }}</h4>
            </div>
            <div class="w-1/2 flex justify-end">
              <p class="text-xs text-gray-600 flex items-center">
                <svg class="w-3 h-3 fill-current text-gray-600 hidden lg:block" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M256,0C114.841,0,0,114.841,0,256s114.841,256,256,256s256-114.841,256-256S397.159,0,256,0z M256,468.732 c-117.301,0-212.732-95.431-212.732-212.732S138.699,43.268,256,43.268S468.732,138.699,468.732,256S373.301,468.732,256,468.732z"/></g></g><g><g><path d="M372.101,248.068H271.144V97.176c0-11.948-9.686-21.634-21.634-21.634c-11.948,0-21.634,9.686-21.634,21.634v172.525 c0,11.948,9.686,21.634,21.634,21.634c0.244,0,0.48-0.029,0.721-0.036c0.241,0.009,0.477,0.036,0.721,0.036h121.149 c11.948,0,21.634-9.686,21.634-21.634S384.049,248.068,372.101,248.068z"/></g></g></svg>
                <span class="mx-1">Held On {{ past_exam[0]['start_time'] }}</span>
              </p>
            </div>
          </div>
          <div :id="past_exam[0]['exam_id']" class="flex flex-wrap custom-table mx-3 my-3 hidden">
            <table class="w-full lg:w-3/4 md:w-3/4">
              <thead class="bg-grey-light">
                <tr class="border-b">
                  <th class="text-left text-sm px-2 py-2 text-grey-darker"> Subject </th>
                  <th class="text-left text-sm px-2 py-2 text-grey-darker"> Start Date </th>
                </tr>
              </thead>   
              <tbody v-if="past_exam != ''">
                <tr class="border-b" v-for="exam in past_exam">
                  <td class="py-3 px-2">
                   <div class="flex items-center">
                    <p class="font-semibold text-xs">{{ exam.subject_name }}</p>
                    </div>
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
      past_exams:[],
      errors:[],
      success:null,
    }
  },

  methods:{
    getData()
    {
      axios.get('/'+this.mode+'/standardLink/show/pastExams/'+this.id).then(response => {
        this.past_exams = response.data;
        //console.log(this.past_exams);   
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