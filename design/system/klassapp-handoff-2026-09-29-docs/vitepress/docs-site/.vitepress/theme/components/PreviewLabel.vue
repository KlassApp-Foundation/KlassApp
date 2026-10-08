<!-- SUPERSEDED (2026-10-07): use the repo's DraftBanner.vue (#922) instead of this file.
     Extend its path check so /blog and /events also show it:
       return (p.startsWith('/help') || p.startsWith('/blog') || p.startsWith('/events')) ? p : ''
     Text, verbatim: "Draft · Preview release (0.x) — first stable release planned for Q1 2027"
     Style: 1px solid var(--vp-c-brand-1) (#1D4ED8), radius 8px, padding 8px 12px, 13px, colour var(--vp-c-brand-1). -->
<template><div class="draft-banner">Draft · Preview release (0.x) — first stable release planned for Q1 2027</div></template>
<style scoped>.draft-banner{border:1px solid var(--vp-c-brand-1);border-radius:8px;padding:8px 12px;margin-bottom:16px;font-size:13px;color:var(--vp-c-brand-1)}</style>
