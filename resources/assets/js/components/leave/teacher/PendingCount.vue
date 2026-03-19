<template>
    <div v-if="this.count > 0">
        ( {{ count }} )
    </div>
</template>

<script>
    export default{
        data(){
            return{
                count:0,
            }
        },

        methods:
        {
            getCount()
            {
                axios.get('/teacher/leave/pendingCount').then(response => {
                    this.count = response.data.data;
                });
            }
        },

        created()
        {
            //
        }
    }
</script>