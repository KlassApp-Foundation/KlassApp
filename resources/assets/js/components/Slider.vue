<template>
<carousel :per-page="1" :autoplay="true">
  <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Automated Student Attendance Alerts</h3>
   <p class="text-center text-sm">Through the daily attendance module, parents will be sent a notification and SMS through the module every time their child is absent from school/class. The teacher requires to take the attendance through the module and the app automatically triggers a message to the parents if the child is marked absent.</p>
  <img :src="this.url+'/images/app-image/attendance.jpg'"/>
  </slide>
  <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Auto update daily homeworks</h3> 
  <p class="text-center text-sm">Parents will get daily updates of their child’s home works through the app every day. Teachers need to update on the module the daily home works they give in the class. Parents will be able to monitor their ward’s academic progress through this module.</p>
   <img :src="this.url+'/images/app-image/homeworks.jpg'"/>
  </slide>
    <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Update lesson plans</h3> 
  <p class="text-center text-sm">Parents will get daily updates of their child’s home works through the app every day. Teachers need to update on the module the daily home works they give in the class. Parents will be able to monitor their ward’s academic progress through this module.</p>
   <img :src="this.url+'/uploads/lesson-plan.png'"/>
  </slide>
    <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Update class events</h3> 
  <p class="text-center text-sm">Through the parent – teacher communication app, parents will get updated on the events that are happening in the class every day. This will ensure that parents are able to know what academic activities are taking place for the welfare of their child in class.</p>
   <img :src="this.url+'/uploads/events.png'"/>
  </slide>
   <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Notice board updates</h3> 
  <p class="text-center text-sm">Parents will be able to view everything that is put up on the school’s notice board through the app. With the notice board updates parents will be able to prepare their child for any event that is up and coming.</p>
   <img :src="this.url+'/images/app-image/noticeboard.jpg'"/>
  </slide>
  <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Update school events</h3> 
  <p class="text-center text-sm">Event and functions that are happening at the school will be updated through the app for parents who have not been able to attend it. Through this module parents will be able to appreciate the various extra-curricular activities happening in the school.</p>
   <img :src="this.url+'/images/app-image/schoolevents.jpg'"/>
  </slide>
  <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Update disciplinary records</h3> 
  <p class="text-center text-sm">Through the school – teacher communication app, parents will be sent regular updates on their ward’s disciplinary progress in school. Any update regarding a student’s disciplinary record will be sent to the parent through the app through SMS or notification.</p>
   <img :src="this.url+'/images/app-image/discipline.jpg'"/>
  </slide>
   <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Fee reminders</h3> 
  <p class="text-center text-sm">The fee reminders module will automatically send in fee reminders for upcoming term, academic year or examination to parents through the app. Parents will be able to pay the school fees in time for the upcoming term/year.</p>
   <img :src="this.url+'/uploads/fees.png'"/>
  </slide>
    <slide>
  <h3 class="font-extrabold my-4 text-xl text-center">Complaints & feedbacks</h3> 
  <p class="text-center text-sm">Parents will be able to send in any feedback or complaint to the admin through the app. The complaints & feedback module will enable parents to send in their grievances to the school admin for further actions.</p>
   <img :src="this.url+'/images/app-image/feedback.jpg'"/>
  </slide>
</carousel>

</template>

<script>
import { Carousel, Slide } from 'vue-carousel';
    export default {
   props:['url'],
        data() {
      return {
         trans :[],
        sliders: [],
      }
    },
     components: {
    Carousel,
    Slide
  },
     created()
            {
                axios.get('/gethomebanner').then(response => {
                 this.sliders=response.data['slider'];  
                  this.trans=response.data['trans'];  
                });
            },
  
        mounted() {
            console.log('Component mounted.')
        }
    }
</script>
<style scoped>
  .banner-bg {
    height: 600px;
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
}
.banner-bg h5,.banner-bg h1 {
    margin-top: unset;
    color: #fff;
    line-height: 1.4;
}
.banner-bg h5{
  margin-bottom: 35px;
  line-height: 1.8;
    letter-spacing: 0.6px;
}

.banner-detail a {
    color: #232323;
    background-color: #fff;
    border-radius: 5px;
    padding: 8px 20px;
    text-decoration: none;
    text-transform: capitalize;
    font-size: 14px;
    font-weight: 600;
}
.item.active .banner-detail a:hover {
    color:#fff ;
    background-color: #232323 !important;
    text-decoration: none;
    transition-delay: 0s !important;
}
.carousel-control.left,.carousel-control.right {
  background: transparent;
}
@media(min-width: 850px){
  .banner-detail {
    width: 35%;
    position: relative;
    top: 30%; 
}
.banner-detail h1 {
  opacity: 0.001;
  /*transform: translate(545px, 0px) !important;*/
}
.item.active .banner-detail h1 {
    opacity: 1;
    /*left: 155px;*/
    transition: all .8s linear;
    /*transform: scale(1.05);*/
    position: absolute;
    width: 100%;
    transform: translate(133px, 0px) !important;
}
.banner-detail h5 {
  opacity: 0.001;
}
.item.active .banner-detail h5 {
  opacity: 1;
     left: 135px;
    transition: all 1.20s linear;
 /*   transform: scale(1.05);*/
    position: relative;
    top: 120px;
     width: 100%;
    transition-delay: 1s;
}
 .banner-detail a{
  opacity: 0.001;;
 }
.item.active .banner-detail a {
    opacity: 1;
    left: 135px;
   transition: all .50s linear;
    /*transform: scale(1.05);*/
    position: relative;
    top: 115px;
    width: 200px;
    text-align: center;
    transition-delay: 2.5s;
}
.banner-detail.slider-even {
    margin-left: auto;
}
.item.active .banner-detail.slider-even h1{
      transform: translate(-425px, 0px) !important;
    left: 310px;
}
.item.active .banner-detail.slider-even h5  {
  /*transform: translate(-240px,0) !important;*/
  right: 115px !important;
  left: unset !important;
}
.item.active .banner-detail.slider-even a {
 /* transform: translate(-400px,0) !important;*/
 right: 115px !important;
 left: unset !important;
}
}
@media(max-width: 840px){
  .banner-detail{
    text-align: center;
     top: 25%; 
     position: absolute;
  }
  .banner-detail h1{
    font-size: 24px;
  }
  .banner-bg {
    height: 400px;
  }
}
.VueCarousel, .VueCarousel-wrapper {
  z-index: -1;
}

</style>