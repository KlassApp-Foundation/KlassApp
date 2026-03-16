<template>
  <div class="">
    <div>
	    <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
<div class="flex flex-wrap">
<div class="w-full lg:w-3/4">
      <div class="my-5">
        <div class="tw-form-group">
          <div class="lg:mr-8 md:mr-8 flex items-center w-full">
            <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
              <label for="type" class="tw-form-label">Notice To</label>
            </div>
            <div class="mb-2 w-full lg:w-1/2 md:w-1/2">
              <select name="type" id="type" v-model="type" class="tw-form-control w-full" v-on:change="selectClass()">
                <option value="" disabled>Select</option>
                <option v-for="notices in noticelist" v-bind:value="notices.id">{{ notices.name }}</option>
              </select>
              <span v-if="errors.type" class="text-red-500 text-xs font-semibold">{{errors.type[0]}}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="my-5 hidden" id="select">
        <div class="tw-form-group w-full">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:flex-row w-full">
            <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
              <label for="standardLink_id" class="tw-form-label">Select Class</label>
            </div>
            <div class="mb-2 w-full lg:w-1/2 md:w-1/2">
              <select name="standardLink_id" id="standardLink_id" v-model="standardLink_id" class="tw-form-control w-full">
                <option v-for="standardLink in standardLinklist" v-bind:value="standardLink.id">{{ standardLink.standard_name }} - {{ standardLink.section_name }}</option>
              </select>
               <span v-if="errors.standardLink_id" class="text-red-500 text-xs font-semibold">{{errors.standardLink_id[0]}}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="my-5">
        <div class="tw-form-group w-full">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:flex-row w-full">
            <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
              <label for="title" class="tw-form-label">Title</label>
            </div>
            <div class="mb-2 w-full lg:w-1/2 md:w-1/2">
              <input type="text" name="title" id="title" v-model="title" class="tw-form-control w-full" @keyup='remaincharCount(60)' maxlength="60">
              <span v-if="errors.title" class="text-red-500 text-xs font-semibold">{{errors.title[0]}}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="my-5">
        <div class="tw-form-group w-full">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:flex-row w-full">
            <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
              <label class="tw-form-label">Publish Date</label>
            </div>
            <div class="mb-2 w-full lg:w-1/2 md:w-1/2">
              <datetime format="DD-MM-YYYY h:i:s" name="publish_date" v-model="publish_date" class="rounded w-full" id="publish_date"></datetime>
              <span v-if="errors.publish_date" class="text-red-500 text-xs font-semibold">{{errors.publish_date[0]}}</span>
            </div>
            <div class="mb-2 mx-2">
             <svg id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current text-gray-600"><g><path d="m144 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m144 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m144 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m467 65h-36v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-36c-24.813 0-45 20.187-45 45v332c0 24.813 20.187 45 45 45h422c24.813 0 45-20.187 45-45 0-9.682 0-323.575 0-332 0-24.813-20.187-45-45-45zm-437 45c0-8.271 6.729-15 15-15h36v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h36c8.271 0 15 6.729 15 15v59h-452zm437 347h-422c-8.271 0-15-6.729-15-15v-243h452v243c0 8.271-6.729 15-15 15z"></path></g></svg>
            </div>
          </div>
        </div>
      </div>
  
      <div class="my-5">  
        <div class="tw-form-group w-full">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:flex-row w-full">
            <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
              <label class="input-group-addon tw-form-label">Expire Date</label>
            </div>
            <div class="mb-2 w-full lg:w-1/2 md:w-1/2">
              <datetime format="DD-MM-YYYY h:i:s"  v-model="expire_date" class="w-full rounded" id="expire_date"></datetime>
              <span v-if="errors.expire_date" class="text-red-500 text-xs font-semibold">{{errors.expire_date[0]}}</span> 
            </div>
            <div class="mb-2 mx-2">
              <svg id="Capa_1" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 fill-current text-gray-600"><g><path d="m144 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m144 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m144 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m272 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 249h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 313h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m400 377h-32c-8.284 0-15 6.716-15 15s6.716 15 15 15h32c8.284 0 15-6.716 15-15s-6.716-15-15-15z"></path><path d="m467 65h-36v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-130v-25c0-8.284-6.716-15-15-15s-15 6.716-15 15v25h-36c-24.813 0-45 20.187-45 45v332c0 24.813 20.187 45 45 45h422c24.813 0 45-20.187 45-45 0-9.682 0-323.575 0-332 0-24.813-20.187-45-45-45zm-437 45c0-8.271 6.729-15 15-15h36v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h130v25c0 8.284 6.716 15 15 15s15-6.716 15-15v-25h36c8.271 0 15 6.729 15 15v59h-452zm437 347h-422c-8.271 0-15-6.729-15-15v-243h452v243c0 8.271-6.729 15-15 15z"></path></g></svg>
            </div>
          </div>
        </div>
      </div>
  	
      <div class="my-5">
        <div class="tw-form-group w-full">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:flex-row w-full">
            <div class="mb-2 w-full lg:w-1/4 md:w-1/4">
              <label for="description" class="tw-form-label">Description</label>
            </div>
            <div class="mb-2 w-full lg:w-1/2 md:w-1/2">
             <!--  <textarea type="text" name="description" id="description" v-model="description" class="tw-form-control w-full" rows="3" @keyup='remaincharCount(255)' maxlength="255"></textarea> -->
             <quill-editor ref="myQuillEditor" v-model="description" :options="editorOption"/>
              <span v-if="errors.description" class="text-red-500 text-xs font-semibold">{{errors.description[0]}}</span>
            </div>
          </div>
        </div>
      </div>


      <div class="noticeboard-option w-full">
                <div class="py-2">
                    <label class="tw-form-label">Select Background style</label>
                    <ul class="list-reset py-3 flex flex-wrap">                                     
                         <li v-for="images,index in backgroundimages" class="m-1" 
                                  v-if="index<5" >
                                    <a  href="#" class="w-full">
                                    <div v-if="images!=''" class="w-full" v-bind:class="[activeImg==index?'active':'']">
                                      <img @click="showimg($event,index,images.id)":src="images.image" class="w-20 h-20 rounded">
                                    </div>
                                    </a>
                        </li>                            
                        <li class="m-1">
                        <div class="px-2 py-1 border rounded text-sm flex items-center justify-center w-20 h-20">
                        <a href="#" @click="moreimage()"  class="text-blue-dark no-underline" >See more</a>
                        </div>
                        </li>      
                      </ul>
              </div>
              <div class="flex items-center mt-3">                  
                       <div class="my-1"><a  href="#" @click="addimage()" class="no-underline text-white px-4 my-3 mx-1 custom-green py-1">Add new image</a></div>
                       </div>
            </div>



      <div class="my-5">
        <div class="tw-form-group w-full">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:flex-row w-full">
            <div class="w-full w-full lg:w-1/4 md:w-1/4">
              <label for="attachment_file" v-model="attachment_file" class="tw-form-label">Attachment</label>
              <span class="font-semibold text-xs text-gray-700">(PDF only)</span>
            </div>
            <div class="mb-2 w-full lg:w-1/2 md:w-1/2">
              <input type="file" name="attachment_file" @change="OnFileSelected" id="attachment_file" class="tw-form-control w-full">
              <span v-if="errors.attachment_file" class="text-red-500 text-xs font-semibold">{{errors.attachment_file[0]}}</span>
            </div>
            <a :href="this.attachment" target="_blank" v-if="this.attachment != '' "><span class="mx-2">Click to Open</span></a>
          </div>
        </div>
      </div>

