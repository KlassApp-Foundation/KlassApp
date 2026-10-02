<script setup lang="ts">defineProps<{ secondary?: boolean }>()</script>
<template><span :class="['ka-btn',{secondary}]"><slot /></span></template>
<style scoped>.ka-btn{display:inline-flex;padding:1px 8px;border-radius:6px;background:#0F172A;color:#fff;font-weight:700;font-size:.88em;white-space:nowrap}.secondary{background:#fff;color:#0F172A;border:1px solid #CBD5E1}</style>
