<script setup lang="ts">
// No endpoint exists yet. Until POST /api/docs-feedback ships, log only — never show a fake "Thanks, sent".
import { ref } from 'vue'; import { useRoute } from 'vitepress'
const route = useRoute(); const v = ref<null|'yes'|'no'>(null); const note = ref('')
const ENDPOINT = '' // set when the API exists
function send() { if (!ENDPOINT) { console.info('[docs-feedback]', route.path, v.value, note.value); return } fetch(ENDPOINT, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ path: route.path, helpful: v.value==='yes', note: note.value }) }) }
</script>
<template>
  <div class="ka-fb">
    <b>Was this helpful?</b>
    <button type="button" :aria-pressed="v==='yes'" @click="v='yes'; send()">Yes</button>
    <button type="button" :aria-pressed="v==='no'" @click="v='no'">No</button>
    <div v-if="v==='no'" class="more"><textarea v-model="note" aria-label="What was missing?" placeholder="What was missing? (optional)"/><button type="button" class="go" @click="send()">Send</button></div>
  </div>
</template>
<style scoped>
.ka-fb{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;border-top:1px solid #E2E8F0;margin-top:40px;padding-top:20px}
b{font:600 16px 'Sora',sans-serif;color:#0F172A;margin-right:auto}
button{min-height:44px;min-width:88px;padding:0 16px;border-radius:10px;border:1px solid #CBD5E1;background:#fff;font-weight:700;color:#1E293B}
button[aria-pressed="true"]{background:#0F172A;border-color:#0F172A;color:#fff}
.more{flex-basis:100%;display:flex;flex-direction:column;gap:8px}textarea{min-height:88px;border:1px solid #CBD5E1;border-radius:10px;padding:10px 12px;font:inherit}
.go{align-self:flex-start;background:#15803D;border-color:#15803D;color:#fff}
</style>
