<template>
  <div>
    <ul class="list-reset flex text-xs profile-tab flex-wrap">
      <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : status === 'draft'}]" v-if="this.role != 'principal'">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('draft')">Draft</a>
      </li>

      <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : status === 'pending'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('pending')" v-if="this.role == 'principal'">Waiting For Review</a>
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('pending')" v-else>Pending</a>
      </li>

      <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : status === 'approved'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('approved')">Approved</a>
      </li>

      <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : status === 'rejected'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('rejected')">Rejected</a>
      </li>
    </ul>

    <portal to="list_lessonplan">
      <List :url="this.url" :role="this.role"></List>
    </portal>
  </div>
</template>

<script>
  import PortalVue from "portal-vue";
  import { bus } from "../../app";
  import List from './List';

  export default {
    props:['url' , 'role'],
    data () {
      return {
        status:'',     
      }
    },
    components: {
      List,
    },

    methods:
    {
      setProfileTab(val)
      {
        this.status=val;
        bus.$emit("statusTab", this.status);
      }
    },

    created()
    {
      bus.$emit("statusTab", this.status);
       
      bus.$on("statusTab", data => {
        if(data!='')
        {
          this.status=data;                   
        }
      });
      if(this.role == 'principal')
      {
        this.status = 'pending';
      }
      else 
      {
        this.status = 'draft';
      }   
    }
  }
</script>