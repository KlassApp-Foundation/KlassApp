<script setup lang="ts">
// n = marker number (max 3 per shot); box = highlight rectangle instead. Positions in % of the image.
defineProps<{ n?: number; x: number; y: number; w?: number; h?: number; box?: boolean }>()
</script>
<template>
  <span v-if="box" class="hl" :style="{left:x+'%',top:y+'%',width:w+'%',height:h+'%'}" aria-hidden="true"/>
  <span v-else class="mk" :style="{left:x+'%',top:y+'%'}" aria-hidden="true">{{ n }}</span>
</template>
<style scoped>
.mk{position:absolute;transform:translate(-50%,-50%);width:28px;height:28px;border-radius:50%;background:var(--kd-mark);color:#fff;font:600 14px/28px 'Sora',sans-serif;text-align:center;box-shadow:0 0 0 3px #fff}
.hl{position:absolute;border:3px solid var(--kd-mark);border-radius:8px;box-shadow:0 0 0 2px #fff}
</style>
