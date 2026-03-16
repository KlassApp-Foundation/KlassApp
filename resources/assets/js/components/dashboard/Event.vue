<template>
<div class="">
	<div class="container mx-auto">
		<div class="flex flex-col lg:flex-row md:flex-row">
		<div class="w-full">
		
			<!-- <h2 class="text-3xl py-3 text-gray-700 border-b py-2 border-gray-300 font-bold">Upcoming Events</h2> -->

			
			<ul class="list-reset" v-for="event in events">
				<li class="mt-2 bg-light  border-l-4 border-teal-400 rounded">
				<div class="flex items-center">
				<div class="w-1/4 py-1  text-center leading-tight">
				<p class="text-gray-600 text-3xl font-bold ">{{ event['date'] }}</p>
				<p class="text-xs text-gray-600 uppercase">{{ event['month'] }}</p>
				</div>
				<div class="w-3/4 ml-2 leading-relaxed">
				<a :href="url+'/admin/events/show/details/'+event['id']" class="font-semibold text-sm py-1">{{event['title']}}</a>
				<p class="text-xs">{{event['category']}}</p>
				
				<!-- <p class="text-sm text-gray-700 flex items-center"><img :src="url+'/uploads/icons/event_calendar.svg'" class="w-3 h-3"><span class="mx-2">{{event['start_date']}}</span></p> --> <!-- change -->
				<!-- <p class="text-xs text-purple-500 flex items-center">{{event['location']}}</p> -->
				
				</div>
				</div>
				</li>
				
			</ul>
			
			<!-- <div class="mt-5 mb-3">
				<a href="{{url('/login')}}" class="text-xs font-semibold px-3 text-white sermon-btn cursor-pointer rounded-full py-1">View all events</a>
			</div> -->
			
			
		</div>
	</div>
	</div>
</div>
</template>

<script>


  export default {
      props:['url'],
      data () {
        return {
         
          events:[],
          errors:[],
          success:null, 
          
        }
      },

       methods:{

        getData()
          {
            axios.get('/admin/dashboard/event').then(response => {
              this.events = response.data.data;
              
              console.log(this.events);   
            });
          }

       },
  
      created()
      {   
        //alert(this.event);
        this.getData();               
      }
  
 
  
  }

  
</script>