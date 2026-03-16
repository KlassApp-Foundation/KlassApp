<template>
    <div>
        <portal to="payroll_search">
            <div class="relative mt-2 z-40">
                <input type="text" class="border px-10 py-2 text-sm border-gray-400 w-full rounded bg-white shadow"  placeholder="Search query" @click="showfilter()">
                <span class="input-group-btn absolute left-0 px-3 py-3 top-0">
                    <a href="#">
                        <svg class="w-4 h-4 fill-current text-gray-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.005 512.005" style="enable-background:new 0 0 512.005 512.005;" xml:space="preserve"><g><g><path d="M508.885,493.784L353.109,338.008c32.341-35.925,52.224-83.285,52.224-135.339c0-111.744-90.923-202.667-202.667-202.667 S0,90.925,0,202.669s90.923,202.667,202.667,202.667c52.053,0,99.413-19.883,135.339-52.245l155.776,155.776 c2.091,2.091,4.821,3.136,7.552,3.136c2.731,0,5.461-1.045,7.552-3.115C513.045,504.707,513.045,497.965,508.885,493.784z M202.667,384.003c-99.989,0-181.333-81.344-181.333-181.333S102.677,21.336,202.667,21.336S384,102.68,384,202.669 S302.656,384.003,202.667,384.003z"/></g></g></svg>
                    </a>
                </span>
                <a v-if="this.payroll_filter==0" href="#" dusk="show" @click="showfilter()" class="absolute right-0 top-0 px-2 py-3">
                    <svg class="w-2 h-2 m-1 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 490.656 490.656" style="enable-background:new 0 0 490.656 490.656;" xml:space="preserve"><g><g><path d="M487.536,120.445c-4.16-4.16-10.923-4.16-15.083,0L245.317,347.581L18.203,120.445c-4.16-4.16-10.923-4.16-15.083,0 c-4.16,4.16-4.16,10.923,0,15.083l234.667,234.667c2.069,2.091,4.8,3.136,7.531,3.136s5.461-1.045,7.552-3.115l234.667-234.667 C491.696,131.368,491.696,124.627,487.536,120.445z"/></g></g></svg>
                </a>
                <a v-if="this.payroll_filter==1" href="#" id="hide" @click="disablefilter()" class="absolute right-0 top-0 px-2 py-3">
                    <svg class="w-2 h-2 m-1 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.001 512.001" style="enable-background:new 0 0 512.001 512.001;" xml:space="preserve"><g><g><path d="M284.286,256.002L506.143,34.144c7.811-7.811,7.811-20.475,0-28.285c-7.811-7.81-20.475-7.811-28.285,0L256,227.717 L34.143,5.859c-7.811-7.811-20.475-7.811-28.285,0c-7.81,7.811-7.811,20.475,0,28.285l221.857,221.857L5.858,477.859 c-7.811,7.811-7.811,20.475,0,28.285c3.905,3.905,9.024,5.857,14.143,5.857c5.119,0,10.237-1.952,14.143-5.857L256,284.287 l221.857,221.857c3.905,3.905,9.024,5.857,14.143,5.857s10.237-1.952,14.143-5.857c7.811-7.811,7.811-20.475,0-28.285 L284.286,256.002z"/></g></g></svg>
                </a>
            </div>
        </portal>
        <portal to="payrollfilter">
            <div class="hide-menu" id="payroll-filter">
                <div class="absolute bg-white w-full  lg:w-2/4  shadow-lg border border-r-0 z-40  h-auto py-3 lg:px-3 overflow-auto search_user_filter">  
                    <div id="search_filter">
                        <div>
                            <ul class="search_filter leading-loose">
                              
                               
                                <li id="filter-content-gender" class="filter_container hide flex">
                                   
                                    <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 px-4 w-1/2">
                                        <div class="person_filter_name_title w-full  lg:w-2/5 md:w-2/5">
                                            <p class="text-gray-700">Month</p>
                                        </div>
                                        <div class="advanced_search_option_container my-2 lg:my-1 md:my-1 flex w-full lg:w-3/5 md:w-3/5" id="month"> 
                                            <select class="filter-form-control" id="month" v-model="month" name="month" data-type="dropdown">
                                                <option value="" disabled="disabled"> Month</option>
                                                <option v-for="months in monthlist" v-bind:value="months.id">{{ months.name }}</option>
                                            </select>
                                        </div>      
                                    </div> 

                                     <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 px-4 w-1/2">
                                        <div class="person_filter_name_title w-full  lg:w-2/5 md:w-2/5">
                                            <p class="text-gray-700">Year</p>
                                        </div>
                                        <div class="advanced_search_option_container my-2 lg:my-1 md:my-1 flex w-full lg:w-3/5 md:w-3/5" id="year"> 
                                            <select class="filter-form-control" id="year" v-model="year" name="year" data-type="dropdown">
                                                <option value="" disabled="disabled">Year</option>
                                                <option v-for="year in yearlist" v-bind:value="year.name">{{ year.name }}</option>
                                            </select>
                                        </div>      
                                    </div>  


                                </li>
                            </ul>
                        </div>
                      
                    </div>
                    <div class="tw-form-row my-4 flex justify-end mr-3 lg:mr-0 md:mr-3">
                        <a href="#" dusk="reset-btn" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 ml-3 text-sm font-medium" @click="reset()">Reset</a>
                        <a href="#" dusk="search-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 ml-3 text-sm font-medium" @click="searchmember()">Search</a>
                    </div>
                </div>
            </div>
        </portal>
    </div>
