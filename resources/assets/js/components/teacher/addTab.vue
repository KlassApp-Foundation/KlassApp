<template>
  <div>
    <ul class="list-reset flex text-xs profile-tab flex-wrap">
      <li class="px-2 mx-1 lg:mx-3 py-2" v-bind:class="[{'active' : profile_tab === '1'}]">
        <!-- <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('1')">Profile</a> -->
        <a href="#" class="text-gray-700 font-medium">Profile</a>
      </li>

      <li class="px-2 mx-1 lg:mx-3 py-2" v-bind:class="[{'active' : profile_tab === '2'}]">
        <!-- <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('2')">Educational Qualification</a> -->
        <a href="#" class="text-gray-700 font-medium">Educational Qualification</a>
      </li>

      <li class="px-2 mx-1 lg:mx-3 py-2" v-bind:class="[{'active' : profile_tab === '3'}]">
        <!-- <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('3')">Notes(Optional)</a> -->
        <a href="#" class="text-gray-700 font-medium">Notes(Optional)</a>
      </li>

      <li class="px-2 mx-1 lg:mx-3 py-2" v-bind:class="[{'active' : profile_tab === '4'}]">
        <!-- <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('4')">Address</a> -->
        <a href="#" class="text-gray-700 font-medium">Address</a>
      </li>
    </ul>

    <portal to="add_teacherprofile" v-if="this.type == 'add'">
      <create-teacher :url="this.url" :staff="this.staff"></create-teacher>
      <createQualification :url="this.url" :teacher_name="null" :type="this.type"></createQualification>
      <notes-tab :type="this.type"></notes-tab>
      <address-tab :type="this.type"></address-tab>
    </portal>

    <portal to="edit_teacherprofile" v-if="this.type == 'edit'">
      <edit-teacher :url="this.url" :staff="this.staff" :teacher_name="this.teacher_name"></edit-teacher>
      <createQualification :url="this.url" :teacher_name="this.teacher_name" :type="this.type"></createQualification>
      <notes-tab :type="this.type" :teacher_name="this.teacher_name"></notes-tab>
      <address-tab :type="this.type" :teacher_name="this.teacher_name"></address-tab>
    </portal>
  </div>
</template>

<script>
  import PortalVue from "portal-vue";
  import { bus } from "../../app";
  import createTeacher from './Create';
  import editTeacher from './Edit';
  import addressTab from './Address';
  import createQualification from './createQualification';
  import notes from './Notes';

  export default {
    props:['url' , 'school_id' , 'teacher_name' , 'type','staff'],
    data () {
      return {
        profile_tab:'1',     
      }
    },
    components: {
      createTeacher,
      editTeacher,
      createQualification,
      addressTab,
      notes,
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