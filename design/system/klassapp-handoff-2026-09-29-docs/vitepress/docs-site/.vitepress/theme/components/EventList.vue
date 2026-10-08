<script setup lang="ts">
import { computed, ref } from 'vue'
import { data as events } from '../../../events/events.data'
import EventTime from './EventTime.vue'
const tab = ref<'upcoming' | 'past'>('upcoming')
const now = Date.now()
const list = computed(() => tab.value === 'upcoming'
  ? events.filter(e => new Date(e.start).getTime() + e.minutes * 60000 >= now)
  : events.filter(e => new Date(e.start).getTime() + e.minutes * 60000 < now).reverse())
const part = (iso: string, o: Intl.DateTimeFormatOptions) => new Intl.DateTimeFormat('en-GB', o).format(new Date(iso))
</script>
<template>
  <ul class="ka-chips" aria-label="Events"><li><button type="button" :aria-pressed="tab === 'upcoming'" @click="tab = 'upcoming'">Upcoming</button></li><li><button type="button" :aria-pressed="tab === 'past'" @click="tab = 'past'">Past</button></li></ul>
  <ul v-if="list.length" class="ka-events">
    <li v-for="e in list" :key="e.url" class="ev">
      <span class="date" aria-hidden="true"><small>{{ part(e.start, { month: 'short' }) }}</small><b>{{ part(e.start, { day: 'numeric' }) }}</b><span>{{ part(e.start, { weekday: 'short' }) }}</span></span>
      <div><span class="tag">{{ e.type }}</span><h3><a :href="e.url">{{ e.title }}</a></h3><div class="when"><EventTime :start="e.start" :minutes="e.minutes" :host="e.host" /><span>{{ e.where }}</span></div></div>
      <a class="btn" :href="e.url">{{ tab === 'past' && e.recording ? 'Watch the recording' : 'Details and registration' }}</a>
    </li>
  </ul>
  <div v-else class="empty"><b>{{ tab === 'past' ? 'No past events yet' : 'No upcoming events' }}</b><p>{{ tab === 'past' ? 'Recordings and notes appear here after each event.' : 'New events are announced here and on the blog.' }}</p></div>
</template>
<style scoped>
.ka-chips{display:flex;gap:8px;list-style:none;margin:0 0 24px;padding:0}
.ka-chips button{min-height:44px;padding:0 16px;border:1px solid #CBD5E1;border-radius:999px;background:#fff;font:600 14.5px 'DM Sans',sans-serif;color:#1E293B;cursor:pointer}
.ka-chips button[aria-pressed="true"]{background:#0F172A;border-color:#0F172A;color:#fff}
.ka-events{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:14px}
.ev{display:grid;grid-template-columns:84px minmax(0,1fr) auto;gap:20px;align-items:center;padding:18px 20px;border:1px solid #E2E8F0;border-radius:14px;background:#fff}
.date{display:flex;flex-direction:column;align-items:center;justify-content:center;width:84px;height:84px;border-radius:12px;background:#FAFAF5;border:1px solid #E2E8F0;line-height:1;color:#0F172A}
.date small{font:700 12px 'DM Sans',sans-serif;letter-spacing:.08em;text-transform:uppercase;color:#14532D}.date b{font:700 32px 'Sora',sans-serif;margin:4px 0}.date span{font-size:12.5px;color:#475569}
.tag{display:block;align-self:flex-start;font-size:12.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#14532D}
h3{font:600 19px/1.3 'Sora',sans-serif;margin:0 0 4px!important;border:0!important;padding:0!important}h3 a{color:#0F172A;text-decoration:none}
.when{display:flex;flex-wrap:wrap;gap:4px 14px;font-size:14.5px}
.btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;padding:0 18px;border-radius:10px;border:1px solid #CBD5E1;background:#fff;color:#0F172A!important;font-weight:700;text-decoration:none!important;white-space:nowrap}
.empty{display:flex;flex-direction:column;gap:8px;padding:28px 24px;border:1px dashed #CBD5E1;border-radius:14px;background:#fff}.empty b{font:600 18px 'Sora',sans-serif;color:#0F172A}.empty p{margin:0}
@media (max-width:760px){.ev{grid-template-columns:64px minmax(0,1fr);gap:14px;padding:16px}.btn{grid-column:1/-1}.date{width:64px;height:64px}.date b{font-size:24px}}
</style>
