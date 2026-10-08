<script setup lang="ts">
import { useData } from 'vitepress'
const { frontmatter: f } = useData()
const d = (iso: string) => new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(iso))
</script>
<template>
  <div class="ka-by"><span class="av" aria-hidden="true">{{ f.authorInitials }}</span><b>{{ f.author }}</b><time :datetime="f.date">{{ d(f.date) }}</time><span>· {{ f.minutes }} min read</span></div>
  <figure v-if="f.cover" class="ka-cover"><img :src="f.cover" :alt="f.coverAlt" width="1600" height="900"></figure>
</template>
<style scoped>
.ka-by{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:14px;color:#475569;margin:0 0 16px}.ka-by b{color:#0F172A}
.av{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:#1E6FD9;color:#fff;font:600 13px 'Sora',sans-serif}
.ka-cover{margin:8px 0 24px;border:1px solid #E2E8F0;border-radius:16px;overflow:hidden}.ka-cover img{display:block;width:100%;height:auto}
</style>
