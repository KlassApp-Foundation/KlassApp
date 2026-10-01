<script setup lang="ts">defineProps<{ title: string }>()</script>
<template><li class="ka-step"><h3 class="t">{{ title }}</h3><div class="b"><slot /></div></li></template>
<style scoped>
.ka-step{counter-increment:ka;display:grid;grid-template-columns:36px minmax(0,1fr);gap:4px 14px}
.ka-step::before{content:counter(ka);grid-row:1/span 2;width:36px;height:36px;border-radius:50%;background:#0F172A;color:#fff;font:600 15px 'Sora',sans-serif;display:flex;align-items:center;justify-content:center}
.t{margin:6px 0 0!important;font-size:17px}.b :deep(p){margin:4px 0}
</style>
