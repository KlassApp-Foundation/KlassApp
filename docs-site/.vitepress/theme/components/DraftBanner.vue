<script setup>
import { useRoute } from 'vitepress'
import { computed } from 'vue'
const route = useRoute()
// useRoute().path is relative to the base (/help/...), but keep it robust if /docs is prefixed
const path = computed(() => {
  const p = route.path.replace(/^\/docs-preview/, '')
  if (p.startsWith('/privacy') || p.startsWith('/terms')) return ''
  return p.startsWith('/help') ? p : ''
})
</script>

<template>
  <div
    v-if="path"
    class="draft-banner"
  >
    Draft · Preview release (0.x) — first stable release planned for Q1 2027
  </div>
</template>

<style scoped>
.draft-banner {
  border: 1px solid var(--vp-c-brand-1);
  border-radius: 8px;
  padding: 8px 12px;
  margin-bottom: 16px;
  font-size: 13px;
  color: var(--vp-c-brand-1);
}
</style>
