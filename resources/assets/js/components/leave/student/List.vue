<template>
    <div class="relative">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
        <div class="">
            <div class="flex flex-wrap custom-table overflow-auto">
                <table class="w-full">
                    <thead class="bg-grey-light">
                        <tr class="border-b">
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">Student</th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">From</th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">To</th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">Reason</th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">Remarks</th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">Approved By</th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">Approved On</th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">Comments</th>
                            <th class="text-left text-sm px-2 py-2 text-grey-darker">Actions</th>
                        </tr>
                    </thead>   
                    <tbody v-if="Object.keys(this.leaves).length > 0">
                        <tr class="border-b" v-for="(leave,index) in leaves">
                            <td class="py-3 px-2">
                                <p class="text-xs">{{ leave.name }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="text-xs">{{ leave.from }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="text-xs">{{ leave.to }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="text-xs">{{ leave.reason }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="text-xs" v-if="leave.remarks != null">{{ leave.remarks }}</p>
                                <p class="text-xs" v-else>--</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="text-xs" v-if="leave.approved_by != null">{{ leave.approved_by }}</p>
                                <p class="text-xs" v-else>--</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="text-xs" v-if="leave.approved_on != null">{{ leave.approved_on }}</p>
                                <p class="text-xs" v-else>--</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="text-xs" v-if="leave.comments != null">{{ leave.comments }}</p>
                                <p class="text-xs" v-else>--</p>
                            </td>
                            <td class="py-3 px-2">
                                <div class="flex items-center">
                                    <a href="#" class="capitalize text-white rounded px-1 py-1 font-medium activate" @click="approveleave(leave.id)" v-if="leave.status == 'pending'">
                                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-5 h-5 fill-current text-green-600 mx-auto"><g><g><path d="M383.841,171.838c-7.881-8.31-21.02-8.676-29.343-0.775L221.987,296.732l-63.204-64.893 c-8.005-8.213-21.13-8.393-29.35-0.387c-8.213,7.998-8.386,21.137-0.388,29.35l77.492,79.561 c4.061,4.172,9.458,6.275,14.869,6.275c5.134,0,10.268-1.896,14.288-5.694l147.373-139.762 C391.383,193.294,391.735,180.155,383.841,171.838z"></path></g></g><g><g><path d="M256,0C114.84,0,0,114.84,0,256s114.84,256,256,256s256-114.84,256-256S397.16,0,256,0z M256,470.487c-118.265,0-214.487-96.214-214.487-214.487c0-118.265,96.221-214.487,214.487-214.487c118.272,0,214.487,96.221,214.487,214.487C470.487,374.272,374.272,470.487,256,470.487z"></path></g></g></svg>
                                    </a>

                                    <a href="#" class="capitalize text-white  rounded px-1 py-1 font-medium activate" @click="rejectleave(leave.id)" v-if="leave.status == 'pending'">
                                        <svg height="512pt" viewBox="0 0 512 512" width="512pt" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mx-auto text-red-600 fill-current"><path d="m256 512c-141.160156 0-256-114.839844-256-256s114.839844-256 256-256 256 114.839844 256 256-114.839844 256-256 256zm0-475.429688c-120.992188 0-219.429688 98.4375-219.429688 219.429688s98.4375 219.429688 219.429688 219.429688 219.429688-98.4375 219.429688-219.429688-98.4375-219.429688-219.429688-219.429688zm0 0"></path><path d="m347.429688 365.714844c-4.679688 0-9.359376-1.785156-12.929688-5.359375l-182.855469-182.855469c-7.144531-7.144531-7.144531-18.714844 0-25.855469 7.140625-7.140625 18.714844-7.144531 25.855469 0l182.855469 182.855469c7.144531 7.144531 7.144531 18.714844 0 25.855469-3.570313 3.574219-8.246094 5.359375-12.925781 5.359375zm0 0"></path><path d="m164.570312 365.714844c-4.679687 0-9.355468-1.785156-12.925781-5.359375-7.144531-7.140625-7.144531-18.714844 0-25.855469l182.855469-182.855469c7.144531-7.144531 18.714844-7.144531 25.855469 0 7.140625 7.140625 7.144531 18.714844 0 25.855469l-182.855469 182.855469c-3.570312 3.574219-8.25 5.359375-12.929688 5.359375zm0 0"></path></svg>
                                    </a>

                                    <a href="#" v-if="leave.status == 'approved'">
                                        <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-4 h-4 fill-current text-green-600 mx-auto"><g><g><path d="M383.841,171.838c-7.881-8.31-21.02-8.676-29.343-0.775L221.987,296.732l-63.204-64.893 c-8.005-8.213-21.13-8.393-29.35-0.387c-8.213,7.998-8.386,21.137-0.388,29.35l77.492,79.561 c4.061,4.172,9.458,6.275,14.869,6.275c5.134,0,10.268-1.896,14.288-5.694l147.373-139.762 C391.383,193.294,391.735,180.155,383.841,171.838z"></path></g></g> <g><g><path d="M256,0C114.84,0,0,114.84,0,256s114.84,256,256,256s256-114.84,256-256S397.16,0,256,0z M256,470.487 c-118.265,0-214.487-96.214-214.487-214.487c0-118.265,96.221-214.487,214.487-214.487c118.272,0,214.487,96.221,214.487,214.487 C470.487,374.272,374.272,470.487,256,470.487z"></path></g></g></svg>
                                    </a>

                                    <a href="#" v-if="leave.status == 'cancelled'">
                                        <svg height="512pt" viewBox="0 0 512 512" width="512pt" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mx-auto text-red-600 fill-current"><path d="m256 512c-141.160156 0-256-114.839844-256-256s114.839844-256 256-256 256 114.839844 256 256-114.839844 256-256 256zm0-475.429688c-120.992188 0-219.429688 98.4375-219.429688 219.429688s98.4375 219.429688 219.429688 219.429688 219.429688-98.4375 219.429688-219.429688-98.4375-219.429688-219.429688-219.429688zm0 0"></path><path d="m347.429688 365.714844c-4.679688 0-9.359376-1.785156-12.929688-5.359375l-182.855469-182.855469c-7.144531-7.144531-7.144531-18.714844 0-25.855469 7.140625-7.140625 18.714844-7.144531 25.855469 0l182.855469 182.855469c7.144531 7.144531 7.144531 18.714844 0 25.855469-3.570313 3.574219-8.246094 5.359375-12.925781 5.359375zm0 0"></path><path d="m164.570312 365.714844c-4.679687 0-9.355468-1.785156-12.925781-5.359375-7.144531-7.140625-7.144531-18.714844 0-25.855469l182.855469-182.855469c7.144531-7.144531 18.714844-7.144531 25.855469 0 7.140625 7.140625 7.144531 18.714844 0 25.855469l-182.855469 182.855469c-3.570312 3.574219-8.25 5.359375-12.929688 5.359375zm0 0"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-if="this.leaves == ''">
                        <tr class="border-b">
                            <td colspan="9">
                                <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="this.page_count > 1">
                <paginate v-model="page" :page-count="this.page_count" :page-range="3" :margin-pages="1" :click-handler="getData" :prev-text="'<'" :next-text="'>'" :container-class="'pagination'" :page-class="'page-item'" :prev-link-class="'prev'" :next-link-class="'next'"></paginate>
            </div>
        </div>
    </div>
</template>

<script>
    import { bus } from "../../../app";
    export default {
        props:['url'],
        data () {
            return {
                leaves:[],
                status:'pending',
                params:{},
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
                axios.get('/teacher/studentLeave/list/'+this.status+'?page='+page).then(response => {
                    this.leaves         = response.data.data;
                    this.page_count     = response.data.meta.last_page;
                    this.total          = response.data.meta.total;
                    //console.log(this.leaves);    
                });
            },

            approveleave(id) 
            {
                var thisswal = this;
                swal({
                    title: 'Are you sure',
                    text: 'Do you want to approve this leave ?',
                    icon: "info",
                    buttons: [
                        'No',
                        'Yes'
                    ],
                    dangerMode: true,
                }).then(function(isConfirm) {
                    if (isConfirm) 
                    {
                        window.location.href = thisswal.url+ '/teacher/studentLeave/approve/'+ id;
                    }
                    else 
                    {
                        swal("Cancelled");
                    }
                });
            },

            rejectleave(id) 
            {
                var thisswal = this;
                swal({
                    title: 'Are you sure',
                    text: 'Do you want to reject this leave ?',
                    icon: "info",
                    buttons: [
                        'No',
                        'Yes'
                    ],
                    dangerMode: true,
                }).then(function(isConfirm) {
                    if (isConfirm) 
                    {
                        window.location.href = thisswal.url+ '/teacher/studentLeave/reject/'+ id;  
                    }
                    else 
                    {
                        swal("Cancelled");
                    }
                });
            },
        },
  
        created()
        {        
            this.getData();  
            bus.$on("statusTab", data => {
                if(data!='')
                {
                    this.status=data;
                    this.getData();           
                }
            });
        }
    }
</script>