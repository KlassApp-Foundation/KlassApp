// KlassApp docs — VitePress config. NOT RUN: written in Claude Design, never built. Verify with `npm run docs:build`.
import { defineConfig } from 'vitepress'
import { helpSidebar, communitySidebar } from './sidebars'
import { legacyHashMap } from './legacy-hash-map'

export default defineConfig({
  title: 'KlassApp Docs',
  description: 'An open education protocol for humans and agents. KlassApp runs in the tools educationists already use — Slack, spreadsheets, WhatsApp — with Toshi, your school\'s AI assistant.',
  lang: 'en',
  base: '/docs-preview/',
  // Soft-launch: build to docs-preview so live Docsify /docs is unchanged until cutover.
  outDir: '../public/docs-preview',
  cleanUrls: true,
  srcExclude: ['**/_templates/**'],
  lastUpdated: true,
  appearance: false,                 // one light theme; dark mode doubles the QA surface on low-end phones
  head: [
    ['link', { rel: 'icon', href: '/docs-preview/favicon.svg', type: 'image/svg+xml' }],
    ['link', { rel: 'preload', href: '/docs-preview/fonts/dm-sans-latin-400.woff2', as: 'font', type: 'font/woff2', crossorigin: '' }],
    ['script', {}, 'window.__KA_HASH_MAP__=' + JSON.stringify(legacyHashMap) + ';' +
      "(function(){var h=location.hash;if(h.indexOf('#/')!==0)return;var m=window.__KA_HASH_MAP__||{};var k=location.pathname.replace(/\\/$/,'')+'/'+h.slice(2).replace(/\\.md$/,'').replace(/\\?.*$/,'');var t=m[k];if(t)location.replace(t);})()"],
  ],
  themeConfig: {
    logo: { src: '/klassapp-icon.svg', alt: '' },
    siteTitle: 'KlassApp Docs',
    nav: [
      { text: 'Help', link: '/help/', activeMatch: '^/help/' },
      { text: 'Community', link: '/community/', activeMatch: '^/community/' },
      { text: 'klassapp.xyz', link: 'https://klassapp.xyz' },
    ],
    sidebar: { '/help/': helpSidebar, '/community/': communitySidebar },
    outline: { level: [2, 2], label: 'On this page' },
    search: {
      provider: 'local',
      options: {
        miniSearch: { searchOptions: { fuzzy: 0.2, prefix: true, boost: { title: 4, text: 2 } } },
        translations: { button: { buttonText: 'Search', buttonAriaLabel: 'Search the docs' },
          modal: { noResultsText: 'No results. Try fewer words.', resetButtonTitle: 'Clear',
            footer: { selectText: 'open', navigateText: 'move', closeText: 'close' } } },
        // The index loads only when search opens (VitePress default), so it costs nothing on page load.
      },
    },
    editLink: { pattern: 'https://github.com/KlassApp-Foundation/KlassApp/edit/main/docs-site/:path', text: 'Suggest a change' },
    lastUpdated: { text: 'Updated', formatOptions: { dateStyle: 'medium' } },
    docFooter: { prev: 'Previous', next: 'Next' },
    footer: { message: 'The source is public on GitHub; supported self-hosting opens after an independent security review.', copyright: 'KlassApp Foundation · MIT licence' },
    socialLinks: [{ icon: 'github', link: 'https://github.com/KlassApp-Foundation/KlassApp', ariaLabel: 'KlassApp on GitHub' }],
  },
  vite: { build: { cssCodeSplit: true } },
})
