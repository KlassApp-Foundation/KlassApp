<script setup lang="ts">
// Use ONLY for capabilities marked shipped in docs/internal/role-capability-matrix.md
defineProps<{ ask?: string }>()
</script>
<template>
  <aside class="ka-toshi" aria-label="Toshi can do this for you">
    <span class="av"><img src="/klassapp-icon.svg" alt="" width="24" height="24"></span>
    <div><b>Toshi can do this for you</b><p><slot /><template v-if="ask"> Ask Toshi <q>{{ ask }}</q></template></p></div>
  </aside>
</template>
<style scoped>
.ka-toshi{display:flex;gap:12px;align-items:flex-start;border:1px solid #86EFAC;background:#fff;border-radius:12px;padding:14px 16px;margin:20px 0}
.av{box-sizing:border-box;width:38px;height:38px;flex:none;border-radius:50%;border:1px solid #22C55E;display:flex;align-items:center;justify-content:center}
b{display:block;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#14532D}
p{margin:4px 0 0}q{font-weight:600;color:#0F172A}
</style>
