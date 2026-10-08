<script setup lang="ts">
import { computed, ref } from 'vue'
import { data as posts } from '../../../blog/posts.data'
const tag = ref('All')
const tags = ['All', 'Announcements', 'Product', 'Protocol', 'Community']
const shown = computed(() => tag.value === 'All' ? posts : posts.filter(p => p.tag === tag.value))
const d = (iso: string) => new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(new Date(iso))
</script>
<template>
  <ul class="ka-chips" aria-label="Filter posts"><li v-for="t in tags" :key="t"><button type="button" :aria-pressed="tag === t" @click="tag = t">{{ t }}</button></li></ul>
  <a v-for="(p, i) in shown" :key="p.url" :href="p.url" :class="['ka-post', { feature: i === 0 }]">
    <span class="cover"><img v-if="p.cover" :src="p.cover" :alt="p.coverAlt || ''" loading="lazy" width="1600" height="900"><img v-else src="/klassapp-icon.svg" alt="" class="mark"></span>
    <span class="in"><span class="tag">{{ p.tag }}</span><b>{{ p.title }}</b><span class="lede">{{ p.description }}</span>
      <span class="by"><span class="av" aria-hidden="true">{{ p.authorInitials }}</span><strong>{{ p.author }}</strong> {{ d(p.date) }} · {{ p.minutes }} min read</span></span>
  </a>
  <p v-if="!shown.length" class="none">No posts in this category yet.</p>
</template>
<style scoped>
.ka-chips{display:flex;flex-wrap:wrap;gap:8px;list-style:none;margin:0 0 24px;padding:0}
.ka-chips button{min-height:44px;padding:0 16px;border:1px solid #CBD5E1;border-radius:999px;background:#fff;font:600 14.5px 'DM Sans',sans-serif;color:#1E293B;cursor:pointer}
.ka-chips button[aria-pressed="true"]{background:#0F172A;border-color:#0F172A;color:#fff}
.ka-post{display:grid;grid-template-columns:240px minmax(0,1fr);border:1px solid #E2E8F0;border-radius:16px;overflow:hidden;background:#fff;text-decoration:none!important;color:#1E293B;margin-bottom:14px}
.ka-post.feature{grid-template-columns:minmax(0,1.1fr) minmax(0,1fr)}
.ka-post:hover{border-color:#0F172A}
.cover{background:#FAFAF5 radial-gradient(circle,#D6D3CB 1px,transparent 1.2px) 0 0/18px 18px;display:flex;align-items:center;justify-content:center;min-height:160px}
.feature .cover{min-height:320px}
.cover img{width:100%;height:100%;object-fit:cover}.cover .mark{width:38%;max-width:180px;height:auto;object-fit:contain}
.in{padding:24px 28px;display:flex;flex-direction:column;gap:10px;justify-content:center}
.tag{display:block;align-self:flex-start;font-size:12.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#14532D}
b{font:700 22px/1.25 'Sora',sans-serif;color:#0F172A}.feature b{font-size:28px}
.lede{color:#1E293B}
.by{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:14px;color:#475569}.by strong{color:#0F172A}
.av{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:#1E6FD9;color:#fff;font:600 13px 'Sora',sans-serif}
.none{color:#475569}
@media (max-width:760px){.ka-post,.ka-post.feature{grid-template-columns:1fr}.feature .cover{min-height:180px}.in{padding:18px}.feature b{font-size:22px}}
</style>
