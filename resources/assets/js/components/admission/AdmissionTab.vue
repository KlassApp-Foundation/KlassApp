<template>
    <div>
        <ul id="progressbar" class="w-full lg:w-9/12 mx-auto">
            <li class="active" v-bind:class="[{'active' : profile_tab === '1'}]">
                <!-- <a href="#" @click="setProfileTab('1')" class="text-gray-700 font-medium">Standard Detail</a> -->
                <a href="#" class="text-gray-700 font-medium">Standard Detail</a>
            </li>

            <li class="active" v-bind:class="[{'active' : profile_tab === '2'}]">
                <!-- <a href="#" @click="setProfileTab('2')" class="text-gray-700 font-medium">Student Detail</a> -->
                <a href="#" class="text-gray-700 font-medium">Student Detail</a>
            </li>

            <li class="active" v-bind:class="[{'active' : profile_tab === '3'}]">
                <!-- <a href="#" @click="setProfileTab('3')" class="text-gray-700 font-medium">Academic Detail</a> -->
                <a href="#" class="text-gray-700 font-medium">Academic Detail</a>
            </li>

            <li class="active" v-bind:class="[{'active' : profile_tab === '4'}]">
                <!-- <a href="#" @click="setProfileTab('4')" class="text-gray-700 font-medium">Parent Detail</a> -->
                <a href="#" class="text-gray-700 font-medium">Parent Detail</a>
            </li>

            <li class="active" v-bind:class="[{'active' : profile_tab === '5'}]">
                <!-- <a href="#" @click="setProfileTab('5')" class="text-gray-700 font-medium">Personal Detail</a> -->
                <a href="#" class="text-gray-700 font-medium">Personal Detail</a>
            </li>
        </ul>

        <portal to="add_admissionform">
            <select-standard :url="this.url" :slug="this.slug"></select-standard>
            <student-detail :url="this.url" :slug="this.slug"></student-detail>
            <academic-detail :url="this.url" :slug="this.slug"></academic-detail>
            <parent-detail :url="this.url" :slug="this.slug"></parent-detail>
            <personal-detail :url="this.url" :slug="this.slug"></personal-detail>
        </portal>
    </div>
</template>

<script>
    import PortalVue from "portal-vue";
    import { bus } from "../../app";
    import selectStandard from './SelectStandard';
    import studentDetail from './StudentDetail';
    import academicDetail from './AcademicDetail';
    import parentDetail from './ParentDetail';
    import personalDetail from './PersonalDetail';

    export default {
        props:['url','slug'],
        data () {
            return {
                profile_tab:'1',     
            }
        },

        components: {
            selectStandard,
            studentDetail,
            academicDetail,
            parentDetail,
            personalDetail, 
        },

        methods:
        {
            setProfileTab(val)
            {
                this.profile_tab=val;
                bus.$emit("dataAdmissionTab", this.profile_tab);
            }
        },

        created()
        {
            bus.$emit("dataAdmissionTab", this.profile_tab);
       
            bus.$on("dataAdmissionTab", data => {
                if(data!='')
                {
                    this.profile_tab=data;                   
                }
            });  
        }
    }
</script>