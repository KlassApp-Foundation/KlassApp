<script setup lang="ts">
// Builds an .ics in the browser (UTC times). No third-party calendar links.
const p = defineProps<{ title: string; start: string; minutes: number; url: string }>()
const z = (d: Date) => d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '')
function dl() {
  const s = new Date(p.start), e = new Date(s.getTime() + p.minutes * 60000)
  const ics = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//KlassApp//Events//EN', 'BEGIN:VEVENT', 'UID:' + p.url.replace(/\W/g, '') + '@klassapp.xyz', 'DTSTAMP:' + z(new Date()), 'DTSTART:' + z(s), 'DTEND:' + z(e), 'SUMMARY:' + p.title, 'URL:https://klassapp.xyz' + p.url, 'END:VEVENT', 'END:VCALENDAR'].join('\r\n')
  const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([ics], { type: 'text/calendar' })); a.download = 'klassapp-event.ics'; a.click(); URL.revokeObjectURL(a.href)
}
</script>
<template><button type="button" class="ka-btn" @click="dl">Add to calendar (.ics)</button></template>
<style scoped>.ka-btn{width:100%;min-height:44px;border:1px solid #CBD5E1;border-radius:10px;background:#fff;color:#0F172A;font:700 15px 'DM Sans',sans-serif;cursor:pointer}</style>
