<template>
    <div class="mb-2">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{ this.success }}</div>
        <h1 class="text-sm uppercase text-gray-800 font-semibold mb-2">Today's Work Anniversary</h1>
        <div>
            <div class="flex border h-24 overflow-hidden">
                <img :src="url+'/uploads/work_anniversary.jpg'" class="w-1/5 md:w-1/5 lg:w-2/5 border-r">
                <ul class="list-reset  px-3 w-3/5">
                    <marquee direction="up" onmouseover="this.stop();" onmouseout="this.start();">
                        <li class="flex items-center py-2" v-for="user in users">  
                            <img :src="user['avatar']" class="w-8 h-8 rounded-full"> <!-- change -->
                            <a :href="url+'/'+mode+'/teacher/show/'+user['name']" class="mx-2 text-gray-800 text-sm" v-if="mode == 'admin'">{{ user['firstname'] }} 
                                <span class="text-xs text-gray-600 italic">({{ user['years'] }})</span> 
                            </a>
                            <a href="#" class="mx-2 text-gray-800 text-sm" v-else>{{ user['firstname'] }} 
                                <span class="text-xs text-gray-600 italic">({{ user['years'] }})</span> 
                            </a>
                        </li>
                    </marquee>
                    <li class="flex items-center py-2" v-if="Object.keys(this.users).length == 0"> 
                        <p class="font-semibold text-sm" style="text-align: center">No Records Found</p>
                    </li>
                </ul>
            </div>
            <div class="flex border py-1 text-center bg-gray-500" v-if="Object.keys(this.users).length > 0 && this.mode == 'admin'">
                <a :href="url+'/'+mode+'/dashboard/workAnniversary'" class="text-white px-2 font-medium uppercase w-full text-xs py-1" title="Send Message">Send a Message</a> 
            </div>
        </div>  
    </div>
</template>

<script>
    export default {
        props:['url' , 'mode'],
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
                axios.get('/'+this.mode+'/dashboard/showWorkAnniversary').then(response => {
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