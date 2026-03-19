<template>
  <div>
    <ul class="list-reset flex text-xs profile-tab flex-wrap">
      <li class="px-2 mx-3 py-2" v-bind:class="[{'active' : profile_tab === '1'}]" >
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('1')">Profile</a>
      </li>
      <li class="px-2 mx-3 py-2" v-bind:class="[{'active' : profile_tab === '2'}]" >
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('2')">Timeline</a>
      </li>
 
      <li class="px-2 mx-3 py-2" v-bind:class="[{'active' : profile_tab === '3'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('3')">My Children</a>
      </li>
 
      <li class="px-2 mx-3 py-2" v-bind:class="[{'active' : profile_tab === '4'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('4')">Feedbacks</a>
      </li>
 
      <li class="px-2 mx-3 py-2" v-bind:class="[{'active' : profile_tab === '5'}]">
        <a href="#" class="text-gray-700 font-medium"  @click="setProfileTab('5')">Notes</a>
      </li>
    </ul>
    <portal to="parent-profile">
      <myprofile :url="this.url" :name="this.name"></myprofile>
      <timeline :url="this.url" :name="this.name"></timeline>
      <family :url="this.url" :name="this.name"></family>
      <feedbacks :url="this.url" :name="this.name"></feedbacks>
      <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==5?'block' :'hidden']">
        <notes :url="this.url" :entity_id="this.entity_id" entity_name="user" :school_id="this.school_id"></notes>
      </div>    
    </portal>
  </div>
</template>

<script>
  import PortalVue from "portal-vue";
  import { bus } from "../../../app";
  import notes from '../../notes';
  import myprofile from './myprofile';
  import timeline from './timeline';
  import family from './family';
  import feedbacks from './feedbacks';
 
  export default {
    props:['url','name','entity_id','school_id'],
    data () {
      return {
        profile_tab:'1',     
      }
    },
    components: {
      myprofile,
      timeline,
      notes,
      family,
      feedbacks,
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