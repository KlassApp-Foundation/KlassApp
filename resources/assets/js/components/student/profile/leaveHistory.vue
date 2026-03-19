<template>
    <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto" v-bind:class="[this.profile_tab==12?'block' :'hidden']">
        <h3 class="font-semibold text-base text-gray-800 capitalize mx-1 my-2">Leave History</h3>
        <div class="flex flex-wrap custom-table  my-3">
            <table class="w-full">
                <thead class="bg-grey-light">
                    <tr class="border-b">
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" width="20%"> From </th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" width="20%"> To </th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" width="10%"> Reason </th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" width="40%"> Remarks </th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" width="10%"> Approved By </th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" width="10%"> Approved On </th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" width="20%"> Comments </th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" width="10%"> Status </th>
                    </tr>
                </thead>   
                <tbody v-if="Object.keys(this.leaves).length > 0">
                    <tr class="border-b" v-for="leave in leaves">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">{{ leave.from_date }}</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">{{ leave.to_date }}</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">{{ leave.reason }}</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">{{ leave.remarks }}</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs" v-if="leave.approved_by != null">{{ leave.approved_by }}</p>
                            <p v-else>--</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs" v-if="leave.approved_on != null">{{ leave.approved_on }}</p>
                            <p v-else>--</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs" v-if="leave.comments != null">{{ leave.comments }}</p>
                            <p v-else>--</p>
                        </td>
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">{{ leave.status }}</p>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr class="border-b">
                        <td colspan="9">
                            <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="this.page_count>1">
                <paginate v-model="page" :page-count="this.page_count" :page-range="3" :margin-pages="1" :click-handler="getData" :prev-text="'<'" :next-text="'>'" :container-class="'pagination'" :page-class="'page-item'" :prev-link-class="'prev'" :next-link-class="'next'"></paginate>
            </div>
        </div>
    </div>
</template>

<script>
    import { bus } from "../../../app";
    export default {
        props:['url' , 'name'],
        data () {
            return {
                profile_tab:'',  
                leaves:[], 
                errors:[],
                success:null, 
                total: 0,
                page: 1,
                page_count: 0,  
            }
        },

        methods:
        {
            getData(page=1)
            {
                axios.get('/admin/teacher/show/leave/'+this.name+'?page='+page).then(response => {
                    this.leaves = response.data.data;
                    this.page_count = response.data.meta.last_page;
                    this.total = response.data.meta.total;
                    //console.log(this.leaves);    
                });
            }
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