</div>
<!-- start -->
     <div class="p-3  w-full lg:w-1/4 noticeborad-background relative" style="max-width: 300px;">   <!--  max-w-sm shadow-md -->
     <div class="mobile-frame">
       
     </div>  
    <!--Card 1-->
    <div class="" >
       <div class="noticeboard-content" :style="{ 'background-image': 'url(' + imageData + ')' ,'background-size':'100% 100%'}"></div>
         
      <div class="px-4 py-2" style="position: absolute;top: 68px;left: 38px;">
        <div >
          <p v-if="this.title!=''" class="font-bold text-xl mb-2" style="max-width: 190px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;">{{this.title}}</p>
          <p v-else="" class="font-bold text-xl mb-2" style="max-width: 190px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">Title</p>
        </div>
  

        <p v-if="this.description!=''" class="text-gray-700 text-base" v-html="this.description" style="max-width: 190px;height: 4.5rem;overflow: hidden;text-overflow: ellipsis;font-size:12px;"></p>
         <p v-else="" class="text-gray-700 text-base" >Description
         </p>
      </div>
     <!--  <div class="px-6 pt-4 pb-2">
      </div> -->
    </div>
  </div>

  <!-- end -->
</div>
      <div class="my-6">
        <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="updateCircular()">Update</a>
    		<a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>
      </div>
	  </div>

    

  <!-- Edit topic model -->
       <div v-if="this.editshow == 1" class="modal modal-mask">
      <div class="modal-wrapper px-4">
        <div class="modal-container w-full  max-w-md px-8 mx-auto">
          <div class="modal-header flex justify-between items-center">
            <h2>Edit To Do</h2>
            <button id="close-button" class="modal-default-button text-xl" @click="close()">
              &times;
            </button>
          </div>
          <div class="modal-body">
            <div class="my-5">
        <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
          <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
            <div class="w-full lw-full lg:w-1/4 md:w-1/4">
              <label for="bg_image" v-model="bg_image" class="tw-form-label">Background Image</label>
            </div>
            <div class="mb-2 w-full lg:w-1/2 md:w-1/2">
              <input type="file" name="bg_image" @change="OnFileSelectedImg" id="bg_image" class="tw-form-control w-full">
              <span v-if="errors.bg_image" class="text-red-500 text-xs font-semibold">{{errors.bg_image[0]}}</span>
            </div>
          </div>
        </div>
      </div>
            
            <div class="flex items-center">
              <div class="my-2 w-full lg:w-3/4">
                <input type="button" name="" value="Update" @click="addnewimage()" class="btn btn-submit blue-bg text-white rounded px-3 py-1 cursor-pointer text-sm font-medium">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
