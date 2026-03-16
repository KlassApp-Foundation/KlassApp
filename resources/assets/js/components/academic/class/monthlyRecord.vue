<template>
  <div class="overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.type=='monthly'?'block' :'hidden']">
    <div class="">
    <div class="flex lg:justify-end w-full px-3"> 
        <div class="flex flex-row lg:flex-col md:flex-col">
          <select name="select_month" v-model="select_month" id="select_month" class="tw-form-control w-full" v-on:change="submitMonth()">
            <option v-for="month in months" v-bind:value="month.id">{{ month.name }}</option>
          </select>
        </div>   
      </div>
      <div class="">
        <highcharts :options="chartOptions"></highcharts>
      </div>
      
      <div class="custom-table">
        <table class="w-full">
          <thead class="bg-grey-light">
            <tr class="border-b">
              <th class="text-left text-sm px-2 py-2 text-grey-darker"> Date </th>
              <th class="text-left text-sm px-2 py-2 text-grey-darker"> Session </th>
              <th class="text-left text-sm px-2 py-2 text-grey-darker"> No. of Absentees </th>
              <th class="text-left text-sm px-2 py-2 text-grey-darker"> Recorded By </th>
              <th class="text-left text-sm px-2 py-2 text-grey-darker"> Recorded On </th>
            </tr>
          </thead>   
          <tbody v-for="attendance in attendances">
            <tr class="border-b" v-for="(student,index) in attendance">
              <td class="py-3 px-2">
                <p class="font-semibold text-xs">{{ student[0]['date'] }}</p>
              </td>
              <td class="py-3 px-2">
                <p class="font-semibold text-xs">{{ student[0]['session'] }}</p>
              </td>
              <td class="py-3 px-2 cursor-pointer" @click="showAbsentees(student[0]['id_date'])">
                <p class="font-semibold text-xs">{{ Object.keys(student).length }}</p>
              </td>
              <td class="py-3 px-2">
                <p class="font-semibold text-xs">{{ student[0]['recorded_by'] }}</p>
              </td>
              <td class="py-3 px-2">
                <p class="font-semibold text-xs py-2">{{ student[0]['created_at'] }}</p>
              </td>
            </tr>
          </tbody>
          <tbody v-if="attendances == ''">
            <tr class="border-b">
              <td colspan="5">
                <p class="font-semibold text-sm" style="text-align: center">No Records Found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-for="attendance in attendances">
      <div v-for="(students,index) in attendance">
        <div v-for="student in students">
          <div v-if="show == student.id_date" class="modal modal-mask">
            <div class="modal-wrapper px-4">
              <div class="modal-container w-full max-w-md px-8 mx-auto">
                <div class="modal-header flex justify-between items-center">
                  <h2>Absent Students</h2>
                    <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                  <div class="flex items-center">
                    <table class="w-full">
                      <thead class="bg-grey-light">
                        <tr class="border-b">
                          <th class="text-left text-sm px-2 py-2 text-grey-darker"> Student Name </th>
                          <th class="text-left text-sm px-2 py-2 text-grey-darker"> Reason </th>
                          <th class="text-left text-sm px-2 py-2 text-grey-darker"> Remark </th>
                        </tr>
                      </thead>   
                      <tbody>
                        <tr class="border-b" v-for="student in students">
                          <td class="py-3 px-2">
                            <p class="font-semibold text-xs">
                              <a :href="url+'/admin/student/show/'+student.user_name">{{ student.user_fullname }}</a>
                            </p>
                          </td>
                          <td class="py-3 px-2">
                            <p class="font-semibold text-xs">{{ student.reason }}</p>
                          </td>
                          <td class="py-3 px-2">
                            <p class="font-semibold text-xs" v-if="student.remarks != null ">{{ student.remarks }}</p>
                            <p class="font-semibold text-xs" v-else>--</p>
                          </td>
                        </tr>
                      </tbody>
                      <tbody v-if="student == ''">
                        <tr class="border-b">
                          <td colspan="5">
                            <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { bus } from "../../../app";
  import {Chart} from 'highcharts-vue'
  export default {
    components: {
      highcharts: Chart
    },
    props:['url','id','mode'],
    data () {
      return {
        type:'monthly',
        chartOptions:{},
        lists:[],
        dates:[],
        forenoon_present:[],
        forenoon_absent:[],
        afternoon_present:[],
        afternoon_absent:[],
        select_month:'',
        attendances:[],
        show:0,
        months:[],
        errors:[],
        success:null,
      }
    },

    methods:
    {
      getData()
      {
        axios.get('/'+this.mode+'/standardLink/show/attendances/'+this.id).then(response => {
          this.lists = response.data;
          //console.log(this.lists);
          this.setData(this.lists);   
        });
      },

      setData(data)
      {
        if(Object.keys(data).length > 0)
        {
          this.months             = data.months;
          this.attendances        = data.attendances;
          this.select_month       = data.select_month;
          this.dates              = data.dates;
          this.forenoon_present   = data.forenoon_present;
          this.forenoon_absent    = data.forenoon_absent;
          this.afternoon_present  = data.afternoon_present;
          this.afternoon_absent   = data.afternoon_absent;
        }
        this.chartOptions = {
          chart: {
            "type": "column",
          },
          credits: {
            "enabled": false
          },
          plotOptions: {
            column: {
              stacking: "percent",
            }
          },
          "legend": {
            "enabled": false,
          },
          "title": {
            "text": 'Attendance Trend',
          },
          "xAxis": {
            "title": {},
            "categories":this.dates,
          },
          "yAxis": [{
            "min": 0,
            "allowDecimals": false,
            "title": {
                "text": ''
            },
            "labels":
            {
              "enabled": false
            }
          }],
          "tooltip": {
              "shared": true,
              "useHTML": true,
              "headerFormat": '{point.x}<table>',
              "pointFormat": '<tr><td><b>{series.userOptions.stack}</b><span style="color: {series.color}"> ({series.name})</span> : </td>' +
                  '<td style="text-align: right"><b>{point.y}</b></td></tr>',
              "footerFormat": '</table>',
              "backgroundColor": '#fad5c0'
            },
          "series": [{
            "name": "Present",
            "color": "#82f5b6",
            "data": this.forenoon_present,
            "stack": 'Forenoon',
          }, {
            "name": "Absent",
            "color": "#f56c7c",
            "data": this.forenoon_absent,
            "stack": 'Forenoon',
          }, {
            "name": "Present",
            "color": "#82f5b6",
            "data": this.afternoon_present,
            "stack": 'Afternoon',
          }, {
            "name": "Absent",
            "color": "#f56c7c",
            "data": this.afternoon_absent,
            "stack": 'Afternoon',
          }]
        };
      },

      showAbsentees(id)
      {
        this.show = id ;
      },

      closeModal()
      {
        this.show = 0 ;
      },

      submitMonth()
      {
        this.errors=[];
        this.success=null;

        let formData=new FormData(); 

        formData.append('select_month',this.select_month);

        axios.post('/'+this.mode+'/standardLink/show/attendances/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {
          this.success = response.data.success;
          this.setData(response.data); 
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },
    },
    
    created()
    {   
      this.getData();
      bus.$on("type", data => {
        if(data!='')
        {
          this.type=data;                
        }
      });   
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
 /* height: 550px;*/
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
