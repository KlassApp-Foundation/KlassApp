<template>
    <div class="">
        <div class="flex flex-col lg:flex-row md:flex-row">
            <div class="w-full lg:w-full my-3 lg:my-0 md:my-0 px-1 dashboard-task">
                <div class="bg-white px-3 py-2 my-3">
                    <div class="mx-2 py-1 pb-3 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center justify-between">
                        <div>
                            <h1 class="text-gray-800 font-semibold text-lg">Tasks</h1>
                        </div>
                 
                        <div class="flex items-center">
                            <div class="flex items-center">
                                <div class="relative">
                                    <input type="text" name="search" v-model="search" class="px-2 py-1 border text-xs" placeholder="Search">
                                    <a href="#" class="no-underline text-white px-4 mx-1 py-1 absolute right-0 focus:outline-none" @click="searchTask()">
                                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30.239px" height="30.239px" viewBox="0 0 30.239 30.239" xml:space="preserve" class="w-4 h-4 fill-current text-gray-600 absolute right-0 mt-1 pt-1 mx-1 top-0"><g><path d="M20.194,3.46c-4.613-4.613-12.121-4.613-16.734,0c-4.612,4.614-4.612,12.121,0,16.735 c4.108,4.107,10.506,4.547,15.116,1.34c0.097,0.459,0.319,0.897,0.676,1.254l6.718,6.718c0.979,0.977,2.561,0.977,3.535,0 c0.978-0.978,0.978-2.56,0-3.535l-6.718-6.72c-0.355-0.354-0.794-0.577-1.253-0.674C24.743,13.967,24.303,7.57,20.194,3.46z M18.073,18.074c-3.444,3.444-9.049,3.444-12.492,0c-3.442-3.444-3.442-9.048,0-12.492c3.443-3.443,9.048-3.443,12.492,0 C21.517,9.026,21.517,14.63,18.073,18.074z"></path></g></svg>
                                    </a>
                                </div>
                                    <a href="#" class="text-xs border bg-gray-400 text-grey-darkest py-1 px-4" @click="clearAll()">Reset</a>
                            </div>
                            <div>
                                <!-- <add-task-popup mode="admin"></add-task-popup> -->
                                <div class="flex flex-wrap lg:flex-row justify-between items-center">
                                    <div class="relative flex items-center justify-end">
                                        <div class="flex items-center">
                                            <a :href="this.url+'/'+this.mode+'/task/add'" class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center cursor-pointer">
                                                <svg class="w-5 h-5 fill-current text-green-100" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M256,0C114.833,0,0,114.833,0,256s114.833,256,256,256s256-114.853,256-256S397.167,0,256,0z M256,472.341 c-119.275,0-216.341-97.046-216.341-216.341S136.725,39.659,256,39.659S472.341,136.705,472.341,256S375.295,472.341,256,472.341z"/></g></g><g><g><path d="M355.148,234.386H275.83v-79.318c0-10.946-8.864-19.83-19.83-19.83s-19.83,8.884-19.83,19.83v79.318h-79.318 c-10.966,0-19.83,8.884-19.83,19.83s8.864,19.83,19.83,19.83h79.318v79.318c0,10.946,8.864,19.83,19.83,19.83 s19.83-8.884,19.83-19.83v-79.318h79.318c10.966,0,19.83-8.884,19.83-19.83S366.114,234.386,355.148,234.386z"/></g></g></svg>
                                            </a> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- list tab -->
                        <list-task-tab :url="this.url" :mode="this.mode"></list-task-tab>
                        <portal-target name="list_task"></portal-target>
                    <!-- list tab -->
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { bus } from "../../app";
    export default{
        props:['url' , 'mode'],
        data(){
            return{
                search:'',
                success:null,
                errors:[],
            }
        },

        methods:
        {
            searchTask()
            {
                bus.$emit('search_query',this.search);
            },

            clearAll()
            {
                window.location.reload();
            },
        },

        created()
        {
            //
        }
    }
</script>