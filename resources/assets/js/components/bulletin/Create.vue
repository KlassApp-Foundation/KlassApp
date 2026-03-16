<template>
    <div class="">
        <div class="bulletin shadow px-4 py-1 bg-white" v-if="parseInt(this.count) <= parseInt(this.no_of_bulletins)">
            <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
            <div class="my-5">
                <div class="">
                    <div class="w-full lg:w-1/4">
                        <label for="name" class="tw-form-label">Magazine Name</label>
                    </div>
                    <div class="w-full lg:w-2/5 my-2">
                        <input type="text" class="tw-form-control w-full" placeholder="Enter name of Magazine" :maxlength="max" id="name" v-model="name">
                        <div class="text-gray-700 text-xs my-1" v-text="(max - name.length)+'/'+max" style="text-align: right"></div>             
                    </div>
                    <span v-if="errors.name" class="text-red-500 text-xs font-semibold">{{ errors.name[0] }}</span>
                </div>
            </div>

            <div class="my-5">
                <div class="">
                    <div class="w-full lg:w-1/4"> 
                        <label for="year" class="tw-form-label">Select Year</label>
                    </div>
                    <div class="my-2 w-full lg:w-2/5">
                        <select name="year" v-model="year" id="year" class="tw-form-control w-full">
                            <option value="" disabled="disabled">Select Year</option>
                            <option v-for="i in range(start,end)" v-bind:value="i">{{ i }}</option>
                        </select>
                        <span v-if="errors.year" class="text-red-500 text-xs font-semibold">{{ errors.year[0] }}</span>
                    </div>
                </div>
            </div>

            <div class="my-5">
                <div class="">
                    <div class="w-full lg:w-1/4">
                        <label for="cover_image" v-model="cover_image" class="tw-form-label">Upload Cover Image</label>
                    </div>
                    <div class="w-full lg:w-2/5 my-2">
                        <input type="file" name="cover_image" @change="OnImageSelected" id="cover_image" class="tw-form-control w-full">
                    </div>
                    <span v-if="errors.cover_image" class="text-red-500 text-xs font-semibold">{{errors.cover_image[0]}}</span>
                </div>
            </div>

            <div class="my-5">
                <div class="">
                    <div class="w-full lg:w-1/4">
                        <label for="bulletin_file" v-model="bulletin_file" class="tw-form-label">Upload Magazine File</label>
                    </div>
                    <div class="w-full lg:w-2/5 my-2">
                        <input type="file" name="bulletin_file" @change="OnFileSelected" id="bulletin_file" class="tw-form-control w-full">
                    </div>
                    <span v-if="errors.bulletin_file" class="text-red-500 text-xs font-semibold">{{ errors.bulletin_file[0] }}</span>
                </div>
            </div>

            <div class="my-6">
                <a href="#" dusk="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="checkForm()">Upload</a>
                <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="resetForm()">Reset</a>
            </div>
        </div>

        <div v-if="parseInt(this.count) > parseInt(this.no_of_bulletins)">
            <a href="/pricing"> 
                <button type="submit" class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center">Upgrade Plan to Add More Magazines</button>
            </a>
        </div>
    </div>
</template>

<script>
    export default {
        props:['count','no_of_bulletins'],
        data(){
            return{
                bulletin:[],
                academic_year_id:'',
                year:'',
                start:'',
                end:'',
                bulletin_file:'',
                cover_image:'',
                max: 15,
                name:'',
                errors:[],
                success:null,
            }
        },
        
        methods:
        {
            resetForm()
            {
                window.location.reload();     
            }, 

            checkForm()
            {
                this.errors=[];
                this.success=null; 

                let formData=new FormData();

                formData.append('name',this.name);                
                formData.append('year',this.year);                   
                formData.append('cover_image',this.cover_image);                   
                formData.append('bulletin_file',this.bulletin_file);          
              
                axios.post('/admin/magazine/create',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                    this.resetForm();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            OnFileSelected(event)
            {
                this.bulletin_file = event.target.files[0];
            },

            OnImageSelected(event)
            {
                this.cover_image = event.target.files[0];
            },

            getData()
            {
                axios.get('/admin/magazine/getDate').then(response => {
                    this.bulletin = response.data;
                    this.setData();   
                });
            },

            setData()
            {
                if(Object.keys(this.bulletin).length>0)
                {
                    this.academic_year_id = this.bulletin.academic_year_id;
                    if(this.academic_year_id == null)
                    {
                        alert("Add Academic Year")
                    }
                    else
                    {
                        this.start  = parseInt(this.bulletin.start);
                        this.end    = parseInt(this.bulletin.end);
                    }
                }
            },

            range(max,min)
            {
                var array = [],
                j = 0;
                for(var i = max; i >= min; i--)
                {
                    array[j] = i;
                    j++;
                }
                return array;
            },
        },
        created()
        {
            this.getData();
        }
    }
</script>