<template>
    <div class="">
        <!-- <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div> -->

        <div class="flex flex-wrap" v-if="Object.keys(this.feegrouplist).length > 0">
            <div class="w-full lg:w-1/2 md:w-1/2 my-2 relative" v-for="list in feegrouplist">
                <div class="flex items-center">
                    
                    <input class="w-5 h-5" type="checkbox" v-model="feegroup" :value="list.id" v-on:change="showDetails()">
                    <span class="mx-2">{{ list.name }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-row justify-between custom-table overflow-x-auto pt-3">
            <table class="w-full">
                <thead class="bg-grey-light">
                    <tr class="border-b">
                        <th class="text-left text-sm px-2 py-2 text-grey-darker">Class</th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker">Fees Title</th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker">Unpaid Count</th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker">Pending Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-grey-light" v-for="list in feelist">
                    <tr class="border-b" v-for="(fee,key) in list">
                        <td class="py-3 px-2">
                            <p v-if="fee.standardLink_id != '--'">{{ fee.standardLink_id }}</p>
                            <p v-else>--</p>
                        </td>
                        <td class="py-3 px-2">   
                            <a :href="url+'/'+mode+'/dashboard/unpaidfees/'+fee.id" target="_blank" class="" title="Show">{{ fee.name }}</a>
                        </td>
                        <td class="py-3 px-2">{{ fee.unpaid_count }}</td>
                        <td class="py-3 px-2">Rs . {{ fee.unpaid_count * fee.amount }}</td>
                    </tr>
                    <tr class="border-b">
                        <td></td>
                        <td></td>
                        <td class="py-3 px-2">Total</td>
                        <td class="py-3 px-2">Rs . {{ list[list.length-1]['final_amount'] }}</td>
                    </tr>
                </tbody>
                <tbody class="bg-grey-light" v-if="Object.keys(this.feelist).length == 0">
                    <tr class="border-t-2 border-b-2">    
                        <td colspan="9" class="py-3 px-2">
                            <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                        </td>
                    </tr>
                </tbody>
            </table>      
        </div>
    </div>
</template>

<script>
    export default {
        props:['url','mode'],
        data () {
            return {
                feegrouplist:[],
                feelist:[],
                isChecked:'',
                feegroup:[],
                lastCount:'',
                errors:[],
                success:null,
            }
        },

        methods:
        {
            getData()
            {
                axios.get('/'+this.mode+'/dashboard/structuralList').then(response => {
                    this.feegrouplist  = response.data.data;
                    //console.log(this.feegrouplist); 
                });
            },

            showDetails()
            {
                axios.post('/admin/dashboard/structuralList',{
                    feegroup:this.feegroup,
                }).then(response => {            
                    //console.log(response.data.data);
                    this.feelist = response.data.data; 
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },
        },
  
        created()
        {   
            this.getData();
        }
    }
</script>