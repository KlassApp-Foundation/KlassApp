<template>
    <div class="ds-card ds-card-padding-default">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold" style="font-family: Sora, sans-serif; color: var(--d-text);">School Calendar</h2>
            <span class="inline-flex items-center gap-1.5 text-xs" style="color: var(--d-muted);">
                <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: #1E6FD9;"></span>
                Events
            </span>
        </div>

        <FullCalendar :options="calendarOptions" />

        <div v-if="upcomingEvents.length" class="mt-6 border-t border-gray-200 pt-4">
            <h3 class="text-sm font-semibold mb-3" style="color: var(--d-text);">Upcoming Events</h3>
            <ul class="divide-y divide-gray-100">
                <li v-for="event in upcomingEvents" :key="event.id" class="py-2 flex flex-wrap items-center gap-3 text-sm">
                    <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background: #1E6FD9;"></span>
                    <span class="font-medium" style="color: var(--d-text);">{{ event.title }}</span>
                    <span class="ml-auto text-xs" style="color: var(--d-muted);">{{ formatEventDate(event) }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
<script>
    import '@fullcalendar/core/vdom' // Vite ESM: before plugins (FullCalendar v5)
    // Vite ESM can evaluate plugins before @fullcalendar/vue re-exports core;
    // import core first so FullCalendarVDom is registered (FC v5).
    import '@fullcalendar/core'
    import FullCalendar from '@fullcalendar/vue'
    import dayGridPlugin from '@fullcalendar/daygrid'
    import interactionPlugin from '@fullcalendar/interaction'
    import timeGridPlugin from '@fullcalendar/timegrid'
    import { INITIAL_EVENTS, createEventId } from './event-utils'
    import { bus } from "../../event-bus";
    import PortalVue from "portal-vue";
    export default {
        components: {
            FullCalendar // make the <FullCalendar> tag available
        },
        props:['events'],
        data() {
            let initialEvents = [];
            try {
                initialEvents = JSON.parse(this.events || '[]');
            } catch (e) {
                initialEvents = [];
            }
            return {
                calendarOptions: {
                    plugins: [ dayGridPlugin, interactionPlugin, timeGridPlugin ],
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    initialView: 'dayGridMonth',
                    initialEvents: initialEvents,
                    editable: true,
                    selectable: true,
                    selectMirror: true,
                    dayMaxEvents: true,
                    weekends: true,
                    navLinks: false,
                    select: this.handleDateSelect,
                    eventClick: this.handleEventClick,
                    eventsSet: this.handleEvents,
                    eventColor: '#1E6FD9',
                },
                calendarEvents: initialEvents,
            }
        },
        mounted() {
            try {
                this.calendarEvents = JSON.parse(this.events || '[]');
            } catch (e) {
                this.calendarEvents = [];
            }
        },
        computed: {
            upcomingEvents() {
                const today = new Date();
                today.setHours(0,0,0,0);
                return (this.calendarEvents || [])
                    .filter((event) => event && event.start && new Date(event.start) >= today)
                    .slice()
                    .sort((a, b) => new Date(a.start) - new Date(b.start));
            },
        },
        methods: 
        {
            handleWeekendsToggle() 
            {
                this.calendarOptions.weekends = !this.calendarOptions.weekends // update a property
            },

            handleDateSelect(selectInfo) 
            {
                /* let title = prompt('Please enter a new title for your event')
      
                this.errors=[];
                this.success=null;    
                let formData=new FormData();
           
                formData.append('title',title);  
        
                axios.post('/admin/events/create',formData,{headers: {'Content-Type': 'multipart/form-data'}}).then(response => { 
                    this.success=response.data.success;
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });*/
            },

            handleEventClick(clickInfo) 
            {
                this.success=null;
                $('#show-detail').removeClass('hide-menu').addClass(
                    'menu'
                );
                var eventObj = clickInfo.event;
                this.id = eventObj.id;
                this.event = eventObj;
                $('.popover').remove();
                $(clickInfo.el).popover({
                    title: eventObj.title,
                    html: true,
                    content: eventObj.extendedProps.description,
                    placement: 'top',
                    trigger: 'manual'
                }).popover('toggle');
            },

            handleEvents(events) 
            {
                this.calendarEvents = events;
            },

            formatEventDate(event) 
            {
                const start = new Date(event.start);
                if (isNaN(start.getTime())) return '';
                const allDay = !!event.allDay;
                if (allDay) {
                    return start.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
                }
                return start.toLocaleString(undefined, { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
            },
        }
    }
</script>
