<template>
     <div class="overflow-x-scroll lg:overflow-x-auto md:overflow-x-auto py-3" v-bind:class="[this.profile_tab== 11 ?'block' :'hidden']">
    Add on required
   </div>
</template>

<script>
        import { bus } from "../../../app";
     export default {
        props:['url','name','mode'],
        data () {
            return {
                profile_tab:'',
            }
        },

   
         created()
            {   

                bus.$on("dataProfileTab", data => {
                    if(data!='')
                    {
                        this.profile_tab=data;                    
                    }
                });    
           }

      }
   </script>