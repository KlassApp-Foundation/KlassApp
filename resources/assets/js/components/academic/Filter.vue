<template>
  <div>
    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
      <div class="my-4 filter-alphabet" > <!-- style="max-width: calc(100vw - 231px)" -->
        <ul class="list-reset flex flex-wrap">
          <li v-for="standardlink in standardlinklist">
            <a :href="url+'/'+type+'/standardLink/show/'+standardlink['id']"  id="filter" class="block font-bold p-2 bg-grey-light border border-grey mx-2 ni">{{ standardlink['standard_section'] }}</a> 
          </li>
        </ul>
      </div>
  </div>
</template>

<script>
  export default {
    props:['url','type'],
    data(){
      return{
        standardlinklist: [],
        selectedStandard: undefined,
        errors:[],
        success:null,
      }
    },

    created() 
    {
      if(this.type == 'admin')
      {
        axios.get('/admin/standardLink/list').then(response => {
         this.standardlinklist = response.data.standardlink;
          //console.log(this.standardlink);
        });
      }
      else if(this.type == 'teacher')
      {
        axios.get('/teacher/standardLink/list').then(response => {
         this.standardlinklist = response.data.data;
          //console.log(this.standardlinklist);
        });
      }
    },
  }
</script>