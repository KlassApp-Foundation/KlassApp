<template>
    <div class="relative">
        <div v-if="this.success!=null" class="alert alert-success mt-2" id="success-alert">{{this.success}}</div>
        <div class="flex flex-wrap lg:flex-row justify-between items-center">
            <div class="relative flex items-center justify-end">
                <div class="flex items-center">
                    <a class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center cursor-pointer" @click="showModal()">
                        <svg class="w-5 h-5 fill-current text-green-100" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M256,0C114.833,0,0,114.833,0,256s114.833,256,256,256s256-114.853,256-256S397.167,0,256,0z M256,472.341 c-119.275,0-216.341-97.046-216.341-216.341S136.725,39.659,256,39.659S472.341,136.705,472.341,256S375.295,472.341,256,472.341z"/></g></g><g><g><path d="M355.148,234.386H275.83v-79.318c0-10.946-8.864-19.83-19.83-19.83s-19.83,8.884-19.83,19.83v79.318h-79.318 c-10.966,0-19.83,8.884-19.83,19.83s8.864,19.83,19.83,19.83h79.318v79.318c0,10.946,8.864,19.83,19.83,19.83 s19.83-8.884,19.83-19.83v-79.318h79.318c10.966,0,19.83-8.884,19.83-19.83S366.114,234.386,355.148,234.386z"/></g></g></svg>
                    </a> 
                </div>
            </div>
        </div>

        <div v-if="this.show == 1" class="modal modal-mask">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full  max-w-md px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>Create To Do</h2>
                        <button id="close-button" class="modal-default-button text-xl" @click="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="flex flex-col">
                            <div class="w-full lg:w-1/4"> 
                                <label for="to_do_list" class="tw-form-label">Description</label>
                            </div>
                            <div class="my-2 w-full">
                                <textarea type="text" name="to_do_list" v-model="to_do_list" class="tw-form-control w-full"></textarea><br>
                                <span v-if="errors.to_do_list"><p class="text-red-500 text-xs font-semibold">{{errors.to_do_list[0]}}</p></span>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <div class="w-full flex"> 
                                <label for="task_date" class="tw-form-label">When</label>
                            </div>
                            <div class="my-2">
                                <input type="date" name="task_date" v-model="task_date" class="tw-form-control w-full" id="task_date">
                                <span v-if="errors.task_date"><p class="text-red-500 text-xs font-semibold">{{errors.task_date[0]}}</p></span>
                            </div>
                        </div>
            
                        <div class="flex items-center">
                            <div class="my-2 w-full lg:w-3/4">
                                <input type="button" name=""  value="Create" class="btn btn-submit blue-bg text-white rounded px-3 py-1 cursor-pointer text-sm font-medium" @click="addtask()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

export default {
  props:['url','searchquery','mode'],
  data () {
    return {
      task:[],
      edit:[],
      to_do_list:'',
      task_date:'',
      edittask:'',
      show:0,
      editshow:0,
      errors:[],
      errors1:[],
      success:null,
     
    }
  },

  methods:
  {
    getlist()
    {
      axios.get('/teacher/task/list').then(response => {
        this.task = response.data.data;
        //console.log(this.topics);    
      });
    },

    addtask()
    {
      axios.post('/teacher/task/add',{
        to_do_list:this.to_do_list,
        task_date:this.task_date,
      
      }).then(response => {
        this.success = response.data.message;
        this.closeModal();
        window.location.reload();
        this.reset();
      }).catch(error=>{
        this.errors=error.response.data.errors;
      }); 
    },



    reset()
    {
      this.to_do_list='';
      this.task_date='';
     
    },
   
            showModal()
            {
              this.show = 1;
              this.reset();
            },
    
            closeModal()
            {
              this.show = 0;
            },

            close()
            {
                this.editshow=0;
                this.resetedit();
            }
        },
        created()
        {   
            //
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