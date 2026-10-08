<script setup lang="ts">
// Times are stored in UTC (start, minutes) with the host's IANA zone. The static build renders UTC;
// after hydration it switches to the viewer's zone. <noscript> keeps the UTC line.
import { computed, onMounted, ref } from 'vue'
const p = defineProps<{ start: string; minutes: number; host: string; variant?: 'row' | 'card' }>()
const tz = ref('UTC')
onMounted(() => { tz.value = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC' })
const f = (d: Date, o: Intl.DateTimeFormatOptions, z: string) => new Intl.DateTimeFormat('en-GB', { timeZone: z, ...o }).format(d)
const offset = (d: Date, z: string) => (new Intl.DateTimeFormat('en-GB', { timeZone: z, timeZoneName: 'longOffset' }).formatToParts(d).find(x => x.type === 'timeZoneName')?.value || 'GMT').replace('GMT', 'UTC') || 'UTC'
const v = computed(() => {
  const s = new Date(p.start), e = new Date(s.getTime() + p.minutes * 60000), hm: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit', hour12: false }
  const at = (z: string) => ({ day: f(s, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }, z), range: f(s, hm, z) + '–' + f(e, hm, z), off: offset(s, z), mon: f(s, { month: 'short' }, z), dd: f(s, { day: 'numeric' }, z), wd: f(s, { weekday: 'short' }, z) })
  return { me: at(tz.value), host: at(p.host) }
})
</script>
<template>
  <span v-if="variant !== 'card'" class="row"><time :datetime="start">{{ v.me.day }}</time><span>{{ v.me.range }} <span class="tz">({{ v.me.off }})</span></span></span>
  <div v-else class="card">
    <time class="d" :datetime="start">{{ v.me.day }}</time><span class="t">{{ v.me.range }}</span>
    <span class="tz">Shown in your time zone: {{ tz.replace(/_/g, ' ') }} ({{ v.me.off }}).<br>Host time: {{ v.host.range }}, {{ host.replace(/_/g, ' ') }} ({{ v.host.off }}).</span>
  </div>
</template>
<style scoped>
.row{display:inline-flex;flex-wrap:wrap;gap:4px 14px}.tz{color:#475569}
.card{display:flex;flex-direction:column;gap:8px}.d{font-size:16px}.t{font:600 22px/1.25 'Sora',sans-serif;color:#0F172A}.card .tz{font-size:14px}
</style>
