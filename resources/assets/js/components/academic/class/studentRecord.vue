<template>
	<div class="overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.type=='student'?'block' :'hidden']">
        <div class="custom-table mx-5 py-5">
            <table class="w-full">
                <thead class="bg-grey-light">
                    <tr class="border-b">
                        <th class="text-left text-sm px-2 py-2 text-grey-darker"> Student </th>
                        <th class="text-left text-sm px-2 py-2 text-grey-darker" :colspan="month_count" style="text-align: center;"> Months </th>
                    </tr>
                </thead>   
                <tbody v-if="Object.keys(this.students).length > 0">
                    <tr class="border-b" v-for="(student,key) in students.students">
                        <td class="py-3 px-2" v-for="(value,index) in student">
                            <p class="font-semibold text-xs" v-if="index != 'FullName'">{{ index }} : {{ value }} days</p>
                            <p class="font-semibold text-xs" v-if="index == 'FullName'">
                                <a :href="url+'/admin/student/show/'+key">{{ value }}</a>
                            </p>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr class="border-b">
                        <td colspan="2">
                            <p class="font-semibold text-sm" style="text-align: center">No Records Found</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
	</div>
</template>

<script>
    import { bus } from "../../../app";
    export default {
        props:['url','id','mode'],
        data () {
            return {
                students:[],
                type:'',
                month_count:0,
                errors:[],
                success:null,
            }
        },

        methods:
        {
            getStudent()
            {
                axios.get('/'+this.mode+'/standardLink/show/student/attendances/'+this.id).then(response => {
                    this.students = response.data;
                    this.month_count = Object.keys(response.data.months).length;
                    //console.log(this.students);
                    //console.log(this.month_count);
                });
            },
        },
    
        created()
        {   
            this.getStudent();
            bus.$on("type", data => {
                if(data!='')
                {
                    this.type=data;                   
                }
            });   
        }
    }
</script>