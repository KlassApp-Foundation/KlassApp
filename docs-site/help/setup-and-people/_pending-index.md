# Pending Help index entries

These pages are new and deliberately not linked from `docs-site/help/index.md` or `docs-site/.vitepress/sidebars.ts`, because #1319 and #1321 are rewriting both. Add them after #1319 merges.

Suggested new group, after **Set up your school**:

```
{ text: 'Setup and people', collapsed: false, items: [
  { text: 'School setup steps and the checklist', link: '/help/setup-and-people/onboarding-steps' },
  { text: 'Invite a class teacher', link: '/help/setup-and-people/invite-class-teacher' },
  { text: 'Setup links for parents and students', link: '/help/setup-and-people/parent-and-student-setup-links' },
  { text: 'Plan limits', link: '/help/setup-and-people/plan-limits' },
  { text: 'Take attendance in one tap', link: '/help/setup-and-people/take-attendance' } ] },
```

Where each page also belongs:

| Page | Also link from |
|---|---|
| `setup-and-people/onboarding-steps.md` | **Get started**, **Set up your school** |
| `setup-and-people/invite-class-teacher.md` | **School admin** landing, **Classes and streams**, **Teachers** |
| `setup-and-people/parent-and-student-setup-links.md` | **Add students**, **School admin** landing, **Parents** |
| `setup-and-people/plan-limits.md` | **School admin** landing, **Get started** |
| `setup-and-people/take-attendance.md` | **Class teacher**, **Subject teacher**, **Mark attendance** |

These five pages describe screens that are already on `main`. None needs a draft banner.