</template>

<script>
    import { bus } from "../../app";
    import PortalVue from "portal-vue";
    export default {
        props:['url','searchquery','type'],
        data(){
            return{
                payroll:[],
                month:'',
                year:'',
                payroll_filter:0,
                monthlist:[{id:'01' , name:'January'} , {id:'02' , name:'February'} , {id:'03' , name:'March'} , {id:'04' , name:'April'} , {id:'05' , name:'May'} , {id:'06' , name:'June'} , {id:'07' , name:'July'} , {id:'08' , name:'August'} , {id:'09' , name:'September'} , {id:'10' , name:'October'} , {id:'11' , name:'November'} , {id:'12' , name:'December'}],
                yearlist:[{name:'2020'} , { name:'2021'} , {name:'2022'} , {name:'2023'} , {name:'2024'} , { name:'2025'} , { name:'2026'} , {name:'2027'} , { name:'2028'} , { name:'2029'} , { name:'2030'} , {name:'2031'}],
            }
        },

        methods:
        {
            reset()
            {
               
                this.month='';
                this.year='';

                if(this.type == 'student')
                {
                    window.location.href = this.url+'/admin/students';
                }
                else
                {
                    window.location.href = this.url+'/admin/reports';
                }
            },

            enableDiv(id)
            {
                if($('#'+id).hasClass('hidden'))
                {
                    $('#'+id).removeClass('hidden').addClass('block');
                }
                else
                {
                    $('#'+id).removeClass('block').addClass('hidden');
                }
            },

            searchmember()
            {
                this.params = {
                    
                    month:this.month,
                    year:this.year,
                };

                if(this.type == 'student')
                {
                    this.final = this.url+'/admin/students/?'+this.searchquery;
                }
                else
                {
                    this.final = this.url+'/admin/reports/?'+this.searchquery;
                }
          
                Object.keys(this.params).forEach(key => {
                    // this.final.searchParams.append(key, params[key])
                    this.final = this.addParam(this.final, key, this.params[key])
                });

                window.location.href=this.final;
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

            showfilter()
            {   
                this.payroll_filter =1;  
                $('#payroll-filter').removeClass('hide-menu').addClass('block');
                bus.$emit("datashowFilter", '1');
            },

            disablefilter()
            {
                this.payroll_filter =0;  
                $('#payroll-filter').removeClass('block').addClass('hide-menu');
            },

            getData()
            {
                axios.get('accountant/payroll/payslip').then(response => {
                    this.payroll = response.data;
                    //this.setData();   
                });
            },
        },

        created()
        {
            bus.$on("dataMemberName", data => {
                if(data!='')
                {
                    this.disablefilter();
                }
            });

            this.getData();
        }
    }
</script>