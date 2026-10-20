export const helpSidebar = [
  { text: 'Start here', items: [
    { text: 'Platform admin', link: '/help/platform/' },
    { text: 'School admin', link: '/help/admins/' },
    { text: 'Head teacher', link: '/help/head-teachers/' },
    { text: 'Class teacher', link: '/help/teachers/class' },
    { text: 'Subject teacher', link: '/help/teachers/subject' },
    { text: 'Bursar', link: '/help/bursars/' },
    { text: 'Parent', link: '/help/parents/' },
    { text: 'Student', link: '/help/students/' } ] },
  { text: 'Soft-launch basics', collapsed: false, items: [
    { text: 'Get started', link: '/help/getting-started' },
    { text: 'Sign up and confirm email', link: '/help/signup' },
    { text: 'Set up your school', link: '/help/setup-school' },
    { text: 'Add students', link: '/help/add-students' },
    { text: 'Enter marks', link: '/help/marks' },
    { text: 'Generate report cards', link: '/help/generate-report-cards' },
    { text: 'Report cards on WhatsApp', link: '/help/send-report-cards-whatsapp' } ] },
  { text: 'Set up your school', collapsed: false, items: [
    { text: 'Onboarding paths', link: '/help/admins/onboarding' },
    { text: 'Link requests', link: '/help/admins/link-requests' },
    { text: 'Approvals', link: '/help/admins/approvals' },
    { text: 'Google tools', link: '/help/admins/google' },
    { text: 'Book a setup session', link: '/help/admins/book-onboarding' },
    { text: 'Classes and streams', link: '/help/concepts/classes-and-streams' } ] },
  { text: 'Teachers', collapsed: true, items: [
    { text: 'Class teacher', link: '/help/teachers/class' },
    { text: 'Subject teacher', link: '/help/teachers/subject' },
    { text: 'Mark attendance', link: '/help/teachers/attendance' } ] },
  { text: 'Parents', collapsed: true, items: [ { text: 'Use KlassApp on WhatsApp', link: '/help/parents/whatsapp' } ] },
  { text: 'Help', items: [
    { text: 'Questions', link: '/help/faq' }, { text: 'Fix a problem', link: '/help/troubleshooting' },
    { text: "What's new", link: '/help/whats-new' } ] },
]
export const communitySidebar = [
  { text: 'Start', items: [
    { text: 'Overview', link: '/community/' },
    { text: 'Local setup', link: '/community/getting-started' },
    { text: 'Tests', link: '/community/tests' },
    { text: 'Architecture', link: '/community/architecture' },
    { text: 'Contributing', link: '/community/contributing' } ] },
  { text: 'Build', items: [
    { text: 'Packages', link: '/community/packages' },
    { text: 'WhatsApp', link: '/community/whatsapp' },
    { text: 'Connectors and MCP', link: '/community/connectors' },
    { text: 'Reference', link: '/community/reference/' }, { text: 'Design system', link: '/community/design-system' } ] },
  { text: 'Project', items: [
    { text: 'Roadmap', link: '/community/roadmap' }, { text: 'Changelog', link: '/community/changelog' },
    { text: 'Provenance', link: '/community/provenance' }, { text: 'Security', link: '/community/security' },
    { text: 'Code of conduct', link: '/community/code-of-conduct' } ] },
  { text: 'Archive', collapsed: true, items: [ { text: 'Legacy WhatsApp (Evolution API)', link: '/community/archive/' } ] },
]
