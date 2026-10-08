<script setup lang="ts">
// Desktop 1280×800@2x → 1280w WebP ≤180KB; mobile 390×844@2x → 640w ≤120KB. Demo Junior School and Demo Senior School data only.
defineProps<{ src: string; alt: string; url?: string; phone?: boolean; w: number; h: number; caption?: string }>()
</script>
<template>
  <figure :class="['ka-shot',{phone}]">
    <div v-if="!phone" class="bar" aria-hidden="true"><i/><i/><i/><span>{{ url }}</span></div>
    <div class="stage"><img :src="src" :alt="alt" :width="w" :height="h" loading="lazy" decoding="async"><slot /></div>
    <figcaption v-if="caption">{{ caption }}</figcaption>
  </figure>
</template>
<style scoped>
.ka-shot{margin:16px 0;border:1px solid #CBD5E1;border-radius:12px;overflow:hidden;background:#fff}
.phone{max-width:320px;border:6px solid #0F172A;border-radius:24px}
.bar{height:32px;display:flex;align-items:center;gap:6px;padding:0 12px;background:#F1F5F9;border-bottom:1px solid #E2E8F0}
.bar i{width:9px;height:9px;border-radius:50%;background:#CBD5E1}
.bar span{margin-left:10px;font-size:11.5px;color:#475569;background:#fff;border:1px solid #E2E8F0;border-radius:6px;padding:1px 8px;white-space:nowrap;overflow:hidden}
.stage{position:relative;line-height:0}.stage img{width:100%;height:auto;display:block}
figcaption{padding:10px 14px;font-size:14px;line-height:1.5;color:#475569;border-top:1px solid #E2E8F0}
</style>
