<template>
  <div>
    <portal to="search">
      <div class="relative mt-2 z-40">
        <input type="text" class="border px-10 py-2 text-sm border-gray-400 w-full rounded bg-white shadow "  placeholder="Search query" @click="showfilter()">
        <span class="input-group-btn absolute left-0 px-3 py-3 top-0">
          <a href="#">
          <svg class="w-4 h-4 fill-current text-gray-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.005 512.005" style="enable-background:new 0 0 512.005 512.005;" xml:space="preserve">
<g>
  <g>
    <path d="M508.885,493.784L353.109,338.008c32.341-35.925,52.224-83.285,52.224-135.339c0-111.744-90.923-202.667-202.667-202.667
      S0,90.925,0,202.669s90.923,202.667,202.667,202.667c52.053,0,99.413-19.883,135.339-52.245l155.776,155.776
      c2.091,2.091,4.821,3.136,7.552,3.136c2.731,0,5.461-1.045,7.552-3.115C513.045,504.707,513.045,497.965,508.885,493.784z
       M202.667,384.003c-99.989,0-181.333-81.344-181.333-181.333S102.677,21.336,202.667,21.336S384,102.68,384,202.669
      S302.656,384.003,202.667,384.003z"></path>
  </g>
</g></svg>
          </a>
        </span>
        <a v-if="this.show_filter==0" href="#" dusk="show"  @click="showfilter()" class="absolute right-0 top-0 px-2 py-3">
        <!--  <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="292.362px" height="292.362px" viewBox="0 0 292.362 292.362" xml:space="preserve" class="w-2 h-2 m-1 fill-current text-gray-700"><g><path d="M286.935,69.377c-3.614-3.617-7.898-5.424-12.848-5.424H18.274c-4.952,0-9.233,1.807-12.85,5.424
    C1.807,72.998,0,77.279,0,82.228c0,4.948,1.807,9.229,5.424,12.847l127.907,127.907c3.621,3.617,7.902,5.428,12.85,5.428
    s9.233-1.811,12.847-5.428L286.935,95.074c3.613-3.617,5.427-7.898,5.427-12.847C292.362,77.279,290.548,72.998,286.935,69.377z"></path></g></svg> -->
    <svg class="w-2 h-2 m-1 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 490.656 490.656" style="enable-background:new 0 0 490.656 490.656;" xml:space="preserve">
<g>
  <g>
    <path d="M487.536,120.445c-4.16-4.16-10.923-4.16-15.083,0L245.317,347.581L18.203,120.445c-4.16-4.16-10.923-4.16-15.083,0
      c-4.16,4.16-4.16,10.923,0,15.083l234.667,234.667c2.069,2.091,4.8,3.136,7.531,3.136s5.461-1.045,7.552-3.115l234.667-234.667
      C491.696,131.368,491.696,124.627,487.536,120.445z"></path>
  </g>
