<template>
  <div>
    <ul class="list-reset flex text-sm profile-tab flex-wrap">
      <li class="px-2 mx-1 py-2" v-bind:class="[{'active' : profile_tab === '1'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('1')">Home</a>
      </li>
      <!-- <li class="px-2 mx-1 py-2" v-bind:class="[{'active' : profile_tab === '2'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('2')">Gallery</a>
      </li> -->
    </ul>
    <portal to="tab">
      <div class="overflow-x-auto lg:overflow-x-auto md:overflow-x-auto " v-bind:class="[this.profile_tab==1?'block' :'hidden']">
        <post-list :url="this.url" :hidecolumns="false" :entity_id="this.entity_id" :entity_name="this.entity_name" :mode="this.mode" id="null" auth_id="null"></post-list>
      </div>
    </portal>
  </div>
</template>

<script>
  import PortalVue from "portal-vue";
  import { bus } from "../../../../app";
 
  export default {
    props:['url' , 'id' , 'entity_id' , 'entity_name' , 'mode'],
    data () {
      return {
        profile_tab:'1',     
      }
    },
    components: {
    },
    methods:
    {
      setProfileTab(val)
      {
        this.profile_tab=val;
        bus.$emit("dataProfileTab", this.profile_tab);
      }
    },

    created()
    {
      bus.$emit("dataProfileTab", this.profile_tab);
       
      bus.$on("dataProfileTab", data => {
        if(data!='')
        {
          this.profile_tab=data;                     
        }
      });   
    }
  }
</script>