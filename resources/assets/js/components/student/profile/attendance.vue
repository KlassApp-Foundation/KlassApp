<template>
  <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==9?'block' :'hidden']">
    <div class="flex flex-wrap custom-table mx-3 my-3">
      <table class="w-full overflow-x-auto">
        <thead class="bg-grey-light">
          <tr class="border-b">
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Date</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Session</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Reason For Absent</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Remarks</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Recorded By</th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker">Recorded On</th>
          </tr>
        </thead>
        <tbody v-if="Object.keys(attendances).length > 0">
          <tr class="border-b" v-for="attendance in attendances">
            <td class="py-3 px-2"><p class="font-semibold text-xs">{{ attendance.date }}</p></td>
            <td class="py-3 px-2"><p class="font-semibold text-xs">{{ attendance.session }}</p></td>
            <td class="py-3 px-2"><p class="font-semibold text-xs">{{ attendance.reason }}</p></td>
            <td class="py-3 px-2"><p class="font-semibold text-xs">{{ attendance.remarks }}</p></td>
            <td class="py-3 px-2">
              <p class="font-semibold text-xs">
                <a :href="url+'/admin/teacher/show/'+attendance.recorded_by_name">{{ attendance.recorded_by }}</a>
              </p>
            </td>
            <td class="py-3 px-2"><p class="font-semibold text-xs">{{ attendance.recorded_on }}</p></td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr>
            <td colspan="6">
              <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
  import { bus } from "../../../app";
  export default {
    props:['url','name','mode'],
    data () {
      return {
        attendances:[],
        profile_tab:'',
        errors:[],
        success:null,     
      }
    },
    methods:
    {
      getData()
      {
        axios.get('/'+this.mode+'/student/show/attendance/'+this.name).then(response => {
          this.attendances = response.data.data;
          //console.log(this.attendances)
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