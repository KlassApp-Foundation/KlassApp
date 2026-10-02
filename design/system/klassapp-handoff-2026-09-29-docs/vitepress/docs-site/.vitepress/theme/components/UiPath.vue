<script setup lang="ts">const p = defineProps<{ to: string }>(); const parts = p.to.split('>').map(s => s.trim())</script>
<template><span class="ka-path"><template v-for="(s,i) in parts" :key="i"><span v-if="i" class="sep" aria-hidden="true">›</span><b>{{ s }}</b></template></span></template>
<style scoped>.sep{margin:0 6px;color:#475569}b{color:#0F172A}</style>
