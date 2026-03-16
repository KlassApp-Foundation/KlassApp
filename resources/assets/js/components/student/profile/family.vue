<template>
  <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==3?'block' :'hidden']">
    <div class="custom-table my-3">
      <table class="w-full overflow-x-auto"> <!-- profiletab-table -->
        <thead>
          <tr>
            <th>Family Members</th>
            <th>Relation</th>
            <th>Contact Number</th>
            <th>Status</th>
            <th>Email</th>
            <th>Occupation</th>
          </tr>
        </thead>
        <tbody v-if="Object.keys(users).length > 0">
          <tr v-for="user in users">
            <td>
              <div class="flex items-center">
                <a :href="url+'/admin/parent/show/'+user.name" class="mx-2 text-blue-600 hover:text-blue-400">{{ user['fullname'] }}</a>
              </div>
            </td>
            <td>{{ user['relation'] }}</td>
            <td>{{ user['mobile_no'] }}</td>
            <td>
              <span v-if="user['status']=='active'" class="bg-green-500 rounded px-1 text-xs text-white">Active</span>
              <span v-else="" class="bg-red-500 rounded px-1 text-xs text-white">InActive</span>
            </td>
            <td>
              <a href="#" class="text-blue-600 hover:text-blue-400">{{ user['email'] }}</a>
            </td>
            <td>
              <a href="#" class="text-blue-600 hover:text-blue-400">{{ user['profession'] }} 
                <span v-if="user['sub_occupation'] != '' "> ({{ user['sub_occupation'] }})</span>
              </a>
            </td>
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
        profile_tab:'',
        users:[],
        errors:[],
        success:null, 
      }
    },

    methods:
    {
      getData()
      {
        axios.get('/'+this.mode+'/student/show/relations/'+this.name).then(response => {
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