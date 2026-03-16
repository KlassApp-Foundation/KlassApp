<template>
  <div class="px-3 overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab==5?'block' :'hidden']">
  <div class="custom-table ">
    <table class="w-full overflow-x-auto"> <!-- profiletab-table -->
      <thead>
        <tr>
          <th>Incident Details</th>
          <th>Teacher</th>
          <th>Incident date</th>
          <th>Attachment</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody v-if="Object.keys(disciplines).length > 0">
        <tr v-for="discipline in disciplines">
          <td>
            <a :href="url+'/admin/discipline/show/'+discipline.id">{{ discipline.incident_detail }}</a>
          </td>
          <td>
            <a :href="url+'/admin/teacher/show/'+discipline.teacher_username">{{ discipline.teacher_name }}</a>
          </td>
          <td>{{ discipline.incident_date }}</td>
          <td>
            <p v-if="discipline.attachment != null">
              <a :href="discipline.attachment" target="_blank">
                <svg id="Layer" enable-background="new 0 0 64 64" height="512" viewBox="0 0 64 64" width="512" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current text-black-500 mx-1"><path d="m30.586 45.414c.39.391.902.586 1.414.586s1.023-.195 1.414-.586l10-10c.781-.781.781-2.047 0-2.828s-2.047-.781-2.828 0l-6.586 6.586v-29.172c0-1.104-.896-2-2-2s-2 .896-2 2v29.172l-6.586-6.586c-.78-.781-2.048-.781-2.828 0-.781.781-.781 2.047 0 2.828z"></path><path d="m18 56h28c3.309 0 6-2.691 6-6v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 1.103-.897 2-2 2h-28c-1.103 0-2-.897-2-2v-8c0-1.104-.896-2-2-2s-2 .896-2 2v8c0 3.309 2.691 6 6 6z"></path></svg>
              </a>
            </p>
            <p v-else>--</p>
          </td>
          <td>
            <ul class="list-reset flex">
              <li>
                <a :href="url+'/admin/discipline/edit/'+discipline.id">
                  <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 477.873 477.873" xml:space="preserve" class="w-4 h-4 fill-current text-black-500 mx-1"><g><g><path d="M392.533,238.937c-9.426,0-17.067,7.641-17.067,17.067V426.67c0,9.426-7.641,17.067-17.067,17.067H51.2 c-9.426,0-17.067-7.641-17.067-17.067V85.337c0-9.426,7.641-17.067,17.067-17.067H256c9.426,0,17.067-7.641,17.067-17.067 S265.426,34.137,256,34.137H51.2C22.923,34.137,0,57.06,0,85.337V426.67c0,28.277,22.923,51.2,51.2,51.2h307.2 c28.277,0,51.2-22.923,51.2-51.2V256.003C409.6,246.578,401.959,238.937,392.533,238.937z"></path></g></g> <g><g><path d="M458.742,19.142c-12.254-12.256-28.875-19.14-46.206-19.138c-17.341-0.05-33.979,6.846-46.199,19.149L141.534,243.937 c-1.865,1.879-3.272,4.163-4.113,6.673l-34.133,102.4c-2.979,8.943,1.856,18.607,10.799,21.585 c1.735,0.578,3.552,0.873,5.38,0.875c1.832-0.003,3.653-0.297,5.393-0.87l102.4-34.133c2.515-0.84,4.8-2.254,6.673-4.13 l224.802-224.802C484.25,86.023,484.253,44.657,458.742,19.142z M434.603,87.419L212.736,309.286l-66.287,22.135l22.067-66.202 L390.468,43.353c12.202-12.178,31.967-12.158,44.145,0.044c5.817,5.829,9.095,13.72,9.12,21.955 C443.754,73.631,440.467,81.575,434.603,87.419z"></path></g></g></svg>
                </a>
              </li>
            </ul>
          </td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr>
          <td colspan="5">
            <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
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
    props:['url','name','mode'],
    data () {
      return {
        disciplines:[],
        profile_tab:'',
        errors:[],
        success:null,     
      }
    },
    methods:
    {
      getData()
      {
        axios.get('/'+this.mode+'/student/show/discipline/'+this.name).then(response => {
          this.disciplines = response.data.data;
          //console.log(this.disciplines)
        });
      },
    },
  
    created()
    {    
      this.getData();   
      bus.$on("dataProfileTab", data => {
        if(data!='')
        {
          this.profile_tab=data;                   
        }
      });      
    }
  }
</script>