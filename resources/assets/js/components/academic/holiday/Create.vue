<template>
  <div class="bg-white shadow px-4">
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

    <div class="tw-form-group relative">
      <div class="flex flex-wrap lg:flex-row justify-between">
         <div class=""></div>
         <div class="relative flex items-center py-2">
            <div class="flex items-center">
              <a href="#" class="bg-blue-500 rounded text-sm text-white px-2 py-1 whitespace-no-wrap flex items-center" @click="addRow()">
                  <span class="mx-1 text-sm font-semibold">Add New</span>
                  <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 409.6 409.6" xml:space="preserve" class="w-3 h-3 fill-current text-white"><g><g><path d="M392.533,187.733H221.867V17.067C221.867,7.641,214.226,0,204.8,0s-17.067,7.641-17.067,17.067v170.667H17.067 C7.641,187.733,0,195.374,0,204.8s7.641,17.067,17.067,17.067h170.667v170.667c0,9.426,7.641,17.067,17.067,17.067 s17.067-7.641,17.067-17.067V221.867h170.667c9.426,0,17.067-7.641,17.067-17.067S401.959,187.733,392.533,187.733z"></path></g></g></svg>
              </a>
            </div>
         </div>
      </div>
    </div>

    <div class="tw-form-group custom-table">
      <table class="w-full lg:w-3/4 md:w-3/4 border">
        <thead class="bg-gray-400">
          <tr>
            <th class="tw-form-label">Date<span class="text-red-500">*</span></th>
            <th class="tw-form-label">Title<span class="text-red-500">*</span></th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(input, index) in inputs">
            <td class="py-3 px-2" style="border-style: none; text-align: center;">
              <datetime format="YYYY-MM-DD" name="date" v-model="input.date" id="date"></datetime>
              <span v-if="errors['date'+index]" class="text-red-500 text-xs font-semibold">{{errors['date'+index][0]}}</span>
            </td>
            <td class="" style="border-style: none;">
              <div class="flex items-center">
                <input type="text" name="title" v-model="input.title" class="tw-form-control w-3/4" id="title" placeholder="Enter Title" @keyup='remaincharCount(25)' :maxlength="25">
                <div class="text-gray-700 text-xs my-1 mx-2" v-text="(25 - input.title.length)+'/'+25" style="text-align: right"></div>
              </div>               
              <span v-if="errors['title'+index]" class="text-red-500 text-xs font-semibold">{{errors['title'+index][0]}}</span>
            </td>

            <td class="" style="border-style: none;">
              <a href="#" class="btn-times" @click="deleteRow(index)" title="Delete">
                <svg data-v-689010ab="" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-4 h-4 fill-current text-black-500"><g data-v-689010ab=""><g data-v-689010ab=""><g data-v-689010ab=""><polygon data-v-689010ab="" points="353.574,176.526 313.496,175.056 304.807,412.34 344.885,413.804"></polygon> <rect data-v-689010ab="" x="235.948" y="175.791" width="40.104" height="237.285"></rect> <polygon data-v-689010ab="" points="207.186,412.334 198.497,175.049 158.419,176.52 167.109,413.804"></polygon> <path data-v-689010ab="" d="M17.379,76.867v40.104h41.789L92.32,493.706C93.229,504.059,101.899,512,112.292,512h286.74 c10.394,0,19.07-7.947,19.972-18.301l33.153-376.728h42.464V76.867H17.379z M380.665,471.896H130.654L99.426,116.971h312.474 L380.665,471.896z"></path></g></g></g> <g data-v-689010ab=""><g data-v-689010ab=""><path data-v-689010ab="" d="M321.504,0H190.496c-18.428,0-33.42,14.992-33.42,33.42v63.499h40.104V40.104h117.64v56.815h40.104V33.42 C354.924,14.992,339.932,0,321.504,0z"></path></g></g></svg>
              </a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
     
    <div class="py-3">
      <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="addHoliday()">Submit</a>
    </div>
  </div>
</template>

<script> 
import datetime from 'vuejs-datetimepicker';
  export default {
    props:['url'],
    components: { datetime},
    data(){
      return {
        start_date:'',
        date:'',
        title:'',
        errors:[],
        success:null,
        inputs: [{
          date:'',
          title:'',
        }],
      }
    },

    methods:
    {
      getData()
      {
        axios.get('/admin/holiday/add/list').then(response => {
          this.start_date = response.data.start_date;
          this.inputs.splice(0,1);
          for(var i =0 ; i < 5 ; i++)
          {
            this.inputs.push({
              date:this.start_date,
              title:'',
            });
          }
        });
      },

      remaincharCount(len)
      {
        var maxLength = len;
        $('textarea').keyup(function() {
          var textlen = maxLength - $(this).val().length+'/'+maxLength;
          $('#rchars').text(textlen);
        });
      },

      addRow()
      {
        this.inputs.push({
          date:this.start_date,
          title:'',
        });
      },

      addHoliday()
      {
        this.errors=[];
        this.success=null;

        let formData=new FormData(); 

        formData.append('count',this.inputs.length);

        for(let i=0; i<this.inputs.length;i++)
        {
          if(typeof this.inputs[i]['date'] !== "undefined")
          {
            formData.append('date'+i,this.inputs[i]['date']);
          }
          else
          {
            formData.append('date'+i,'');
          }

          if(typeof this.inputs[i]['title'] !== "undefined")
          {
            formData.append('title'+i,this.inputs[i]['title']);
          }
          else
          {
            formData.append('title'+i,'');
          }
        }

        axios.post('/admin/holiday/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
          this.success = response.data.success; 
          window.location.reload();
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

      deleteRow(index) 
      {
        this.inputs.splice(index,1);
      }
    },
    
    created()
    {
      this.getData();
    }
  }

</script>