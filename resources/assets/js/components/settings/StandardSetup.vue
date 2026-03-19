<template>
    <div class="bg-white shadow px-4">
        <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>

        <div class="flex flex-col lg:flex-row">
            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2"> 
                        <label for="board" class="tw-form-label">Board Of Education<span class="text-red-500">*</span></label>
                    </div>
                    <div class="w-full lg:w-3/4 my-2">
                        <select name="board" v-model="board" id="board" class="tw-form-control w-full">
                            <option value="" disabled="disabled">Select Board</option>
                            <option v-for="boards in boardlist" v-bind:value="boards.id">{{ boards.name }}</option>
                        </select>
                        <span v-if="errors.board" class="text-red-500 text-xs font-semibold">{{errors.board[0]}}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row" v-if="this.board != ''">
            <div class="tw-form-group w-full lg:w-1/2">
                <div class="lg:mr-8 md:mr-8">
                    <div class="mb-2"> 
                        <label for="standards" class="tw-form-label">Select Highest Standard<span class="text-red-500">*</span></label>
                    </div>
                    <div class="w-full lg:w-full my-2">
                        <input type="radio" value="nursery" id="nursery" name="standards" v-model="standards">
                        <span class="text-sm mx-1">Nursery</span>
                        <input type="radio" value="primary" id="primary" name="standards" v-model="standards">
                        <span class="text-sm mx-1">Primary</span>
                        <input type="radio" value="secondary" id="secondary" name="standards" v-model="standards">
                        <span class="text-sm mx-1">Secondary</span>
                        <input type="radio" value="higher_secondary" id="higher_secondary" name="standards" v-model="standards">
                        <span class="text-sm mx-1">Higher Secondary</span>
                        <span v-if="errors.standards" class="text-red-500 text-xs font-semibold">{{errors.standards[0]}}</span>
                    </div>
                </div>
            </div>
        </div>
     
        <div class="py-3">
            <a href="#" dusk="submit-btn" class="btn btn-primary submit-btn" @click="addStandardLink()">Submit</a>
        </div>
    </div>
</template>

<script> 
    export default {
        props:['url' , 'academic_year_id'],
        data(){
            return {
                list:[],
                board:'',
                standards:'',
                boardlist:[ {id:'state_board' , name:'State Board'} , {id:'matric' , name:'Matriculation'} , {id:'cbse' , name:'CBSE'} , {id:'icse' , name:'ICSE'} , {id:'ib' , name:'IB'} ],
                errors:[],
                success:null,
            }
        },

        methods:
        {
            addStandardLink()
            {
                this.errors=[];
                this.success=null;

                let formData=new FormData(); 

                formData.append('board',this.board);
                formData.append('standards',this.standards);

                axios.post('/admin/standard/create',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => {     
                    this.success = response.data.success; 
                    //window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            getData()
            {
                if(this.academic_year_id == null)
                {
                    alert("Add Academic Year")
                    window.location = '/admin/academic/add';
                }
            },
        },
    
        created()
        {
            this.getData();
        }
    }
</script>