<template>
 <div class="overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
    <div class="flex flex-wrap custom-table mx-3 my-3">
      <table class="w-full">
        <thead class="bg-grey-light">
          <tr class="border-b">
            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Date </th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Title </th>
         
          </tr>
        </thead>   
        <tbody v-if="this.holidays != ''">
          <tr class="border-b" v-for="holiday in holidays">
            <td class="py-3 px-2">
              <p class="font-semibold text-xs">{{ holiday['date'] }}</p>
            </td>
            <td class="py-3 px-2">
              <p class="font-semibold text-xs">{{ holiday['title'] }}</p>
            </td>
          </tr>
        </tbody>
        <tbody v-else>
          <tr class="border-b">
            <td colspan="3"><p class="font-semibold text-s" style="text-align: center">No Records Found</p></td>
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
import datetime from 'vuejs-datetimepicker';
  export default {
    props:['url'],
    components: { datetime},
    data () {
      return {
        holidays:[],
        date:'',
        title:'',
        show:'',
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
        axios.get('/admin/holidays/list?page='+page).then(response => {
          this.holidays = response.data.data;
          this.page_count = response.data.meta.last_page;
          this.total = response.data.meta.total;
          //console.log(this.holidays);   
        });
      },
     
    },
  
    created()
    {   
      this.getData(); 
    }
  }
</script>

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
/*    height: 550px;*/
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