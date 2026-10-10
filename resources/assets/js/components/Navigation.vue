<template>
    <div class="dashboard-ay" data-testid="academic-year-control">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
        <label :for="fieldId" class="tw-form-label dashboard-ay-label">Academic year</label>
        <select class="tw-form-control dashboard-ay-select"
                :id="fieldId"
                v-model="academic_year"
                name="academic_year"
                aria-label="Academic year"
                @change="showYear()">
            <option v-for="academic in academiclist" :key="academic.id" :value="String(academic.id)">{{ academic.name }}</option>
        </select>
        <span v-if="errors.academic"><p class="text-red-500 text-xs font-semibold">{{ errors.academic[0] }}</p></span>
    </div>
</template>

<script>
	export default {
        props: {
            fieldId: { type: String, default: 'academic_year' },
        },
        data(){
            return{
                academic_year:'',
                academiclist:[],
                errors:[],
                success:null,
            }
        },
        
        methods:
        {
            showYear()
            {
                this.errors=[];
                this.success=null;    

                let formData=new FormData();

                formData.append('academic_year_id',this.academic_year);

                axios.post('/admin/academicyear/index',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },
     
            getAcademicYear()
            {
                axios.get('/admin/list/academicyear').then(response => {
                    this.academiclist = response.data.academiclist ?? [];
                    // A brand new school has no academic year yet, so current_year is
                    // null and dereferencing .id threw a TypeError on every
                    // onboarding screen. Guard the value instead of assuming it.
                    const current = response.data.current_year;
                    this.academic_year = current ? String(current.id) : '';
                });
            },
        },
        created()
        {
            this.getAcademicYear();
        }
    }
</script>