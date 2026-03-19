<template>
    <div class="bg-white shadow px-4 py-3">
        <div>
            <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
            <div class="flex flex-col lg:flex-row md:flex-row items-end">
                <div class="w-full lg:w-1/3 md:w-1/3">
                    <div class="tw-form-group w-full lg:w-3/4 md:w-3/4">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label for="application_status" class="tw-form-label">Application Status</label>
                            </div>
                            <div class="mb-2">
                                <select class="tw-form-control w-full" id="application_status" v-model="application_status" name="application_status">
                                    <option value="" disabled>Application Status</option>
                                    <option v-for="status in statuslist" v-bind:value="status.id">{{ status.name }}</option>
                                </select>
                            </div>
                        </div>     
                    </div>

                    <div class="tw-form-group w-full lg:w-3/4 md:w-3/4" v-if="this.application_status=='Approved'">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label for="section_id" class="tw-form-label">Section</label>
                            </div>
                            <div class="mb-2">
                                <select class="tw-form-control w-full" id="section_id" v-model="section_id" name="section_id">
                                    <option value="" disabled>Select Section</option>
                                    <option v-for="section in sectionlist" v-bind:value="section.id">{{ section.name }}</option>
                                </select>
                            </div>
                            <span v-if="errors.section_id" class="text-red-500 text-xs font-semibold">{{ errors.section_id[0] }}</span>
                        </div>     
                    </div>

                    <div class="tw-form-group w-full lg:w-3/4 md:w-3/4" v-if="this.application_status=='Approved'">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label for="fee_group_id" class="tw-form-label">Fee Type</label>
                            </div>
                            <div class="mb-2">
                                <select class="tw-form-control w-full" id="fee_group_id" v-model="fee_group_id" name="fee_group_id">
                                    <option value="" disabled>Select Fee Type</option>
                                    <option v-for="fees in  feelist" v-bind:value="fees.id">{{ fees.name }}</option>
                                </select>
                            </div>
                            <span v-if="errors.fee_group_id" class="text-red-500 text-xs font-semibold">{{ errors.fee_group_id[0] }}</span>
                        </div>     
                    </div>

                    <div class="tw-form-group w-full lg:w-3/4 md:w-3/4" v-if="this.application_status=='Approved'">
                        <div class="lg:mr-8 md:mr-8">
                            <div class="mb-2">
                                <label for="payment_status" class="tw-form-label">Admission Fee</label>
                            </div>
                            <div class="mb-2">
                                <select class="tw-form-control w-full" id="payment_status" v-model="payment_status" name="payment_status">
                                    <option value="" disabled>Payment Status</option>
                                    <option v-for="list in paymentlist" v-bind:value="list.id">{{ list.name }}</option>
                                </select>
                            </div>
                            <span v-if="errors.payment_status" class="text-red-500 text-xs font-semibold">{{ errors.payment_status[0] }}</span>
                        </div>     
                    </div>
                </div>
            </div>
        </div>

        <div class="my-6">
            <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="submitForm()">Submit</a>
            <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="resetForm()">Reset</a>  
        </div>
    </div>
</template>


<script>
    export default {
        props:['id','url'],
        data(){
            return{
                admission:[],
                sectionlist:[],
                feelist:[],
                application_status:'',
                section_id:'',
                fee_group_id:'',
                payment_status:'',
                statuslist:[{id:'Draft' , name:'Draft'} , {id:'Pending' , name:'Pending'} , {id:'Rejected' , name:'Rejected'} , {id:'Approved' , name:'Approved'}],
                paymentlist:[{id:'paid' , name:'Paid'} , {id:'not_paid' , name:'Not Paid'}],
                errors:[],
                success:null,
            }
        },
        
        methods:
        {
            getData()
            {
                axios.get(this.url+'/admin/admission/show/'+this.id).then(response => {
                    this.application_status=response.data.application_status;  
                    this.sectionlist=response.data.sectionlist;    
                    this.feelist=response.data.feelist;                 
                });             
            },

            resetForm()
            {
                window.location.reload();
            },

            submitForm()
            {
                this.errors=[];
                this.success=null; 

                let formData=new FormData();

                formData.append('application_status',this.application_status);                 
                formData.append('section_id',this.section_id);                 
                formData.append('payment_status',this.payment_status);                 
                formData.append('fee_group_id',this.fee_group_id);                 
                                 
                axios.post(this.url+'/admin/admission/update/'+this.id,formData).then(response => {   
                    this.success = response.data.success;
                    this.resetForm();
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