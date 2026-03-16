<template>
  <div class=" overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-1" v-bind:class="[this.profile_tab==5?'block' :'hidden']">
    <div class="">
      <div class="flex flex-wrap">
        <div class="w-full lg:w-1/4 md:w-1/2 my-2 px-2 relative " v-for="student in students">
          <div class="flex p-2 member-list active" :id="student['id']">
            <img :src="student['avatar']" class="w-16 h-16">
            <div class="px-2">
              <h2 class="font-bold text-base text-gray-700">
                <a :href="url+'/'+mode+'/student/show/'+student['name']" >{{ student.fullname }}</a>
              </h2>
              <p>{{ student.class }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { bus } from "../../../app";

export default {
  props:['url','id','mode'],
  data () {
    return {
      profile_tab:'',
      students:[],
      errors:[],
      success:null,
    }
  },

  methods:{
    getData()
    {
      axios.get('/'+this.mode+'/standardLink/show/students/'+this.id).then(response => {
        this.students = response.data.data;
        //console.log(this.students);   
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