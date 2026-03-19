<template>
  <div>
    <portal to="memberdetail">
      <div class="hide-menu absolute top-0" id="show-detail">
        <div class="bg-white w-full lg:w-2/5 md:w-2/5 shadow-lg border border-r-0 member-detail z-40 right-0 fixed h-full overflow-y-auto">
          <a href="#" @click="disableform()" class="absolute right-0">
            <svg class="w-3 h-3 m-3 fill-current text-gray-700" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.001 512.001" style="enable-background:new 0 0 512.001 512.001;" xml:space="preserve"><g><g><path d="M284.286,256.002L506.143,34.144c7.811-7.811,7.811-20.475,0-28.285c-7.811-7.81-20.475-7.811-28.285,0L256,227.717 L34.143,5.859c-7.811-7.811-20.475-7.811-28.285,0c-7.81,7.811-7.811,20.475,0,28.285l221.857,221.857L5.858,477.859 c-7.811,7.811-7.811,20.475,0,28.285c3.905,3.905,9.024,5.857,14.143,5.857c5.119,0,10.237-1.952,14.143-5.857L256,284.287 l221.857,221.857c3.905,3.905,9.024,5.857,14.143,5.857s10.237-1.952,14.143-5.857c7.811-7.811,7.811-20.475,0-28.285 L284.286,256.002z"/></g></g></svg>
          </a>
          <div class="flex flex-col lg:flex-row md:flex-row py-3">
            <div class="w-3/4 lg:w-1/5 md:w-1/5 p-3">
              <img v-bind:src="user.avatar" class="lg:w-24 lg:h-24 md:w-24 md:h-24">
            </div>
            <div class="w-full lg:w-4/5 md:w-4/5 px-3 lg:px-0 md:px-0">
              <div>
                <div class="leading-relaxed">
                  <h1 class="text-2xl font-semibold text-gray-700 capitalize">{{ user.fullname }}</h1>
                  <h1 class="text-base font-medium text-gray-600 capitalize">{{ user.standard }}</h1>
                </div>
                <!-- <p class="text-gray-700 text-sm my-1 capitalize">{{ user.profession }}</p> -->
                <div class="flex items-center my-4">
                  <svg class="w-5 h-5 fill-current text-blue-600" id="Layer_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m407.579 87.677c-31.073-53.624-86.265-86.385-147.64-87.637-2.62-.054-5.257-.054-7.878 0-61.374 1.252-116.566 34.013-147.64 87.637-31.762 54.812-32.631 120.652-2.325 176.123l126.963 232.387c.057.103.114.206.173.308 5.586 9.709 15.593 15.505 26.77 15.505 11.176 0 21.183-5.797 26.768-15.505.059-.102.116-.205.173-.308l126.963-232.387c30.304-55.471 29.435-121.311-2.327-176.123zm-151.579 144.323c-39.701 0-72-32.299-72-72s32.299-72 72-72 72 32.299 72 72-32.298 72-72 72z"/></g></svg>
                    <p class="text-gray-600 text-sm mx-2">Currently in <span class="font-bold">{{ user.city }}</span></p>
                </div>
              </div>  
              <div class="my-4 p-2 member-contact">
                <ul class="list-reset leading-loose">
                  <li class="flex items-center py-1">
                    <svg class="w-5 h-5 fill-current text-blue-600" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 503.604 503.604" style="enable-background:new 0 0 503.604 503.604;" xml:space="preserve"><g><g><path d="M337.324,0H167.192c-28.924,0-53.5,23.584-53.5,52.5v398.664c0,28.916,24.056,52.44,52.98,52.44l170.412-0.184 c28.92,0,52.58-23.528,52.58-52.448l0.248-398.5C389.908,23.452,366.364,0,337.324,0z M227.68,31.476h49.36 c4.336,0,7.868,3.52,7.868,7.868c0,4.348-3.532,7.868-7.868,7.868h-49.36c-4.348,0-7.868-3.52-7.868-7.868 C219.812,34.996,223.332,31.476,227.68,31.476z M198.02,33.98c2.916-2.912,8.224-2.952,11.136,0c1.46,1.456,2.324,3.5,2.324,5.588 c0,2.048-0.864,4.088-2.324,5.548c-1.452,1.46-3.504,2.32-5.548,2.32c-2.084,0-4.088-0.86-5.588-2.32 c-1.452-1.456-2.28-3.5-2.28-5.548C195.736,37.48,196.568,35.436,198.02,33.98z M250.772,488.008 c-12.984,0-23.544-10.568-23.544-23.548c0-12.984,10.56-23.548,23.544-23.548s23.544,10.564,23.544,23.548 C274.316,477.44,263.752,488.008,250.772,488.008z M365.488,424.908H141.232V74.756h224.256V424.908z"/></g></g></svg>
                    <p class="text-gray-600 text-sm mx-2">{{ user.mobile_no }}</p>
                  </li>
                  <li class="flex items-center py-1" v-if="this.user.email != null">
                    <svg class="w-5 h-5 fill-current text-blue-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><polygon points="339.392,258.624 512,367.744 512,144.896"/></g></g><g><g><polygon points="0,144.896 0,367.744 172.608,258.624"/></g></g><g><g><path d="M480,80H32C16.032,80,3.36,91.904,0.96,107.232L256,275.264l255.04-168.032C508.64,91.904,495.968,80,480,80z"/></g></g><g><g><path d="M310.08,277.952l-45.28,29.824c-2.688,1.76-5.728,2.624-8.8,2.624c-3.072,0-6.112-0.864-8.8-2.624l-45.28-29.856 L1.024,404.992C3.488,420.192,16.096,432,32,432h448c15.904,0,28.512-11.808,30.976-27.008L310.08,277.952z"/></g></g></svg>
                    <p class="text-gray-600 text-sm mx-2">
                      <!-- <a :href="this.url+'/admin/student/sendMessage/'+this.user.name">{{ user.email }} ( {{ user.email_verified }} )</a> -->
                      <a href="#">{{ user.email }} ( {{ user.email_verified }} )</a>
                    </p>
                  </li>
                </ul>
              </div>
              <div class="my-4">
                <h1 class="admin-h1">About {{ user.fullname }} </h1>
                <p class="text-xs leading-loose pr-1 my-2">{{ user.notes }}</p>
              </div>
              <div class="pt-3 border-t">
                <a :href="this.url+'/admin/student/show/'+this.membername" class="text-blue-500 text-sm flex items-center">
                  <span>View {{ user.fullname }}'s Full Detail</span>
                  <svg class="w-4 h-4 mx-2 fill-current text-blue-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M508.875,248.458l-160-160c-3.063-3.042-7.615-3.969-11.625-2.313c-3.99,1.646-6.583,5.542-6.583,9.854v21.333 c0,2.833,1.125,5.542,3.125,7.542l109.792,109.792H10.667C4.771,234.667,0,239.437,0,245.333v21.333 c0,5.896,4.771,10.667,10.667,10.667h432.917L333.792,387.125c-2,2-3.125,4.708-3.125,7.542V416c0,4.313,2.594,8.208,6.583,9.854 c1.323,0.552,2.708,0.813,4.083,0.813c2.771,0,5.5-1.083,7.542-3.125l160-160C513.042,259.375,513.042,252.625,508.875,248.458z"/></g></g></svg>
                </a>
              </div>
            </div>
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
    props:['url'],
    data(){
      return{
        user:[],
        errors:[],
        success:null,
        membername:null,
      }
    },

    created() 
    {
      //
      bus.$on("dataMemberName", data => {
        if(data!='')
        {//console.log(data);
          this.membername=data;  
          this.getData();
        }
      });

      bus.$on("datashowFilter", data => {
        if(data!='')
        {
          this.disableform();
        }
      });
    },

    methods:
    {
      disableform()
      {
        $('#show-detail').removeClass('block').addClass('hide-menu');
      },

      getData()
      {
        axios.get('/admin/student/show/details/'+this.membername).then(response => {
          this.user = response.data.data[0];
          //console.log(this.user);  
        });
      } 
    }
  }
</script>

<style>
  .tw-label{
      color:#3492e2;
    }
    .tw-label input[type="file"] {
      display: none;
    }
</style>

<style scoped>
  .modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, .5);
    display: table;
    transition: opacity .3s ease;
  }

  .modal-wrapper {
    display: table-cell;
    vertical-align: middle;
      overflow:auto;
  }

  .modal-container {
    margin: 0px auto;
    padding: 20px 30px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
    transition: all .3s ease;
    overflow:auto;
  }

  .modal-header h3 {
    margin-top: 0;
    color: #42b983;
  }

  .modal-body {
    margin: 20px 0;
  }

  .modal-default-button {
    float: right;
  }

  /*
   * The following styles are auto-applied to elements with
   * transition="modal" when their visibility is toggled
   * by Vue.js.
   *
   * You can easily play with the modal transition by editing
   * these styles.
   */

  .modal-enter {
    opacity: 0;
  }

  .modal-leave-active {
    opacity: 0;
  }

  .modal-enter .modal-container,
  .modal-leave-active .modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
  }

  .text-danger
  {
    color:red;
  }
</style>