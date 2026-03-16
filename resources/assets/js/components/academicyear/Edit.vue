<template>
    <div>
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{ this.success }}</div>
        <div class="bg-white shadow px-4 py-3">
            <div class="flex">
                <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="description" class="tw-form-label">Description</label>
                        </div>
                        <div class="mb-2">
                            <textarea type="text" name="description" v-model="description" id="description" class="tw-form-control w-full" placeholder="Description" rows="3"></textarea>
                        </div>
                        <span v-if="errors.description" class="text-red-500 text-xs font-semibold">{{ errors.description[0] }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row md:flex-row w-full lg:w-1/2"> 
                <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="start_date" class="tw-form-label">Start Date<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2">
                            <input type="date" class="tw-form-control w-full" name="start_date" v-model="start_date" id="start_date">
                        </div>
                        <span v-if="errors.start_date" class="text-red-500 text-xs font-semibold">{{ errors.start_date[0] }}</span>
                    </div> 
                </div>

                <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="end_date" class="tw-form-label">End Date<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2">
                            <input type="date" class="tw-form-control w-full" name="end_date" v-model="end_date" id="end_date">
                        </div>
                        <span v-if="errors.end_date" class="text-red-500 text-xs font-semibold">{{ errors.end_date[0] }}</span>
                    </div>     
                </div>
            </div>

            <div class="flex flex-col lg:flex-row md:flex-row w-full lg:w-1/2"> 
                <div class="tw-form-group w-full lg:w-1/2 md:w-1/2">
                    <div class="lg:mr-8 md:mr-8">
                        <div class="mb-2">
                            <label for="status" class="tw-form-label">Type<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2">
                            <select name="status" v-model="status" id="status" class="tw-form-control w-full">
                                <option value="">Select Type</option>
                                <option v-for="list in statuslist" :value="list.id">{{ list.name }}</option>
                            </select>
                        </div>
                        <span v-if="errors.status" class="text-red-500 text-xs font-semibold">{{ errors.status[0] }}</span>
                    </div> 
                </div>
            </div>
     
            <div class="my-6">
                <a href="#" dusk="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitForm()">Submit</a>
            </div>
        </div>
    </div>
</template>

<script>
    export default{
        props:['url' , 'id'],
        data(){
            return{
                academicyear:[],
                statuslist:[],
                description:'',
                start_date:'',
                end_date:'',
                status:'',
                success:null,
                errors:[],
            }
        },

        methods:
        {
            getData()
            {
                axios.get('/admin/academic/edit/list/'+this.id).then(response =>{
                    this.academicyear = response.data;
                    this.setData();
                });
            },

            setData()
            {
                if(Object.keys(this.academicyear).length > 0)
                {
                    this.description    = this.academicyear.description;
                    this.start_date     = this.academicyear.start_date;
                    this.end_date       = this.academicyear.end_date;
                    this.status         = this.academicyear.status;
                    this.statuslist     = this.academicyear.statuslist;
                }
            },

            submitForm()
            {
                this.errors=[];
                this.success=null;

                let formData=new FormData(); 

                formData.append('description',this.description);
                formData.append('start_date',this.start_date);
                formData.append('end_date',this.end_date);
                formData.append('status',this.status);

                axios.post('/admin/academic/edit/'+this.id,formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success;
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },
        },

        created()
        {
            this.getData();
        }
    }
</script>