<script setup lang="ts">defineProps<{ say: string; title?: string }>()</script>
<template>
  <aside class="ka-wa">
    <b><img src="/whatsapp.svg" alt="" width="18" height="18">{{ title ?? 'Do this on WhatsApp instead' }}</b>
    <p><slot>Message your school's KlassApp number and send:</slot></p>
    <code class="say">{{ say }}</code>
  </aside>
</template>
<style scoped>
.ka-wa{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:6px 16px;align-items:center;border:1px solid #86EFAC;background:#F0FDF4;border-radius:12px;padding:14px 16px;margin:20px 0}
b{grid-column:1/-1;display:flex;gap:8px;align-items:center;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:#14532D}
p{margin:0}.say{font-size:15px;font-weight:600;background:#fff;border:1px solid #BBF7D0;border-radius:8px;padding:6px 10px;color:#0F172A}
@media (max-width:520px){.ka-wa{grid-template-columns:1fr}.say{justify-self:start}}
</style>
