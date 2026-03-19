<template>
    <div class="relative">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{ this.success }}</div>
        <div class="my-2">
            <div class="w-full flex flex-wrap items-center justify-between mb-2">
                <div class="flex items-center text-sm">
                    <div class="px-3" v-if="this.selectedTaskCount > 0">
                        {{ parseInt(this.selectedTaskCount) }} tasks selected
                    </div>
                </div> 
                <div class="relative flex items-center w-full lg:w-1/4 md:w-1/4 lg:justify-end mx-3 lg:mx-0 md:mx-0 my-2 lg:my-0 md:my-0" v-if="this.selectedTaskCount > 0">
                    <a href="#" class="btn btn-submit blue-bg text-white px-3 py-1 text-sm font-medium rounded whitespace-no-wrap" @click="changestatus()">Task Completed</a>
                </div>
            </div>
            <div class="flex flex-wrap">
                <div class="w-full my-1 relative" v-for="task in tasks"> <!-- xl:w-1/2 lg:w-1/2 md:w-1/2 -->
                    <div class="flex justify-between member-list">
                        <div class="flex items-center student_select">
                            <input class="w-5 h-5" type="checkbox" v-model="task_completed" :value="task.task_id" @click="selectedCount(task.task_id,$event)">
                            <label></label>
                        </div>
                        <div class="flex p-2 active w-full" :id="task.task_id">
                            <div class="px-2">
                                <h2 class="font-bold text-base text-gray-700">{{ task.title }} </h2>
                            </div>    
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="Object.keys(this.tasks).length == 0">
                <p class="text-sm" style="text-align: center;">No Records Round</p>
            </div>
        </div>
    </div>
</template>

<script>
    import { bus } from "../../app";
    export default {
        props:['url','mode'],
        data () {
            return {
                tasks:[],
                task_flag:1,
                search_query:'',
                task_completed:[],
                selectedTaskCount:0,
                errors:[],
                success:null,
            }
        },

        methods:
        {
            getData()
            {
                axios.get(this.url+'/'+this.mode+'/dashboard/tasklist/'+this.task_flag+'?q='+this.search_query).then(response => {
                    this.tasks  = response.data.data;
                    //console.log(this.tasks);    
                });
            },

            changestatus()
            {
                this.errors=[];
                this.success=null;

                axios.post('/admin/task/completed',{
                    task_completed:this.task_completed, 
                    selectedTaskCount:this.selectedTaskCount,
                }).then(response => {
                    this.success = response.data.success;
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            selectedCount(id,e) 
            { 
                if (e.target.checked) 
                {   
                    this.selectedTaskCount++;
                    this.task_completed.push(id);
                    $('#'+id).addClass('student_selected');
                }
                else
                {
                    this.selectedTaskCount--;
                    //this.task_completed.splice(id);
                    this.removeUser(id,this.task_completed);
                    $('#'+id).removeClass('student_selected');
                }
            },

            removeUser(item, arr)
            {
                for (var i=0 ; i < arr.length ; i++)
                {
                    if (arr[i]==item)
                    {
                        arr.splice(i,1); //this delete from the "i" index in the array to the "1" length
                        break;
                    }
                } 
            },
        },
  
        created()
        {   
            this.getData(); 
            bus.$on("task_flagTab", data => {
                if(data!='')
                {
                    this.task_flag=data;      
                    this.getData();             
                }
            });
            bus.$on("search_query", data => {
                if(data!='')
                {
                    this.search_query=data;      
                    this.getData();             
                }
            });
        }
    }
</script>