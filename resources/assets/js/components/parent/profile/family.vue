<template>
  <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==3?'block' :'hidden']">
    <table class="profiletab-table w-full overflow-x-auto">
      <thead>
        <tr>
          <th>Children</th>
          <th>Class</th>
        </tr>
      </thead>
      <tbody v-if="Object.keys(users).length > 0">
        <tr v-for="user in users">
          <td>
            <div class="flex items-center">
              <img :src="user.avatar" class="rounded-full w-8 h-8">
              <a :href="url+'/admin/student/show/'+user.name" class="mx-2 text-blue-600 hover:text-blue-400">{{ user.fullname }}</a>
            </div>
          </td>
          <td>{{ user.class }}</td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr>
          <td colspan="2">
            <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { bus } from "../../../app";

  export default {
    props:['url','name'],
    data () {
      return {
        profile_tab:'',
        users:[],
        errors:[],
        success:null, 
      }
    },

    methods:{

      getData()
      {
        axios.get('/admin/parent/show/children/'+this.name).then(response => {
          this.users = response.data.data; 
          //console.log(this.users) 
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