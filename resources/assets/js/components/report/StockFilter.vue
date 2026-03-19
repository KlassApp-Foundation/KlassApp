<template>
    <div>
        <portal to="stock_search">
            <div class="relative mt-2 z-40">
                <input type="text" class="border px-10 py-2 text-sm border-gray-400 w-full rounded bg-white shadow"  placeholder="Search query" @click="showfilter()">
                <span class="input-group-btn absolute left-0 px-3 py-3 top-0">
                    <a href="#">
                        <svg class="w-4 h-4 fill-current text-gray-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30.239px" height="30.239px" viewBox="0 0 30.239 30.239" style="enable-background:new 0 0 30.239 30.239;" xml:space="preserve"><g><path d="M20.194,3.46c-4.613-4.613-12.121-4.613-16.734,0c-4.612,4.614-4.612,12.121,0,16.735 c4.108,4.107,10.506,4.547,15.116,1.34c0.097,0.459,0.319,0.897,0.676,1.254l6.718,6.718c0.979,0.977,2.561,0.977,3.535,0 c0.978-0.978,0.978-2.56,0-3.535l-6.718-6.72c-0.355-0.354-0.794-0.577-1.253-0.674C24.743,13.967,24.303,7.57,20.194,3.46z M18.073,18.074c-3.444,3.444-9.049,3.444-12.492,0c-3.442-3.444-3.442-9.048,0-12.492c3.443-3.443,9.048-3.443,12.492,0 C21.517,9.026,21.517,14.63,18.073,18.074z"/></g></svg>
                    </a>
                </span>
                <a v-if="this.stock_filter==0" href="#" dusk="show" @click="showfilter()" class="absolute right-0 top-0 px-2 py-3">
                    <svg class="w-2 h-2 m-1 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="292.362px" height="292.362px" viewBox="0 0 292.362 292.362" style="enable-background:new 0 0 292.362 292.362;" xml:space="preserve"><g><path d="M286.935,69.377c-3.614-3.617-7.898-5.424-12.848-5.424H18.274c-4.952,0-9.233,1.807-12.85,5.424 C1.807,72.998,0,77.279,0,82.228c0,4.948,1.807,9.229,5.424,12.847l127.907,127.907c3.621,3.617,7.902,5.428,12.85,5.428 s9.233-1.811,12.847-5.428L286.935,95.074c3.613-3.617,5.427-7.898,5.427-12.847C292.362,77.279,290.548,72.998,286.935,69.377z"/></g></svg>
                </a>
                <a v-if="this.stock_filter==1" href="#" id="hide" @click="disablefilter()" class="absolute right-0 top-0 px-2 py-3">
                    <svg class="w-2 h-2 m-1 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.001 512.001" style="enable-background:new 0 0 512.001 512.001;" xml:space="preserve"><g><g><path d="M284.286,256.002L506.143,34.144c7.811-7.811,7.811-20.475,0-28.285c-7.811-7.81-20.475-7.811-28.285,0L256,227.717 L34.143,5.859c-7.811-7.811-20.475-7.811-28.285,0c-7.81,7.811-7.811,20.475,0,28.285l221.857,221.857L5.858,477.859 c-7.811,7.811-7.811,20.475,0,28.285c3.905,3.905,9.024,5.857,14.143,5.857c5.119,0,10.237-1.952,14.143-5.857L256,284.287 l221.857,221.857c3.905,3.905,9.024,5.857,14.143,5.857s10.237-1.952,14.143-5.857c7.811-7.811,7.811-20.475,0-28.285 L284.286,256.002z"/></g></g></svg>
                </a>
            </div>
        </portal>
        <portal to="stockfilter">
            <div class="hide-menu" id="stock-filter">
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
                                                <option v-for="i in range(start,end)" v-bind:value="i">{{ i }}</option>
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
        props:['url','searchquery'],
        data(){
            return{
                month:'',
                year:'',
                date:'',
                start:'',
                end:'',
                stock_filter:0,
                monthlist:[{id:'01' , name:'January'} , {id:'02' , name:'February'} , {id:'03' , name:'March'} , {id:'04' , name:'April'} , {id:'05' , name:'May'} , {id:'06' , name:'June'} , {id:'07' , name:'July'} , {id:'08' , name:'August'} , {id:'09' , name:'September'} , {id:'10' , name:'October'} , {id:'11' , name:'November'} , {id:'12' , name:'December'}],
            }
        },

        methods:
        {
            reset()
            {
                this.month='';
                this.year='';
                window.location.href = this.url+'/admin/reports';
            },

            searchmember()
            {
                this.params = {
                    month:this.month,
                    year:this.year,
                };
                this.final = this.url+'/admin/reports?'+this.searchquery;
          
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
                this.stock_filter =1;  
                $('#stock-filter').removeClass('hide-menu').addClass('block');
                bus.$emit("datashowFilter", '1');
            },

            disablefilter()
            {
                this.stock_filter =0;  
                $('#stock-filter').removeClass('block').addClass('hide-menu');
            },

            getData()
            {
                axios.get('/admin/alumni/getdate').then(response => {
                    this.date = response.data;
                    //console.log(this.date);
                    this.setData();   
                });
            },

            setData()
            { 
                if(Object.keys(this.date).length>0)
                {
                    this.start  = parseInt(this.date.start);
                    if(this.date.end != null)
                    {
                        this.end = parseInt(this.date.end);
                    }
                    else
                    {
                        this.end = null;
                    }
                }
            },
     
            range(max,min)
            {
                var array = [],
                j = 0;
                if(min != null)
                {
                    for(var i = max; i >= min; i--)
                    {
                        array[j] = i;
                        j++;
                    }
                }
                else
                {
                    array[j] = max
                }
                return array;
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