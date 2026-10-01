<script setup lang="ts">
import { useData, useRoute, withBase } from 'vitepress'
import { computed } from 'vue'
import { helpSidebar, communitySidebar } from '../../sidebars'
const route = useRoute(); const { page, frontmatter } = useData()
const crumbs = computed(() => {
  if (frontmatter.value.breadcrumbs === false) return []
  const p = route.path.replace(/^\/docs-preview/, '')
  const [section, sb] = p.startsWith('/help/') ? ['Help', helpSidebar] : p.startsWith('/community/') ? ['Community', communitySidebar] : [null, []]
  if (!section) return []
  const out = [{ text: 'Docs', link: withBase('/') }, { text: section, link: withBase(section === 'Help' ? '/help/' : '/community/') }]
  for (const g of sb as any[]) if (g.items?.some((i: any) => p.replace(/\/$/, '') === i.link.replace(/\/$/, ''))) { if (g.text !== 'Start here' && g.text !== 'Start') out.push({ text: g.text, link: '' }); break }
  out.push({ text: page.value.title, link: '' })
  return out
})
</script>
<template>
  <nav v-if="crumbs.length" class="ka-crumbs" aria-label="Breadcrumb"><ol>
    <li v-for="(c,i) in crumbs" :key="i"><a v-if="c.link && i < crumbs.length-1" :href="c.link">{{ c.text }}</a><span v-else :aria-current="i===crumbs.length-1 ? 'page' : undefined">{{ c.text }}</span></li>
  </ol></nav>
</template>
<style scoped>
ol{display:flex;flex-wrap:wrap;gap:6px;list-style:none;margin:0 0 12px;padding:0;font-size:14px;color:#475569}
li+li::before{content:'/';margin-right:6px;color:#CBD5E1}a{color:#1D4ED8;text-decoration:none}
</style>