<!-- End modal -->
<!-- Edit image model -->
       <div v-if="this.imgpopup == 1" class="modal modal-mask">
      <div class="modal-wrapper px-4">
        <div class="modal-container w-full  max-w-md px-8 mx-auto">
          <div class="modal-header flex justify-between items-center">
            <h2> background images</h2>
            <button id="close-button" class="modal-default-button text-xl" @click="closeimg()">
              &times;
            </button>
            </div>
          <div class="modal-body">
             <ul class="list-reset py-3 flex flex-wrap">                                     
                         <li v-for="images,index in backgroundimages" class="m-1" 
                             >
                                    <a href="#" class="w-full">
                                    <div v-if="images!=''" class="w-full" v-bind:class="[activeImg==index?'active':'']">
                                      <img @click="showimg($event,index,images.id)":src="images.image" class="w-20 h-20 rounded">
                                    </div>
                                    </a>
                        </li>   
                         </ul> 
                         </div>  
          </div>
        </div>
      </div>
    </div>
<!-- End modal -->


  </div>
</template>


<script>

import datetime from 'vuejs-datetimepicker';
  import Vue from 'vue'
  import VueQuillEditor from 'vue-quill-editor'
  import 'quill/dist/quill.core.css' // import styles
  import 'quill/dist/quill.snow.css' // for snow theme
  import 'quill/dist/quill.bubble.css' // for bubble theme
  Vue.use(VueQuillEditor)

