<template>
  <div class=" overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==8?'block' :'hidden']">
    <ul class="list-reset flex text-sm profile-tab flex-wrap">
      <li class="px-2 mx-1 lg:mx-4 md:mx-4 py-2 lg:py-3 md:py-3" v-bind:class="[{'active' : type === 'monthly'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setType('monthly')">Monthly Record</a>
      </li>
      <li class="px-2 mx-1 lg:mx-4 md:mx-4 py-2 lg:py-3 md:py-3" v-bind:class="[{'active' : type === 'staff'}]">
        <a href="#" class="text-gray-700 font-medium" @click="setType('staff')">Staff Record</a>
      </li>
    </ul>
    <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.type=='monthly'?'block' :'hidden']">
      <monthly :url="this.url" :name="this.name" ></monthly>
    </div>
    <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.type=='staff'?'block' :'hidden']">
      <staff :url="this.url" :name="this.name" ></staff>
    </div>
  </div>
</template>

<script>
  import { bus } from "../../../app";
  import monthly from "./monthlyRecord";
  import staff from "./staffRecord";

  export default {
    components:{ monthly, staff },
    props:['url' , 'name' , 'mode'],
    data () {
      return {
        profile_tab:'',
        type:'monthly',
        errors:[],
        success:null,
      }
    },

    methods:
    {
      setType(val)
      {
        this.type = val;
        bus.$emit("type", this.type);
      },
    },
    
    created()
    {   
      bus.$emit("type", this.type);
      bus.$on("dataProfileTab", data => {
        if(data!='')
        {
          this.profile_tab=data;                   
        }
      });   
    }
  }
</script>