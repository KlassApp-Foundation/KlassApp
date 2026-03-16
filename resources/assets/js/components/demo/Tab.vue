<template>
    <div class="bg-white shadow border border-grey px-10 py-3 w-full">
        <ul class="list-reset flex text-sm profile-tab flex-wrap">
            <li class="px-2 mx-1 lg:mx-2 md:mx-2 py-2 lg:py-3 md:py-2" v-bind:class="[{'active' : demoTab === school.id}]" v-for="school in schoolList">
                <a href="#" class="text-gray-700 font-medium" @click="setTab(school.id)">{{ school.name }} ( {{ school.board }} )</a>
            </li>
        </ul>
        <div class="flex flex-wrap">
            <div class="flex flex-col">
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Admin</b></li>
                    <li class="text-sm py-1" v-for="admin in list.admin">
                        <p>Username : <span class="text-gray-600"> {{ admin.email }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Principal</b></li>
                    <li class="text-sm py-1" v-for="principal in list.principal">
                        <p>Username : <span class="text-gray-600"> {{ principal.email }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Librarian</b></li>
                    <li class="text-sm py-1" v-for="librarian in list.librarian">
                        <p>Username : <span class="text-gray-600"> {{ librarian.email }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Receptionist</b></li>
                    <li class="text-sm py-1" v-for="receptionist in list.receptionist">
                        <p>Username : <span class="text-gray-600"> {{ receptionist.email }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Accountant</b></li>
                    <li class="text-sm py-1" v-for="accountant in list.accountant">
                        <p>Username : <span class="text-gray-600"> {{ accountant.email }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
            </div>
            <div class="flex flex-col">
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Teacher</b></li>
                    <li class="text-sm py-1" v-for="teacher in list.teacher">
                        <p>Username : <span class="text-gray-600"> {{ teacher.email }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Teacher</b> (For App)</li>
                    <li class="text-sm py-1" v-for="teacher in list.teacher">
                        <p>Username : <span class="text-gray-600"> {{ teacher.mobile_no }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
            </div>
            <div class="flex flex-col">
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Student</b></li>
                    <li class="text-sm py-1" v-for="student in list.student">
                        <p>Username : <span class="text-gray-600"> {{ student.email }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
                <ul class="px-2 py-2">
                    <li class="text-gray-800 text-sm"><b>Parent</b> (For App)</li>
                    <li class="text-sm py-1" v-for="parent in list.parent">
                        <p>Username : <span class="text-gray-600"> {{ parent.mobile_no }} </span></p>
                        <p>Password : <span class="text-gray-600"> password </span></p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
    import PortalVue from "portal-vue";
    import { bus } from "../../app";
    export default{
        props:['url'],
        data(){
            return{
                schoolList:[],
                list:[],
                demoTab:1,
                success:null,
                erros:[],
            }
        },

        methods:
        {
            getData()
            {
                axios.get('/demo/schoolList').then(response => {
                    this.schoolList = response.data.data;
                    //console.log(this.schoolList);
                });
            },

            getList(id)
            {
                axios.get('/demo/list/'+id).then(response => {
                    this.list = response.data;
                    //console.log(this.list);
                });
            },

            setTab(val)
            {
                bus.$emit('demoTab',val);
                this.getList(val);
            },
        },

        created()
        {
            this.getData();
            this.getList(this.demoTab);
            bus.$emit('demoTab',this.demoTab);

            bus.$on('demoTab',data => {
                if(data != '')
                {
                    this.demoTab = data;
                }
            });
        }
    }
</script>