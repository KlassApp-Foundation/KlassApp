<template>
    <div>
        <ul class="list-reset flex text-xs profile-tab flex-wrap">
            <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : task_flag === '1'}]">
                <a href="#" class="text-gray-700 font-medium" @click="setTab('1')">Today<span v-if="this.list['Today'] > 0">({{ this.list['Today'] }})</span></a>
            </li>

            <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : task_flag === '2'}]">
                <a href="#" class="text-gray-700 font-medium" @click="setTab('2')">Upcoming<span v-if="this.list['Upcoming'] > 0">({{ this.list['Upcoming'] }})</span></a>
            </li>  

            <li class="px-2 mx-1 py-1" v-bind:class="[{'active' : task_flag === '0'}]">
                <a href="#" class="text-gray-700 font-medium" @click="setTab('0')">Overdue<span v-if="this.list['Overdue'] > 0">({{ this.list['Overdue'] }})</span></a>
            </li>
        </ul>

        <portal to="list_task">
            <ListTask :url="this.url" :mode="this.mode"></ListTask>
        </portal>
    </div>
</template>

<script>
    import PortalVue from "portal-vue";
    import { bus } from "../../app";
    import ListTask from './ListTask';

    export default {
        props:['url','mode'],
        data () {
            return {
                task_flag:'1',
                list:[],
            }
        },
        components: {
            ListTask,
        },

        methods:
        {
            getData()
            {
                axios.get('/'+this.mode+'/dashboard/task/count').then(response => {
                    this.list = response.data;
                    //console.log(this.list)
                });
            },

            setTab(val)
            {
                this.task_flag=val;
                bus.$emit("task_flagTab", this.task_flag);
            }
        },

        created()
        { 
            this.getData();
            bus.$emit("task_flagTab", this.task_flag);
       
            bus.$on("task_flagTab", data => {
                if(data!='')
                {
                    this.task_flag=data;                   
                }
            });
        }
    }
</script>