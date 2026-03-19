<template>
    <div class="">
        <div>
            <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

            <div class="flex">
                <table class="w-3/4">
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">Teacher Name</p>
                        </td>
                        <td class="py-3 px-10">
                            <p class="text-xs">{{ leaves.teacher_name }}</p>
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">From Date</p>
                        </td>
                        <td class="py-3 px-10">
                            <p class="text-xs">{{ leaves.from_date }}</p>
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">To Date</p>
                        </td>
                        <td class="py-3 px-10">
                            <p class="text-xs">{{ leaves.to_date }}</p>
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">Reason</p>
                        </td>
                        <td class="py-3 px-10">
                            <p class="text-xs">{{ leaves.reason_id }}</p>
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">Remarks</p>
                        </td>
                        <td class="py-3 px-10">
                            <p class="text-xs" v-if="leaves.remarks != null">{{ leaves.remarks }}</p>
                            <p class="text-xs" v-else>--</p>
                        </td>
                    </tr>
                   <!--  <tr class="border-b">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">Leave Days</p>
                        </td>
                        <td class="py-3 px-10">
                            <p class="text-xs">{{ leaves.leave_days }}</p>
                        </td>
                    </tr> -->
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">Session</p>
                        </td>
                        <td class="py-3 px-10">
                            <p class="text-xs">{{ leaves.session }}</p>
                        </td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-3 px-2">
                            <p class="font-semibold text-xs">Leave Type</p>
                        </td>
                        <td class="py-3 px-10">
                            <p class="text-xs">{{ leaves.leave_type_id }}</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="flex">
                <div class="tw-form-group w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="comments" class="tw-form-label">Comments</label>
                        </div>
                        <div class="mb-2">
                            <textarea type="text" name="comments" id="comments" v-model="comments" class="tw-form-control w-full" rows="3" placeholder="Enter Comments"></textarea>
                        </div>
                        <span v-if="errors.comments" class="text-red-500 text-xs font-semibold">{{errors.comments[0]}}</span>
                    </div>
                </div>
            </div>
        
            <div class="my-6">
                <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitApprove()" v-if="this.status == 'approve'">Submit</a>
                <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitReject()" v-if="this.status == 'reject'">Submit</a>
                <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="resetForm()">Reset</a>    
            </div>
        </div>
    </div>
</template>

<script>
    import datetime from 'vuejs-datetimepicker';
    export default {
        components: {
            datetime,
        },
        props : ['url' , 'id' , 'status'],

        data(){
            return{
                leaves:[],
                comments:'',
                errors:[],
                success:null,
            }
        },
        
        methods:
        {
            getList()
            {
                axios.get('/teacher/leave/approve/list/'+this.id).then(response => {
                    this.leaves = response.data;
                    //console.log(this.leaves)
                });
            },

            resetForm()
            {
                this.comments = '';
            },

            submitApprove()
            {
                alert('Approve')
                this.errors=[];
                this.success=null; 

                let formData=new FormData();
                
                formData.append('comments',this.comments);  

                axios.post('/teacher/leave/approve/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });     
            },

            submitReject()
            {
                alert('Reject')
                this.errors=[];
                this.success=null; 

                let formData=new FormData();
                
                formData.append('comments',this.comments); 

                axios.post('/teacher/leave/reject/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });           
            },
        },

        created()
        {
            this.getList();
        }
    }
</script>