<template>
  <div>
    <portal to="teacherdetail">
      <div class="hide-menu absolute top-0" id="show-detail">
        <div class="bg-white w-full lg:w-2/5 md:w-2/5 shadow-lg border border-r-0 member-detail z-40 right-0 fixed h-full overflow-y-auto">
          <a href="#" @click="disableform()" class="absolute right-0">
            <svg class="w-3 h-3 m-3 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.001 512.001" style="enable-background:new 0 0 512.001 512.001;" xml:space="preserve"><g><g><path d="M284.286,256.002L506.143,34.144c7.811-7.811,7.811-20.475,0-28.285c-7.811-7.81-20.475-7.811-28.285,0L256,227.717 L34.143,5.859c-7.811-7.811-20.475-7.811-28.285,0c-7.81,7.811-7.811,20.475,0,28.285l221.857,221.857L5.858,477.859 c-7.811,7.811-7.811,20.475,0,28.285c3.905,3.905,9.024,5.857,14.143,5.857c5.119,0,10.237-1.952,14.143-5.857L256,284.287 l221.857,221.857c3.905,3.905,9.024,5.857,14.143,5.857s10.237-1.952,14.143-5.857c7.811-7.811,7.811-20.475,0-28.285 L284.286,256.002z"/></g></g></svg>
          </a>
          <div class="flex flex-col lg:flex-row md:flex-row py-3">
            <div class="w-3/4 lg:w-1/5 md:w-1/5 p-3">
              <img v-bind:src="user.avatar" class="lg:w-24 lg:h-24 md:w-24 md:h-24">
            </div>
            <div class="w-full lg:w-4/5 md:w-4/5 px-3 lg:px-0 md:px-0">
              <div>
                <div class="leading-relaxed">
                  <h1 class="text-2xl font-semibold text-gray-700 capitalize">{{ user.fullname }}</h1>
                  <h1 class="text-base font-semibold text-gray-700 capitalize" v-if="user.designation != 'others'">{{ user.designation_name }}</h1>
                  <h1 class="text-base font-semibold text-gray-700 capitalize" v-if="user.designation == 'others'">{{ user.sub_designation }}</h1>
                  <p class="text-gray-700 font-semibold text-sm" v-if="user.class_teacher != null"> Class Teacher : 
                    <span>{{ user.class_teacher }}</span>
                  </p>
                </div>
              </div>
              <div v-if="gtimetableEnabled">   
              <div class="my-4 p-2 member-contact" v-if="Object.keys(this.timetablelist).length > 0">
                <table class="w-full">
                  <thead class="">
                    <tr class="">
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Period </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Class </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Subject </th>
                    </tr>
                  </thead> 
                  <tbody v-for="(timetable,key) in timetablelist">
                    <tr v-if="key != 'periodCount'" v-for="(data,standard) in timetable">
                      <td class="py-3 px-2" style="border-style: none;">
                        <p class="font-semibold text-xs" v-if="key != 'periodCount' & timetable != 'Free'">{{ key }}</p>
                        <p class="font-semibold text-xs" v-if="timetable == 'Free'">{{ key }}</p>
                      </td>
                      <td class="py-3 px-2" style="border-style: none;">
                        <p class="font-semibold text-xs" v-if="key != 'periodCount' & timetable != 'Free'">{{ standard }}</p>
                        <p class="font-semibold text-xs" v-if="timetable == 'Free'">FREE</p>
                      </td>
                      <td class="py-3 px-2" style="border-style: none;">
                        <p class="font-semibold text-xs" v-if="key != 'periodCount' & timetable != 'Free'">{{ data['subject_id'] }}</p>
                        <p class="font-semibold text-xs" v-if="timetable == 'Free'">FREE</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>   
              <div class="my-4 p-2 member-contact">
                <table class="w-full">
                  <thead class="">
                    <tr class="">
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Period </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Class </th>
                      <th class="text-left text-sm px-2 py-2 text-grey-darker"> Subject </th>
                    </tr>
                  </thead> 
                  <tbody>
                    <tr>
                      <td colspan="3">
                        <p class="font-semibold text-xs" style="text-align: center;">No Records Found</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
              <div class="pt-3 border-t">
                <a :href="this.url+'/admin/teacher/show/'+this.membername" class="text-blue-500 text-sm flex items-center">
                  <span>View {{ user.fullname}}'s Full Detail</span>
                    <svg class="w-4 h-4 mx-2 fill-current text-blue-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
                    <g>
                      <g>
                        <path d="M508.875,248.458l-160-160c-3.063-3.042-7.615-3.969-11.625-2.313c-3.99,1.646-6.583,5.542-6.583,9.854v21.333 c0,2.833,1.125,5.542,3.125,7.542l109.792,109.792H10.667C4.771,234.667,0,239.437,0,245.333v21.333 c0,5.896,4.771,10.667,10.667,10.667h432.917L333.792,387.125c-2,2-3.125,4.708-3.125,7.542V416c0,4.313,2.594,8.208,6.583,9.854 c1.323,0.552,2.708,0.813,4.083,0.813c2.771,0,5.5-1.083,7.542-3.125l160-160C513.042,259.375,513.042,252.625,508.875,248.458z"/>
                      </g>
                    </g>
                  </svg>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </portal>
  </div>
</template>

<script>

  import { bus } from "../../app";
  import PortalVue from "portal-vue";
  export default {
    props:['url'],
    data(){
      return{
        timetablelist:[],
        periodCount:'',
        user:[],
        errors:[],
        success:null,
        membername:null,
        gtimetableEnabled: false,
      }
    },
    mounted() 
    {
      this.gtimetableEnabled = window.AppConfig?.gtimetable_enabled ?? false;
    },

    created() 
    {
      //
      bus.$on("dataMemberName", data => {
        if(data!='')
        {
          this.membername=data;  
          this.getData();
          this.getTimetable();
          this.timetablelist = [];
        }
      });

      bus.$on("datashowFilter", data => {
        if(data!='')
        {
          this.disableform();
          this.reset();
        }
      });
    },

    methods:
    {
      disableform()
      {
        $('#show-detail').removeClass('block').addClass('hide-menu');
      },

      getData()
      {
        axios.get('/admin/teacher/show/details/'+this.membername).then(response => {
          this.user = response.data.data[0];
          //console.log(this.user);
        });
      },

      reset()
      {
        this.timetablelist=[];
        this.periodCount='';

      }, 

      getTimetable()
      {
        axios.get('/admin/teacher/show/timetable/'+this.membername).then(response => {

          //console.log(response);
          this.reset();
          if(response.data!=''){
          if(response.data.data[0]!=null){
          this.timetablelist = response.data.data[0];
          this.periodCount    = response.data.data[0]['periodCount'];
          this.sortArray(response.data.data[0]);
        }
      }
          //console.log(this.timetablelist);
          //console.log(this.periodCount);
        });
      },

      sortArray(data)
      {
        for(var i=1 ; i <= data['periodCount'] ; i++)
        {
          if(data[i] == undefined)
          {
            this.timetablelist[i] = [];
            this.timetablelist[i][0] = "Free";
          }
        }
      },
    }
  }
</script>

<style>
  .hide-menu {
    display: none;
  }
</style>