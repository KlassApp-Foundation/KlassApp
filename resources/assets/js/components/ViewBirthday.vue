<template>
  <div class="mb-2">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
    <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Today's Birthday (Students)</h1>
    <div>
      <div class="flex border h-24 overflow-hidden">
        <img :src="url+'/uploads/birthday.png'" class="w-2/5 border-r">

          <ul class="list-reset  px-3 w-3/5">
          <marquee direction="up" onmouseover="this.stop();" onmouseout="this.start();">
            <li class="flex items-center py-2" v-for="user in users">  
              <img :src="url+'/'+user['avatar']" class="w-8 h-8 rounded-full"> <!-- change -->
                <a :href="url+'/admin/student/show/'+user['name']" class="mx-2 text-gray-800 text-sm">{{ user['firstname'] }} 
                  <span class="text-xs text-gray-600 italic">({{ user['age'] }})</span> 
                </a>
            </li>
            </marquee>
            <li class="flex items-center py-2" v-if="this.users == ''"> 
              <p class="font-semibold text-sm" style="text-align: center">No Records Found</p>
            </li>
          </ul>
      </div>
      <div class="flex border py-1 text-center bg-gray-500" v-if="this.users != ''">
        <a :href="url+'/admin/dashboard/birthday'" class=" text-white  px-2  font-medium uppercase w-full text-xs py-1" title="Send Message">Send a Message</a> 
      </div>
    </div>  
  </div>
</template>

<script>

  export default {
    props:['url'],
  data () {
    return {
      users: [],
      errors:[],
      success:null,
    }
  },

  methods: 
  {
    getMember()
    {
      axios.get('/admin/dashboard/showBirthday').then(response => {
        this.users = response.data.data;
      });
    },
  },

  created()
  {
    this.getMember();
  }
}

</script>