</g></svg>
        </a>
        <a v-if="this.show_filter==1" href="#" id="hide"  @click="disablefilter()" class="absolute right-0 top-0 px-2 py-3">
          <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.001 512.001" xml:space="preserve" class="w-2 h-2 m-1 fill-current text-gray-700"><g><g><path d="M284.286,256.002L506.143,34.144c7.811-7.811,7.811-20.475,0-28.285c-7.811-7.81-20.475-7.811-28.285,0L256,227.717
      L34.143,5.859c-7.811-7.811-20.475-7.811-28.285,0c-7.81,7.811-7.811,20.475,0,28.285l221.857,221.857L5.858,477.859
      c-7.811,7.811-7.811,20.475,0,28.285c3.905,3.905,9.024,5.857,14.143,5.857c5.119,0,10.237-1.952,14.143-5.857L256,284.287
      l221.857,221.857c3.905,3.905,9.024,5.857,14.143,5.857s10.237-1.952,14.143-5.857c7.811-7.811,7.811-20.475,0-28.285
      L284.286,256.002z"></path></g></g></svg>
        </a>
      </div>
    </portal>
    <portal to="teacherfilter">
      <div class="hide-menu" id="show-filter">
        <div class="absolute bg-white w-full  lg:w-2/4  shadow-lg border border-r-0 z-40  h-auto py-3 lg:px-4  overflow-auto search_user_filter">  
          <div id="search_filter">
            <div>
              <ul class="search_filter leading-loose">
               <li id="filter-content-name" class="filter_container">
                  <div class="advanced_search_option_container " id="name">
                    <div class="person_address_container flex">
                      <div class="flex flex-col lg:flex-row md:flex-row my-1 w-1/2 px-4 items-center">
                        <div class="person_filter_name_title w-full lg:w-2/5 md:w-2/5">
                          <p class="text-gray-700">First Name</p>
                        </div>
                        <div class="person_filter_name_input w-full lg:w-3/5 md:w-3/5">
                          <input v-model="firstname" name="firstname" id="firstname" type="text" class="filter-form-control my-2 lg:my-0 md:my-0" value="" placeholder="First Name">
                        </div>
                      </div>
                      <div class="flex flex-col lg:flex-row md:flex-row my-1 w-1/2 px-4 items-center">
                          <div class="person_filter_name_title w-full lg:w-2/5 md:w-2/5">
                            <p class="text-gray-700">Last Name</p>
                          </div>
                          <div class="person_filter_name_input w-full lg:w-3/5 md:w-3/5">
                            <input v-model="lastname" name="lastname" id="lastname" type="text" class="filter-form-control my-2 lg:my-0 md:my-0" value="" placeholder="Last Name">
                          </div>
                      </div>
                    </div>
                  </div>
                </li>

                <li id="filter-content-phone" class="filter_container flex">
                  <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 w-1/2 px-4">
                    <div class="person_filter_name_title w-full lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Phone</p>
                    </div>
                    <div class="advanced_search_option_container  my-1 w-full lg:w-3/5 md:w-3/5" >
                      <input class="filter-form-control my-2 lg:my-0 md:my-0" v-model="mobile_no" id="mobile_no" name="mobile_no" type="text" value="" placeholder="Phone">
                    </div>
                  </div>
                  <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 w-1/2 px-4">
                  <div class="person_filter_name_title w-full lg:w-2/5 md:w-2/5">
                    <p class="text-gray-700">Email</p>
                  </div>
                  <div class="advanced_search_option_container my-1 w-full lg:w-3/5 md:w-3/5">
                    <input class="filter-form-control my-2 lg:my-0 md:my-0" name="email" v-model="email" id="email" type="text" value="" placeholder="Email">       
                  </div>  
                </div> 
                </li>

               <!--  <li id="filter-content-qualification" class="filter_container hide flex">
                  <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 px-4 w-1/2">
                    <div class="person_filter_name_title w-full  lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Additional Certificates</p>
                    </div>
                    <div class="advanced_search_option_container my-2 lg:my-1 md:my-1 flex w-full lg:w-3/5 md:w-3/5" id="qualification"> 
                      <select name="qualification" v-model="qualification" id="qualification" class="filter-form-control" data-type="dropdown">
                        <option value="" disabled="disabled">Additional Certificates</option>
                        <option value="" v-for="qualification in qualificationlist" v-bind:value="qualification.id">{{qualification.display_name}}</option>
                      </select>
                    </div>      
                  </div> 
                  <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 px-4 w-1/2">
                    <div class="person_filter_name_title w-full  lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Designation</p>
                    </div>
                    <div class="advanced_search_option_container my-2 lg:my-1 md:my-1 flex w-full lg:w-3/5 md:w-3/5" id="designation"> 
                      <select name="designation" v-model="designation" id="designation" class="filter-form-control" data-type="dropdown">
                        <option value="" disabled="disabled">Designation</option>
                        <option value="" v-for="designation in designationlist" v-bind:value="designation.id">{{designation.name}}</option>
                      </select>
                    </div>      
                  </div> 
                </li> -->

                <li id="filter-content-gender" class="filter_container hide flex">
                  <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 w-1/2 px-4 ">
                    <div class="person_filter_name_title w-full lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Gender</p>
                    </div>
                    <div class="advanced_search_option_container flex my-2 lg:my-1 md:my-1 w-full lg:w-3/5 md:w-3/5" id="gender">
                      <select name="gender" v-model="gender" id="gender" class="filter-form-control" data-type="dropdown">
                        <option value="" disabled="disabled">Gender</option>
                        <option value="male">Male</option>    
                        <option value="female">Female</option>   
                      </select>     
                    </div>
                  </div>
                  <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 px-4 w-1/2">
                    <div class="person_filter_name_title w-full  lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Blood Group</p>
                    </div>
                    <div class="advanced_search_option_container my-2 lg:my-1 md:my-1 flex w-full lg:w-3/5 md:w-3/5" id="blood_group"> 
                      <select class="filter-form-control" id="blood_group" v-model="blood_group" name="blood_group" data-type="dropdown">
                        <option value="" disabled="disabled">Blood Group</option>
                        <option value="" v-for="blood_group in blood_groups" v-bind:value="blood_group.num">{{ blood_group.name }}</option>
                      </select>
                    </div>      
                  </div> 
                </li>

                <li id="filter-content-marital_status" class="filter_container hide flex">
                  <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 w-1/2 px-4 ">
                    <div class="person_filter_name_title w-full lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Marital Status</p>
                    </div>
                    <div class="advanced_search_option_container flex my-2 lg:my-1 md:my-1 w-full lg:w-3/5 md:w-3/5" id="marital_status">
                      <select name="marital_status" v-model="marital_status" id="marital_status" class="filter-form-control" data-type="dropdown">
                        <option value="" disabled="disabled">Marital Status</option>
                        <option v-for="marital in maritalList" v-bind:value="marital.id">{{ marital.name }}</option>    
                      </select>     
                    </div>
                  </div>
                  <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 px-4 w-1/2">
                    <div class="person_filter_name_title w-full  lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Designation</p>
                    </div>
                    <div class="advanced_search_option_container my-2 lg:my-1 md:my-1 flex w-full lg:w-3/5 md:w-3/5" id="designation"> 
                      <select name="designation" v-model="designation" id="designation" class="filter-form-control" data-type="dropdown">
                        <option value="" disabled="disabled">Designation</option>
                        <option value="" v-for="designation in designationlist" v-bind:value="designation.id">{{designation.name}}</option>
                      </select>
                    </div>      
                  </div> 
                </li>

                <li id="filter-content-gender" class="filter_container hide flex">
                    <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 px-4 w-1/2">
                    <div class="person_filter_name_title w-full  lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Birthday</p>
                        </div>
                    <div class="advanced_search_option_container my-2 lg:my-1 md:my-1 flex w-full lg:w-3/5 md:w-3/5" id="date_of_birth"> 
                        <select class="filter-form-control" id="date_of_birth" v-model="date_of_birth" name="date_of_birth" data-type="dropdown">
                          <option value="" disabled="disabled">Month</option>
                          <option v-for="month in months" v-bind:value="month.id">{{ month.name }}</option>
                        </select>
                      </div>      
                    </div> 

                    <div class="flex flex-col lg:flex-row md:flex-row items-center my-1 px-4 w-1/2">
                    <div class="person_filter_name_title w-full  lg:w-2/5 md:w-2/5">
                      <p class="text-gray-700">Job Type</p>
                        </div>
                    <div class="advanced_search_option_container my-2 lg:my-1 md:my-1 flex w-full lg:w-3/5 md:w-3/5" id="job_type"> 
                        <select class="filter-form-control" id="job_type" v-model="job_type" name="job_type" data-type="dropdown">
                          <option value="" disabled="disabled">Job type</option>
                          <option v-for="job in jobs" v-bind:value="job.id">{{ job.name }}</option>
                        </select>
                      </div>      
                    </div> 
                </li>

            
              

              </ul>
            </div>
            <div>
           <!--  <ul class="search_filter leading-loose">
              <li id="filter-content-date_of_birth" class="filter_container hide">
                <div class=" my-1 flex flex-col lg:flex-row md:flex-row items-center">
                  <div class="person_filter_name_title pl-4 w-full lg:w-1/6 md:w-1/6">
                    <p class="text-gray-700">Date Of Birth:</p>
                  </div>
                  <div class="advanced_search_option_container my-1 flex items-center w-full lg:w-5/6 md:w-5/6" id="date_of_birth"> 
                    <div class="birthdate_filter  flex items-center px-4 my-1 w-1/2">
                      <div class="w-2/5">
                        <p class="text-gray-700">Before:</p>
                      </div>
                      <div class="w-3/5">
                        <input type="date" class="filter-form-control appearance-none my-2 lg:my-0 md:my-0" v-model="date_of_birth_before" id="date_of_birth_before" name="date_of_birth_before" value="">
                      </div>
                    </div>       
                    <div class="birthdate_filter flex items-center px-4 my-1 w-1/2">
                      <div class="w-2/5">
                        <p class="text-gray-700">After:</p>
                      </div>
                      <div class="w-3/5">
                        <input type="date" class="filter-form-control appearance-none my-2 lg:my-0 md:my-0" v-model="date_of_birth_after" id="date_of_birth_after" name="date_of_birth_after" value="">
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul> -->
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
          firstname:'',
          lastname:'',
          gender:'',
          date_of_birth_after:'',
          date_of_birth_before:'',
          date_of_birth:'',
          job_type:'',
          mobile_no:'',
          email:'',
          marital_status:'',
          maritalList:[],
          qualification:'',
          qualificationlist:[],
          count:100,
          selectedLetter: undefined,
          active: false,
          errors:[],
          success:null,
          params:{},
          show_filter:0,
          designation:'',
          blood_group:'' ,          
          blood_groups:[],
          maritalList:[],
          designationlist:[],
          months:[{id:'01' , name:'January'} , {id:'02' , name:'February'} , {id:'03' , name:'March'} , {id:'04' , name:'April'} , {id:'05' , name:'May'} , {id:'06' , name:'June'} , {id:'07' , name:'July'} , {id:'08' , name:'August'} , {id:'09' , name:'September'} , {id:'10' , name:'October'} , {id:'11' , name:'November'} , {id:'12' , name:'December'}], 
          jobs:[{id:'full_time' , name:'Full Time'} , {id:'part_time' , name:'Part Time'}], 
        }
      },

      methods:
      {
        reset()
        {
          this.firstname='';
          this.lastname='';
          this.gender='';
          this.date_of_birth_after='';
          this.date_of_birth_before='';
          this.date_of_birth='';
          this.job_type='';
          this.mobile_no='';
          this.email='';
          this.location='';
          this.qualification='';
          this.blood_group='';
          this.designation='';
          this.marital_status='';
          window.location.href=this.url+"/admin/staffs";
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
                firstname:this.firstname,
                lastname:this.lastname,
                gender:this.gender,
                date_of_birth:this.date_of_birth,
                job_type:this.job_type,
                mobile_no:this.mobile_no,
                email:this.email,
                qualification:this.qualification,
                blood_group:this.blood_group,
                designation:this.designation,
                marital_status:this.marital_status,
             };

            this.final=this.url+'/admin/staffs/?'+this.searchquery;
          
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
          this.show_filter =1;  
          $('#show-filter').removeClass('hide-menu').addClass('block');
          bus.$emit("datashowFilter", '1');
        },

        disablefilter()
        {
          this.show_filter =0;  
          $('#show-filter').removeClass('block').addClass('hide-menu');
        },

        getData()
          {
            axios.get('/admin/teacher').then(response => {
            this.user = response.data;
            this.setData();   
            });
          },

      setData()
      {
        if(Object.keys(this.user).length>0)
        {
          this.qualificationlist=this.user.qualificationlist;
          this.designationlist  = this.user.nonteachinglist;
          this.blood_groups     = this.user.blood_groups;
          this.maritalList      = this.user.maritalList;
          //console.log(this.qualificationlist)
        }
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
      },
  }

</script>