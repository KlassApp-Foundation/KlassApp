<template>
  <div>
    <ul class="list-reset flex text-xs profile-tab flex-wrap">
      <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : status === 'pending'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('pending')">Pending</a>
      </li>

      <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : status === 'approved'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('approved')">Approved</a>
      </li>

      <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : status === 'cancelled'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('cancelled')">Rejected</a>
      </li>
    </ul>

    <portal to="student_leave_list">
      <List :url="this.url"></List>
    </portal>
  </div>
</template>

<script>
  import PortalVue from "portal-vue";
  import { bus } from "../../../app";
  import List from './List';

  export default {
    props:['url'],
    data () {
      return {
        status:'pending',     
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
    }
  }
</script>