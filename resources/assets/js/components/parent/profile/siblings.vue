<template>
  <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==4?'block' :'hidden']">
    <table class="profiletab-table w-full overflow-x-auto">
      <thead>
        <tr>
          <th>Name</th>
          <th>Relation</th>
          <th>Date Of Birth</th>
          <th>Class</th>
        </tr>
      </thead>
      <tbody v-if="this.users != null">
        <tr v-for="user in users">
          <td>
            <div class="flex items-center">
              <a href="#" class="mx-2 text-blue-600 hover:text-blue-400">{{ user['fullname'] }}</a>
            </div>
          </td>
          <td>{{ user['relation'] }}</td>
          <td>{{ user['date_of_birth'] }}</td>
          <td>{{ user['standard_section'] }}</td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr>
          <td colspan="4">
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
        axios.get('/admin/student/show/siblings/'+this.name).then(response => {
          this.users = response.data.data[0]; 
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