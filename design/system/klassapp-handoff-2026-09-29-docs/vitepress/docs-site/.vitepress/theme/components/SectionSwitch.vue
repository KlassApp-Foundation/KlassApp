<script setup lang="ts">
import { useRoute, withBase } from 'vitepress'
import { computed } from 'vue'
const route = useRoute()
const cur = computed(() => route.path.includes('/community/') ? 'community' : route.path.includes('/help/') ? 'help' : '')
</script>
<template>
  <nav class="ka-switch" aria-label="Docs section">
    <a :href="withBase('/help/')" :aria-current="cur==='help' ? 'page' : undefined">Help<span> for schools</span></a>
    <a :href="withBase('/community/')" :aria-current="cur==='community' ? 'page' : undefined">Community<span> for contributors</span></a>
  </nav>
</template>
<style scoped>
.ka-switch{display:flex;gap:2px;padding:3px;border:1px solid #CBD5E1;border-radius:10px;background:#fff;margin-left:16px}
a{min-height:38px;display:inline-flex;align-items:center;padding:0 12px;border-radius:7px;font-weight:700;font-size:14px;color:#1E293B;text-decoration:none;white-space:nowrap}
a[aria-current="page"]{background:#0F172A;color:#fff}
@media (max-width:959px){span{display:none}}
@media (max-width:767px){.ka-switch{margin-left:8px}a{padding:0 10px}}
</style>
