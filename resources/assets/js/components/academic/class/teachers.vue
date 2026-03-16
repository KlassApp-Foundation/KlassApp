<template>
  <div class=" overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==4?'block' :'hidden']">
    <div class="flex flex-wrap custom-table mx-3 my-3" v-if="Object.keys(teachers).length>0">
      <table class="w-full">
        <thead class="bg-grey-light">
          <tr class="border-b">
            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Teacher </th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Subject </th>
          </tr>
        </thead>   
        <tbody v-if="this.teachers != ''">
          <tr class="border-b" v-for="teacher in teachers">
            <td class=" py-3 px-2">
            <div class=" flex items-center">
              <img :src="teacher.teacher_avatar" class="w-10 h-10">
              <p class="font-semibold text-xs mx-2">
                <a :href="url+'/admin/teacher/show/'+teacher.teacher_name" v-if="mode == 'admin'">{{ teacher.teacher_fullname }}</a>
                <a href="#" v-else>{{ teacher.teacher_fullname }}</a>
              </p>
              </div>
            </td>
           <td class="py-3 px-2">
            <p class="font-semibold text-xs">{{ teacher.subject_name }}</p>
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
  props:['url','id','mode'],
  data () {
    return {
      profile_tab:'',
      teachers:[],
      errors:[],
      success:null,
    }
  },

  methods:{
    getData()
    {
      axios.get('/'+this.mode+'/standardLink/show/teachers/'+this.id).then(response => {
        this.teachers = response.data.data;
        //console.log(this.teachers);   
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