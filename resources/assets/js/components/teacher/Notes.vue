<template>
	<div class=" overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3 bg-white shadow px-3" v-bind:class="[this.profile_tab==3?'block' :'hidden']">
    <div class="flex flex-col lg:flex-row">
      <div class="tw-form-group w-full lg:w-1/2">
        <div class="lg:mr-8 md:mr-8">
          <div class="mb-2">
            <label for="notes" class="tw-form-label">Notes</label>
          </div>
          <div class="mb-2">
            <textarea type="text" class="tw-form-control w-full" v-model="notes" id="notes" name="notes" rows="3"></textarea>
          </div>
          <span v-if="errors.notes" class="text-red-500 text-xs font-semibold">{{errors.notes[0]}}</span>
        </div> 
      </div>
    </div>

    <div class="my-6">
      <a href="#" dusk="submit-btn" class=" btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="previous(2)">Previous</a>
      <a href="#" dusk="submit-btn" class="btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="submitForm('4')" v-if="this.type == 'add'">Submit</a>
      <a href="#" dusk="submit-btn" class="btn-primary submit-btn blue-bg text-sm text-white px-2 py-1 rounded mx-1" @click="updateForm('4')" v-else>Submit</a>
      <a href="#" class="btn-reset reset-btn" @click="resetForm()">Reset</a>
    </div>
	</div>
</template>

<script> 
  import { bus } from "../../app";
  import PortalVue from "portal-vue";
  export default {
    props:['type' , 'teacher_name'],
    data(){
      return {
        profile_tab:'',
        notes:'',
        errors:[],
        success:null,
      }
    },

    methods:
    {
      getData(getUrl)
      {
        axios.get(getUrl).then(response => {
          this.user = response.data;
          this.setData();   
        });
      },

      setData()
      {
        if(Object.keys(this.user).length>0)
        {
          if(this.type == 'edit')
          {
            this.notes  = this.user.notes;
          }
        }
      },

      resetForm()
      {
        this.notes='';
      }, 

      setProfileTab(val)
      {
        this.profile_tab=val;
        bus.$emit("dataProfileTab", this.profile_tab);
      },

      previous(tab)
      {
        //this.profile_tab=val;
        bus.$emit("dataProfileTab", tab);

      } ,

      submitForm(val)
      {
        this.errors=[];
        this.success=null; 

        let formData =  new FormData(); 

        formData.append('notes',this.notes);

        axios.post('/admin/teacher/add/validationNote',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {  
          this.setProfileTab(val);
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },

      updateForm(val)
      {
        this.errors=[];
        this.success=null; 

        let formData =  new FormData(); 

        formData.append('notes',this.notes);

        axios.post('/admin/teacher/edit/validationNote/'+this.teacher_name,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {  
          this.setProfileTab(val);
        }).catch(error => {
          this.errors = error.response.data.errors;
        });
      },
    },
    
    created()
    { 
      if(this.type == 'edit')
      {
        this.getData('/admin/teacher/editTeacher/'+this.teacher_name);
      }

      bus.$on("dataProfileTab", data => {
        if(data!='')
        {
          this.profile_tab=data;                   
        }
      });
    }
  }
</script>