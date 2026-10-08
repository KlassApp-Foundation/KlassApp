<script setup lang="ts">defineProps<{ items: { title: string; text: string; link: string }[] }>()</script>
<template><div class="ka-cards"><a v-for="i in items" :key="i.link" :href="i.link" class="c"><b>{{ i.title }}</b><span>{{ i.text }}</span></a></div></template>
<style scoped>
.ka-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:12px;margin:16px 0}
.c{display:flex;flex-direction:column;gap:4px;padding:16px;border:1px solid #E2E8F0;border-radius:12px;background:#fff;text-decoration:none!important;color:#1E293B}
.c:hover{border-color:#CBD5E1}b{font-family:'Sora',sans-serif;font-weight:600;color:#0F172A}span{font-size:14.5px;color:#475569}
</style>
