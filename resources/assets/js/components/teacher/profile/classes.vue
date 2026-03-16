<template>
  <div class=" overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==3?'block' :'hidden']">
    <div class="flex items-start" v-if="Object.keys(class_teacher).length>0">
      <div class="w-full lg:w-3/5 mx-4 border rounded px-2 py-2">
        <div class="flex justify-between items-center">
          <div class="flex items-center cursor-pointer">
            <h4 class="font-semibold text-sm text-gray-800">Class Teacher : </h4>
            <p class="text-xs text-gray-700 flex items-center">
              {{ class_teacher['standard'] }} - 
              <span class="mx-1">{{ class_teacher['section'] }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="flex flex-wrap custom-table mx-3 my-3" v-if="Object.keys(teachers).length>0">
      <table class="w-full">
        <thead class="bg-grey-light">
          <tr class="border-b">
            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Class </th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Section </th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Subject </th>
          </tr>
        </thead>   
        <tbody v-if="this.teachers != ''">
          <tr class="border-b" v-for="teacher in teachers">
            <td class="py-3 px-2">
              <p class="font-semibold text-xs">{{ teacher.standard }}</p>
            </td>
            <td class="py-3 px-2">
              <p class="font-semibold text-xs">{{ teacher.section }}</p>
            </td>
           <td class="py-3 px-2">
            <p class="font-semibold text-xs">{{ teacher.subject }}</p>
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
</template>

<script>
import { bus } from "../../../app";

export default {
  props:['url','name'],
  data () {
    return {
      profile_tab:'',
      teachers:[],
      class_teacher:[],
      errors:[],
      success:null,
    }
  },

  methods:{
    getData()
    {
      axios.get('/admin/teacher/show/classes/'+this.name).then(response => {
        this.teachers = response.data.data;
        //console.log(this.teachers);   
      });
    },

    getClassTeacherDetails()
    {
      axios.get('/admin/teacher/show/classteacher/'+this.name).then(response => {
        this.class_teacher = response.data;
        //console.log(this.class_teacher)
      })
    }
  },
  
  created()
  {   
    this.getData();
    this.getClassTeacherDetails();
    
    bus.$on("dataProfileTab", data => {
      if(data!='')
      {
        this.profile_tab=data;                   
      }
    });   
  }
}
</script>