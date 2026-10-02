<script setup lang="ts">defineProps<{ r: 'admin'|'teacher'|'bursar'|'parent'|'student' }>()
const L = { admin:'Admin', teacher:'Teacher', bursar:'Bursar', parent:'Parent', student:'Student' }</script>
<template><span :class="['ka-role', r]">{{ L[r] }}</span></template>
<style scoped>
.ka-role{display:inline-flex;align-items:center;height:26px;padding:0 10px;border-radius:999px;font-size:12.5px;font-weight:700;margin-right:6px}
.admin{background:#0F172A;color:#fff}.teacher{background:#DCFCE7;color:#14532D}.bursar{background:#FEF3C7;color:#78350F}.parent{background:#DBEAFE;color:#1E3A8A}.student{background:#EDE9FE;color:#4C1D95}
</style>
