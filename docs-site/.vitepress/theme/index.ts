import DefaultTheme from 'vitepress/theme'
import { h } from 'vue'
import './fonts.css'
import './klassapp.css'
import Breadcrumbs from './components/Breadcrumbs.vue'
import SectionSwitch from './components/SectionSwitch.vue'
import Feedback from './components/Feedback.vue'
import ToshiCallout from './components/ToshiCallout.vue'
import Steps from './components/Steps.vue'
import Step from './components/Step.vue'
import Shot from './components/Shot.vue'
import Mark from './components/Mark.vue'
import Kbd from './components/Kbd.vue'
import Btn from './components/Btn.vue'
import UiPath from './components/UiPath.vue'
import WhatsAppInstead from './components/WhatsAppInstead.vue'
import Role from './components/Role.vue'
import RoleCards from './components/RoleCards.vue'
import DraftBanner from './components/DraftBanner.vue'
import VersionBadge from './components/VersionBadge.vue'

export default {
  extends: DefaultTheme,
  Layout: () => h(DefaultTheme.Layout, null, {
    'nav-bar-content-before': () => h(SectionSwitch),
    'nav-bar-content-after': () => h(VersionBadge),
    'doc-before': () => [h(DraftBanner), h(Breadcrumbs)],
    'doc-footer-before': () => h(Feedback),
  }),
  enhanceApp({ app }) {
    for (const [n, c] of Object.entries({ ToshiCallout, Steps, Step, Shot, Mark, Kbd, Btn, UiPath, WhatsAppInstead, Role, RoleCards })) app.component(n, c)
  },
}
