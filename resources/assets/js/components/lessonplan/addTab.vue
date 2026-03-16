<template>
  <div>
    <ul class="list-reset flex text-xs profile-tab flex-wrap">
      <li class="px-2 mx-1 lg:mx-3 py-2" v-bind:class="[{'active' : profile_tab === '1'}]">
        <!-- <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('1')">Step 1</a> -->
        <a href="#" class="text-gray-700 font-medium">Step 1</a>
      </li>

      <li class="px-2 mx-1 lg:mx-3 py-2" v-bind:class="[{'active' : profile_tab === '2'}]">
        <!-- <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('2')">Step 2</a> -->
        <a href="#" class="text-gray-700 font-medium">Step 2</a>
      </li>

      <li class="px-2 mx-1 lg:mx-3 py-2" v-bind:class="[{'active' : profile_tab === '3'}]">
        <!-- <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('3')">Step 3</a> -->
        <a href="#" class="text-gray-700 font-medium">Step 3</a>
      </li>

      <li class="px-2 mx-1 lg:mx-3 py-2" v-bind:class="[{'active' : profile_tab === '4'}]">
        <!-- <a href="#" class="text-gray-700 font-medium" @click="setProfileTab('4')">Step 4(optional)</a> -->
        <a href="#" class="text-gray-700 font-medium">Step 4</a>
      </li>
    </ul>

    <portal to="add_lessonplan" v-if="this.type == 'add'">
      <stepOne :url="this.url" :type="this.type"></stepOne>
      <stepTwo :url="this.url" :type="this.type"></stepTwo>
      <stepThree :url="this.url" :type="this.type"></stepThree>
      <stepFour :url="this.url" :type="this.type"></stepFour>
    </portal>

    <portal to="edit_lessonplan" v-if="this.type == 'edit'">
      <stepOne :url="this.url" :type="this.type" :id="this.id"></stepOne>
      <stepTwo :url="this.url" :type="this.type" :id="this.id"></stepTwo>
      <stepThree :url="this.url" :type="this.type" :id="this.id"></stepThree>
      <stepFour :url="this.url" :type="this.type" :id="this.id"></stepFour>
    </portal>
  </div>
</template>

<script>
  import PortalVue from "portal-vue";
  import { bus } from "../../app";
  import stepOne from './stepOne';
  import stepTwo from './stepTwo';
  import stepThree from './stepThree';
  import stepFour from './stepFour';

  export default {
    props:['url' , 'type' , 'id'],
    data () {
      return {
        profile_tab:'1',     
      }
    },
    components: {
      stepOne,
      stepTwo,
      stepThree,
      stepFour,
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