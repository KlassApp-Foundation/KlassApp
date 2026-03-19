<template>
  <div class="">
    <h1 class="admin-h1 my-3">Change Avatar</h1>
    <div class="bg-white shadow border border-grey p-5">
      <div v-if="this.success!=null" class="alert alert-success font-muller text-hayvn-purple" id="success-alert">{{this.success}}</div>
      <div class="flex flex-wrap">
        <div class="w-24 mr-4" v-if="image">
          <img :src="image" class="img-responsive" height="70" width="90">
        </div>
      </div>
      <div class="row">
        <div class="my-3">
          <div v-if="this.avatar != ''">
            <img :src="avatar" style="width: 100px;height: 100px">
          </div>
          <VueImageUploadCroppie :defaultImage.sync="avatar" :height="100" :width="100" :trans="trans"></VueImageUploadCroppie>
          <span v-if="errors.avatar" class="text-red-500 text-xs font-semibold">{{errors.avatar}}</span>
        </div>
        <div class="flex">
          <a href="#" class="submit-btn" @click.prevent="uploadImage()">Upload</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
	import axios from 'axios';
  import VueImageUploadCroppi from 'vue-image-upload-croppie'
  export default {
    props:['url'],
    components: 
    { 
      VueImageUploadCroppi,
    },
    data(){
      return {
        image: '',
        avatar:'',
        trans: { 
          'cropImage': 'Choose File', 
          'chooseImage':'Choose File', 
          'confirmCutting': 'Save File'
        },
        errors:[],
        success:null,
      }
    },
    methods: 
    {
      getdata()
      {
        axios.get('/teacher/getavatar').then(response => {
          this.avatar = response.data.avatar;
        });
      },

      uploadImage()
      {
        this.errors=[];
        if(this.avatar==null) this.errors["avatar"] = "Required";
        let formData = new FormData();

        formData.append('avatar',this.avatar);

        axios.post('/teacher/changeavatar',formData).then(response => {
          this.success = response.data.message;
          window.location.reload();
		    }).catch(error => {
		      this.errors = error.response.data.errors;
		    });
      }
    },
    created()
    {
      this.getdata(); 
    },     
  }
</script>

<style>
  .new
  {
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
    position: absolute;
    z-index: -1;
  }
</style>