<template>
	<div class="overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.type=='monthly'?'block' :'hidden']">
    <div class="custom-table mx-5 py-5">
      <table class="w-full">
        <thead class="bg-grey-light">
          <tr class="border-b">
            <th class="text-left text-sm px-2 py-2 text-grey-darker"> Month </th>
            <th class="text-left text-sm px-2 py-2 text-grey-darker" >Leave Taken (days)</th>
            
          </tr>
        </thead>   
        <tbody >
          <tr v-for="(value, key, index) in students.staff" class="border-b">
            <td class="py-3 px-2" >
              {{key}}
            </td>
            <td class="py-3 px-2">
             {{value}} 
            </td>
          </tr>
        </tbody>
        <tbody v-if="students == ''">
          <tr class="border-b">
            <td colspan="5">
              <p class="font-semibold text-sm" style="text-align: center">No Records Found</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
	</div>
</template>

<script>
  import { bus } from "../../../app";

  export default {
    props:['url','name'],
    data () {
      return {
        type:'monthly',
        students:[],
        type:'',
        errors:[],
        success:null,
      }
    },

    methods:
    {
      getData()
      {
        this.type='monthly';
        axios.get('/admin/teacher/attendances/show/'+this.name).then(response => {
          this.students = response.data;
          console.log(this.students);
        });
      },
    },
    
    created()
    {   
      this.getData();
      //alert(this.type);

      bus.$on("type", data => {
        if(data!='')
        {
          this.type=data;                
        }
      });     
    }
  }
</script>