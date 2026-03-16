<template>
  <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto" v-bind:class="[this.profile_tab==9?'block' :'hidden']">
    <div class="my-5" v-if="Object.keys(events).length > 0">
      <div class="flex mx-2 my-3 items-start" v-for="event in events">
        <div class="w-full mx-4 border rounded px-2 py-2">
          <div class="flex flex-col lg:flex-row md:flex-row justify-between lg:items-center">
          <div class="flex w-full lg:w-1/3">
            <div class="">
              <img :src="event['image']" class="w-10 h-10">
            </div>
            <div class=" flex items-center cursor-pointer ">
              <h4 class="font-semibold text-sm text-gray-700 mx-4">
                <a :href="url+'/admin/events/show/details/'+event['id']" v-if="mode == 'admin'">{{ event['title'] }}</a>
                <a href="#" v-else>{{ event['title'] }}</a>
              </h4>
            </div>
            </div>
            <div class="w-full lg:w-1/3 flex lg:items-center">
              <h4 class="font-semibold text-sm text-gray-700 lg:mx-4 my-1 lg:my-0">
                <span class="mx-1">By : {{ event['organised_by'] }}</span>
              </h4>
            </div>
            <div class="w-full lg:w-2/3 flex justify-end">
              <p class="text-xs text-gray-600 flex items-center">
                <svg class="w-3 h-3 fill-current text-gray-600" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M256,0C114.841,0,0,114.841,0,256s114.841,256,256,256s256-114.841,256-256S397.159,0,256,0z M256,468.732 c-117.301,0-212.732-95.431-212.732-212.732S138.699,43.268,256,43.268S468.732,138.699,468.732,256S373.301,468.732,256,468.732z"/></g></g><g><g><path d="M372.101,248.068H271.144V97.176c0-11.948-9.686-21.634-21.634-21.634c-11.948,0-21.634,9.686-21.634,21.634v172.525 c0,11.948,9.686,21.634,21.634,21.634c0.244,0,0.48-0.029,0.721-0.036c0.241,0.009,0.477,0.036,0.721,0.036h121.149 c11.948,0,21.634-9.686,21.634-21.634S384.049,248.068,372.101,248.068z"/></g></g></svg>
                <span class="mx-1">{{ event['start_date'] }} - {{ event['end_date'] }}</span>
              </p>
            </div>
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
      events:[],
      errors:[],
      success:null,
    }
  },

  methods:{
    getData()
    {
      axios.get('/'+this.mode+'/standardLink/show/events/'+this.id).then(response => {
        this.events = response.data.data;
        //console.log(this.events);   
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