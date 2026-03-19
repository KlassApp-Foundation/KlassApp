<template>

    <div class="w-full rounded h-64">
        <carousel :per-page="1" :mouse-drag="false" :navigationClickTargetSize="25" :navigationEnabled="true" :autoplay="false" :paginationEnabled="false" :autoplayHoverPause="true">
            <slide v-for="list in feeds" v-bind:key="list.id" class="w-84">
                <img :src="list.path" class="object-cover h-64 w-full">
            </slide>
        </carousel>
    </div>
</template>

<script>
    import { Carousel, Slide } from 'vue-carousel';
    export default {
        props:['url','id','mode','left','right'],
        components: {
            Carousel,
            Slide
        },
        data() {
            return {
                feeds:[],
                feed:[],
                path:'',
            }
        },

        methods:
        {
            getData()
            {
                axios.get(this.url+'/'+this.mode+'/classwall/post/showList/'+this.id).then(response => {
                    this.feeds = response.data.attachments;
                    console.log(this.feeds);
                });
            },
        },

        created()
        {
            this.getData();
        }
    }
</script>
<style>
    .arrow {
        border: solid black;
        border-width: 0 3px 3px 0;
        display: inline-block;
        padding: 3px;
    }

    .VueCarousel-navigation-prev {
        /* transform: translateY(-50%) translateX(-300%) !important;*/
        transform: unset !important;
        left: 46% !important;
    }

    .VueCarousel-navigation-next {
        /* transform: translateY(-50%) translateX(300%) !important;*/
        transform: unset !important;
        right: 46% !important;
    }

    .VueCarousel-navigation-button {
        padding: 10px !important;
        font-size: 14px !important;
    }

    .VueCarousel-navigation {
        position: relative;
    }

</style>