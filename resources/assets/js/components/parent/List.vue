<template>
    <div>
        <portal to="parent_index">
            <div class="flex flex-wrap lg:flex-row justify-between my-3">
                <div class="">
                    <h1 class="admin-h1 my-3">Parents</h1>
                </div>

                <div class="relative flex items-center w-8/12 lg:w-1/4 md:w-1/4 justify-end">
                    <div class="flex items-center w-full justify-end">
                        <a :href="url+'/admin/parents'" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 ml-3 text-sm font-medium">
                            <span class="mx-1 text-sm font-semibold">Reset</span>
                        </a> 
                    </div>
                </div>
            </div>
        </portal>
        <vue-good-table  :columns="columns" :rows="rows" @on-column-filter="onColumnFilter" @on-page-change="onPageChange" :paginationOptions="{ enabled: true , perPageDropdownEnabled: false }" :totalRows="totalRecords" :isLoading="isLoading" mode="remote"> 
            <template slot="table-row" slot-scope="props">
                <div v-if="props.column.field == 'action'" class="w-full flex justify-between">
                    <p v-bind:class="[props.row.count==0?'hidden':'block']">
                        <a :href=props.row.editurl class="block btn px-2 py-1 bg-blue-600 text-white rounded text-sm">Edit</a>
                    </p>
                </div>
                <div v-if="props.column.field == 'fullname'" class="w-full flex justify-between">
                    <a :href=props.row.showurl class="">{{ props.row.fullname }}
                    <span  v-if="props.row.status != 'active'" class="bg-red-500 rounded px-1 text-xs text-white">InActive</span> 
                    </a>

                </div>
                <div v-if="props.column.field == 'children.fullname'" class="w-full flex justify-between">
                    <a :href=props.row.children.name class="">{{ props.row.children.fullname }}</a>
                </div>
                <div v-if="props.column.field == 'mobile_no'" class="w-full flex justify-between">
                    <p>{{ props.row.mobile_no }}</p>
                </div>
            </template>
        </vue-good-table>     
    </div>
</template>

<script>

    import 'vue-good-table/dist/vue-good-table.css'
    import { VueGoodTable } from 'vue-good-table';
    export default {
        props:['url' , 'searchquery'],
        components: {
            VueGoodTable,
        },
        data() {
            return{
                show_event_link:'',
                array:{},
                totalRecords:'',
                action:'',
                id:'',
                isLoading: false,
                columns: [
                    {
                        label: 'Name',
                        field: 'fullname',
                        filterOptions: {
                            enabled: true,
                            placeholder: "Search",
                            filterFn: this.myFunc,
                        }
                    },
                    {
                        label: 'Parent Of',
                        field: 'children.fullname',
                        filterOptions: {
                            enabled: true,
                            placeholder: "Search",
                            filterFn: this.myFunc,
                        }
                    },
                    {
                        label: 'Mobile Number',
                        field: 'mobile_no',
                        filterOptions: {
                            enabled: true,
                            placeholder: "Search",
                            filterFn: this.myFunc,
                        }
                    },
                    {
                        label: 'Action',
                        field:'action',
                        html:true,
                    },
                ],
                rows: [
          
                ], 
                total: 0,
                page: 1,
                page_count: 0,
                fullname:'',
                mobile_no:'',
                student_name:'',
            }
        },

        methods: 
        {
            getData()
            {
                /*axios.get('/admin/parent/list?'+this.searchquery+'&page='+page).then(response => {
                    this.rows = response.data.data;
                    this.page_count = response.data.meta.last_page;
                    this.totalRecords = response.data.meta.total;
                    //console.log(this.rows)
                });*/
                axios.get('/admin/parent/list?'+'fullname='+this.fullname+'&student_name='+this.student_name+'&mobile_no='+this.mobile_no+'&page='+this.page).then(response => {
                    this.rows = response.data.data;
                    this.page_count = response.data.meta.last_page;
                    this.totalRecords = response.data.meta.total;
                    //console.log(this.rows)
                });
            },

    
            onColumnFilter(params) 
            {
                //console.log(params.columnFilters['fullname']);

                if(typeof params.columnFilters['fullname'] !== "undefined")
                {
                  this.fullname=params.columnFilters['fullname'];
                }
                if(typeof params.columnFilters['mobile_no'] !== "undefined")
                {
                   this.mobile_no=params.columnFilters['mobile_no'];
                }
                if(typeof params.columnFilters['children.fullname'] !== "undefined")
                {
                  this.student_name=params.columnFilters['children.fullname'];
                }
                this.page=1;
                
                
                 
               /* this.array = {
                    fullname:params.columnFilters['fullname'],
                    mobile_no:params.columnFilters['mobile_no'],
                    student_name:params.columnFilters['children.fullname'],
                };*/
    
                /*axios.get('/admin/parent/list?'+'firstname='+this.fullname+'&page='+this.page).then(response => {
                    this.rows = response.data.data;
                    this.page_count = response.data.meta.last_page;
                    this.totalRecords = response.data.meta.total;
                    //console.log(this.rows)
                });*/
                this.getData();

               /* this.final=this.url+'/admin/parents?'+this.searchquery;
          
                Object.keys(this.array).forEach(key => {
                    this.final = this.addParam(this.final, key, this.array[key])
                });

                window.location.href=this.final;*/
            },

            onPageChange(params) 
            {
                this.page = params.currentPage;
                this.getData(this.page);
            },

            addParam(url, param, value) 
            {
                param = encodeURIComponent(param);
                var r = "([&?]|&amp;)" + param + "\\b(?:=(?:[^&#]*))*";
                var a = document.createElement('a');
                var regex = new RegExp(r);
                var str = param + (value ? "=" + encodeURIComponent(value) : ""); 
                a.href = url;
                var q = a.search.replace(regex, "$1"+str);
                if (q === a.search) 
                {
                    a.search += (a.search ? "&" : "") + str;
                } 
                else 
                {
                    a.search = q;
                }
                return a.href ;
            },
        },

        created()
        {
            this.getData();
        },
    }
</script>