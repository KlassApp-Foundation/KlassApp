<template>
    <div class="relative">
        <div class="modal-body">
            <div class="flex flex-col lg:flex-row w-full lg:w-full">
                <div class="tw-form-group w-full lg:w-full">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="attachment" class="tw-form-label">Attachment</label>
                        </div>
                        <div class="mb-2">
                            <vue-dropzone ref="myVueDropzone" id="dropzone" :options="dropzoneOptions"v-on:vdropzone-success="successMsg"></vue-dropzone>
                            <a href="#" class="btn btn-reset reset-btn" @click="removeAllFiles()">Remove All Files</a> 
                        </div>
                        <span v-if="errors.attachment" class="text-red-500 text-xs font-semibold">{{ errors.attachment[0] }}</span>
                    </div> 
                </div>
            </div>

            <div class="my-6">
                <a href="#" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitForm()">Submit</a>
            </div>
        </div>
    </div>
</template>

<script>
    import vue2Dropzone from 'vue2-dropzone'
    import 'vue2-dropzone/dist/vue2Dropzone.min.css'
    import { bus } from "../../../app";
    export default {
        props:['url' , 'id' , 'mode'],
        components:{ 
            vueDropzone: vue2Dropzone,
        },
        data () {
            return {
                errors:[],
                success:null, 
                dropzoneOptions: {
                    url: this.url+'/'+this.mode+'/homework/add/'+this.id,
                    method:'post',
                    headers: {
                        "X-CSRF-TOKEN": document.head.querySelector("[name=csrf-token]").content
                    },
                    addRemoveLinks:"true",
                    maxFilesize: 8,
                    paramName: "file", // The name that will be used to transfer the file
                    parallelUploads: 6,
                    maxFiles:6,
                    uploadMultiple: true,
                    acceptedFiles: ".jpg,.jpeg,.png",
                    autoProcessQueue: false,
                    maxThumbnailFilesize:2,
                },
                submitUrl:'',
            }
        },

        methods:
        {
            submitForm() 
            { 
                this.$refs.myVueDropzone.processQueue();
            },

            removeAllFiles() 
            {
                this.$refs.myVueDropzone.removeAllFiles();
            },

            successMsg(file, response)
            {
                bus.$emit('success',response);
            },
        },
  
        created()
        {   
            //
        }
    }
</script>