export default {

  props:['id','url'],

  components: { datetime },

  data(){
    return{
      standardLinklist:[],
      backgroundimages:[],
      notice:[],
      noticelist:[ { 'id' : 'school' , 'name' : 'School' } , { 'id' : 'class' , 'name' : 'Class' } , { 'id' : 'teacher' , 'name' : 'Teachers' }],
      type:'',
      standardLink_id:'',
      title:'',
      publish_date:'',
      expire_date:'',
      description:'',
      attachment_file:'',
      attachment:'',
      errors:[],
      success:null,
      imageData:'',
      bg_image:'',
      bg_id:'',
      activeImg:'',
      editshow:0,
      imgpopup:0,
      editorOption:{
          theme: 'snow',
          modules: {
            toolbar: {
              container: [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': 1 }, { 'header': 2 }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }], 
                [{ 'size': ['small', false, 'large', 'huge'] }],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],       
                [{ 'color': [] }, { 'background': [] }],
                [{ 'script': 'sub' }, { 'script': 'super' }], 
                [{ 'font': [] }],       
                [{ 'align': [] }],
                ['clean'],  
                ['image'],
                ['video'],
              ],      
            }
          } 
        },
    }
  },
        
  methods:
  {
    getData()
    {
      axios.get('/admin/notice/show/'+this.id).then(response => {
        this.notice= response.data;
        console.log(this.notice)
        this.editCircular();
      });  
    },

    showimg($event,index,imageid)
    {
      //alert(imageid);
       this.activeImg=index;
       this.bg_id=imageid;
       this.imageData=$event.target.src;
      // console.log(this.imageData);

    },

    addimage()
    {
      this.editshow=1;
    },

    close()
    {
       this.editshow=0;
    },

    moreimage()
    {
         this.imgpopup=1;
    },

    closeimg()
    {
         this.imgpopup=0;
    },

    addnewimage()
    {
      //this.errors=[];
      //this.success=null; 

      let formData=new FormData();

      formData.append('bg_image',this.bg_image);          
                     
      axios.post('/admin/notice/background/add',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
        this.success = response.data.success;
        window.location.reload();
      }).catch(error => {
        this.errors = error.response.data.errors;
      });
    },


    editCircular()
    {
      if(Object.keys(this.notice).length>0)
      { 
        this.standardLink_id  = this.notice.standardLink_id; 
        this.type             = this.notice.type;
        this.title            = this.notice.title;
        this.publish_date     = this.notice.publish_date;
        this.expire_date      = this.notice.expire_date;
        this.attachment       = this.notice.attachment_file;
        this.description      = this.notice.description;
        this.standardLinklist = this.notice.standardLinklist;
        this.backgroundimages = this.notice.backgroundimages;
        this.bg_id            = this.notice.bg_id;
        this.imageData        = this.notice.bg_image;

        if(this.standardLink_id == null)
        {
          $('#select').removeClass('block').addClass('hidden');
        }
        else
        {
          $('#select').addClass('block').removeClass('hidden');
        }
      }             
    },

     OnFileSelectedImg(event)
    {
      this.bg_image = event.target.files[0];
    },


    remaincharCount(len)
    {
      var maxLength = len;
      $('textarea').keyup(function() {
        var textlen = maxLength - $(this).val().length+'/'+maxLength;
        $('#rchars').text(textlen);
      });
    },

    selectClass()
    {
      if(this.type == 'class')
      {
        if($('#select').hasClass('hidden'))
        {
          $('#select').removeClass('hidden').addClass('block');
        }
      }
      else
      {
        if($('#select').hasClass('block'))
        {
          $('#select').addClass('hidden').removeClass('block');
        }
      }
    },

    updateCircular()
    {
      this.errors=[];
      this.success=null; 

      let formData=new FormData();
      formData.append('bg_id',this.bg_id);
      formData.append('standardLink_id',this.standardLink_id);
      formData.append('type',this.type);      
      formData.append('title',this.title);      
      formData.append('publish_date',this.publish_date);
      formData.append('expire_date',this.expire_date);      
      formData.append('description',this.description); 
      formData.append('attachment_file',this.attachment_file);
             
      axios.post('/admin/notice/update/'+this.id,formData).then(response => {  
        this.success = response.data.success;
      }).catch(error => {
          this.errors = error.response.data.errors;
      });
    },

    OnFileSelected(event)
    {
      this.attachment_file=event.target.files[0];
      let files = event.target.files || event.dataTransfer.files;
      if (!files.length)
        return;
      this.createImage(files[0]);
    },

    createImage(file) 
    {
      let reader = new FileReader();
      let vm = this;
      reader.onload = (e) => {
        vm.file = e.target.result;
      };
      reader.readAsDataURL(file);
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
 max-height: 550px;
  overflow:auto;
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-body {
  margin: 10px 0;
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
