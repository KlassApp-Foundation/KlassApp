// KlassApp v2.1 concept. Demo data only (Demo Junior School). Currency comes from school settings; phone numbers are examples.
(() => {
const Q = new URLSearchParams(location.search);
const ST = window.ST = { plan: Q.get('plan') || 'B', page: Q.get('page') || 'dashboard', role: Q.get('role') || 'student', tab: Q.get('tab'), state: Q.get('state') || 'filled', logo: Q.get('logo') === '1', att: Q.get('att') || 'week', rc: Q.get('rc') || 'gen', hover: Q.get('hover'), menu: Q.get('menu') === '1', dlg: Q.get('dlg') === '1', drawer: false };
const PAL = ['#1E6FD9', '#15803D', '#B45309', '#1E293B'];
const CUR = 'UGX', money = v => CUR + ' ' + Number(v).toLocaleString('en');
const ic = (n, c = 'ic') => `<i data-lucide="${n}" class="${c}" aria-hidden="true"></i>`;
const ini = (f, l) => (([...(f || '').trim().split(/\s+/)[0] || ''][0] || '') + ([...(l || '').trim()][0] || '')).toLocaleUpperCase();
const av = (p, s, round) => `<span class="av${round ? ' avc' : ''}" style="width:${s}px;height:${s}px;font-size:${Math.round(s * .4)}px;${!round && s <= 32 ? 'border-radius:8px;' : ''}background:${PAL[p.id % 4]}" aria-hidden="true">${ini(p.fn, p.ln)}</span>`;
const SCHOOL = 'Demo Junior School', TERM = 'Term III, 2026';
const ME = { id: 3301, fn: 'Daniel', ln: 'Mukasa', role: 'School admin', email: 'admin@junior.demo.klassapp.xyz' };
const isPhone = () => innerWidth <= 760;
const NAV = [['', [['dashboard', 'layout-dashboard', 'Dashboard']]], ['People', [['students', 'graduation-cap', 'Students'], ['staff', 'presentation', 'Teachers and staff'], ['parents', 'users', 'Parents']]], ['Academics', [['classes', 'school', 'Classes and subjects'], ['attendance', 'calendar-check', 'Attendance'], ['exams', 'clipboard-list', 'Exams and reports'], ['library', 'library', 'Library']]], ['Money', [['fees', 'wallet', 'Fees'], ['unmatched', 'receipt-text', 'Unmatched payments']]], ['School', [['whatsapp', 'message-circle', 'WhatsApp'], ['approvals', 'list-checks', 'Approvals'], ['settings', 'settings', 'Settings']]]];
const navCur = () => ST.page === 'profile' ? ({ student: 'students', teacher: 'staff', staff: 'staff', parent: 'parents' })[ST.role] : ST.page;
function sidebar() {
  return `<aside class="sb" id="sb" aria-label="Main menu" ${isPhone() && !ST.drawer ? 'inert' : ''}>
  <div class="sb-top"><img src="../../assets/brand/klassapp-horizontal-light.svg" alt="KlassApp"><button class="ib mob" type="button" data-act="drawer" aria-label="Close menu">${ic('x')}</button></div>
  ${isPhone() ? `<div style="padding:0 8px 10px;display:flex;flex-direction:column;gap:4px"><label class="ay" for="ay-m">Academic year</label><select class="sel" id="ay-m"><option selected>2026 (current)</option><option>2025</option></select></div>` : ''}
  <nav class="sb-list" aria-label="Main">${NAV.map(([g, rows]) => `${g ? `<p class="sec">${g}</p>` : ''}${rows.map(([k, i, l]) => `<a class="row" href="#" data-go="${k}" ${k === navCur() ? 'aria-current="page"' : ''}>${ic(i)}<span class="trunc">${l}</span></a>`).join('')}`).join('')}</nav>
  <div class="sb-foot"><button class="acct" type="button" aria-haspopup="menu" aria-expanded="false">${av(ME, 36)}<span style="min-width:0"><b class="trunc">${ME.fn} ${ME.ln}</b><small class="trunc">${ME.email}</small></span>${ic('chevrons-up-down', 'ic ic-s')}</button></div></aside>`;
}
const kbd = () => /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘K' : 'Ctrl K';
const right = () => `<div class="hr"><button class="search" type="button" aria-keyshortcuts="Control+K Meta+K">${ic('search')}<span>Search</span><kbd>${kbd()}</kbd></button><label class="ay">Academic year<select class="sel" aria-label="Academic year"><option selected>2026 (current)</option><option>2025</option></select></label></div>`;
const logo = s => ST.logo ? `<span class="slogo" style="width:${s}px;height:${s}px" role="img" aria-hidden="true">LOGO</span>` : '';
function header(title) {
  const greet = 'Good morning, ' + ME.fn;
  if (ST.page === 'dashboard') {
    if (ST.plan === 'A' || isPhone()) return `<div class="hrow"><div class="hl"><h1 class="pt" style="margin:0">${greet}</h1><p class="sub">${ST.plan === 'A' && !isPhone() ? TERM : TERM}</p></div>${right()}</div>`;
    return `<div class="hrow"><div class="hl"><div class="hsch">${logo(28)}<h1 class="school">${SCHOOL}</h1></div><p class="sub">${greet} · ${TERM}</p></div>${right()}</div>`;
  }
  const q = ST.plan === 'B' && !isPhone() ? `<p class="qsch">${logo(20)}${SCHOOL}</p>` : '';
  return `<div class="hrow"><div class="hl">${q}<h1 class="pt"${q ? '' : ' style="margin:0"'}>${title}</h1></div>${ST.page === 'profile' ? '' : right()}</div>`;
}
/* ---------------- Dashboard v2.1 ---------------- */
function kt(o) {
  const hov = ST.hover === o.k;
  return `<article class="kt surf${hov ? ' is-hover' : ''}"><span class="l">${ic(o.i, 'ic ic-s')}<a class="kl" href="#" data-go="${o.go}" title="${o.l}">${o.l}<span class="sr">, open ${o.to}</span></a></span>${ic('arrow-up-right', 'ic ic-s go')}
  <span class="v">${o.v}</span>${o.m != null ? `<span class="meter" role="img" aria-label="${o.m}%"><i style="width:${o.m}%"></i></span>` : ''}${o.fb ? `<span class="fb">${o.fb}</span>` : ''}<span class="s ${o.c || ''}">${o.s}</span>
  ${o.a ? `<span class="acts"><a class="btn sm" href="#">${ic(o.a[0], 'ic ic-s')}${o.a[1]}</a></span>` : ''}</article>`;
}
function scrDashboard() {
  const last = ST.att === 'last';
  const tiles = [
    { k: 'stu', i: 'graduation-cap', l: 'Students', to: 'Students', go: 'students', v: '66', s: '+66 this term', c: 'up', a: ['user-plus', 'Add students'] },
    { k: 'staff', i: 'presentation', l: 'Staff', to: 'Teachers and staff', go: 'staff', v: '12', s: '10 teachers · 2 admin' },
    last ? { k: 'att', i: 'calendar-check', l: 'Attendance last week', to: 'Attendance', go: 'attendance', v: '94.2%', fb: 'No register taken yet this week', s: 'Mon 5 – Fri 9 Oct', a: ['clipboard-check', 'View registers'] }
      : { k: 'att', i: 'calendar-check', l: 'Attendance this week', to: 'Attendance', go: 'attendance', v: '93.4%', s: '▲ 1.2 pts on last week', c: 'up', a: ['clipboard-check', 'View registers'] },
    { k: 'fees', i: 'wallet', l: 'Fees collected', to: 'Fees', go: 'fees', v: '18%', m: 18, s: CUR + ' 9.5M of 54M', a: ['banknote', 'Record payment'] },
    ST.rc === 'send' ? { k: 'rc', i: 'file-text', l: 'Report cards ready', to: 'Report cards', go: 'exams', v: '66', m: 100, s: 'of 66 · End of term', a: ['message-circle', 'Send on WhatsApp'] }
      : { k: 'rc', i: 'file-text', l: 'Report cards ready', to: 'Report cards', go: 'exams', v: '10', m: 15, s: 'of 66 · End of term', a: ['file-plus', 'Generate'] }];
  const perf = [['P.1', 61.8], ['P.2', 81.8], ['P.3', 62.2], ['P.4', 73], ['P.5', 63.2], ['P.6', 59.4], ['P.7', 78.4]];
  const G = [['P.1', 5, 4, 0], ['P.2', 4, 5, 0], ['P.3', 5, 5, 0], ['P.4', 4, 4, 1], ['P.5', 5, 5, 0], ['P.6', 5, 5, 0], ['P.7', 5, 4, 0]];
  const tot = G.reduce((a, r) => [a[0] + r[1], a[1] + r[2], a[2] + r[3]], [0, 0, 0]), T = tot[0] + tot[1] + tot[2];
  const gbar = (lab, g, b, u, cls) => { const n = g + b + u; return `<div class="gb${cls ? ' ' + cls : ''}"><span>${lab}</span><span class="stack" role="img" aria-label="${lab}: ${g} girls, ${b} boys, ${u} not specified"><i style="width:${g / n * 100}%;background:#B45309"></i><i style="width:${b / n * 100}%;background:#1E6FD9"></i><i style="width:${u / n * 100}%;background:#64748B"></i></span><span class="n">${n}</span></div>`; };
  const W = 520, H = 190, pts = [['W1', 96], ['W2', 95.2], ['W3', 95], ['W4', 97.1], ['W5', last ? null : 93.4]].filter(p => p[1] != null);
  const x = i => 40 + i * (W - 56) / 4, y = v => 14 + (100 - v) / 20 * (H - 44);
  const line = `<svg class="chart" viewBox="0 0 ${W} ${H}" role="img" aria-label="Attendance by week: ${pts.map(p => p[0] + ' ' + p[1] + '%').join(', ')}">${[80, 90, 100].map(v => `<line x1="40" x2="${W - 12}" y1="${y(v)}" y2="${y(v)}" stroke="#E2E8F0"/><text x="34" y="${y(v) + 4}" text-anchor="end" font-size="11" fill="#475569">${v}%</text>`).join('')}<path d="${pts.map((p, i) => (i ? 'L' : 'M') + x(i) + ' ' + y(p[1])).join(' ')}" fill="none" stroke="#15803D" stroke-width="2.5"/>${pts.map((p, i) => `<circle cx="${x(i)}" cy="${y(p[1])}" r="3.5" fill="#15803D"/>`).join('')}${['W1', 'W2', 'W3', 'W4', 'W5'].map((w, i) => `<text x="${x(i)}" y="${H - 8}" text-anchor="middle" font-size="11" fill="#475569">${w}</text>`).join('')}</svg>`;
  const F = [['Sep', 4.5, 13.5], ['Oct', 5, 27], ['Nov', 0, 13.5]], fmax = 30;
  const fy = v => (H - 30) - v / fmax * (H - 50);
  const fees = `<svg class="chart" viewBox="0 0 ${W} ${H}" role="img" aria-label="Fees by month, collected against expected: ${F.map(f => f[0] + ' ' + f[1] + 'M of ' + f[2] + 'M').join(', ')}">${F.map((f, i) => { const bx = 70 + i * 150; return `<rect x="${bx}" y="${fy(f[2])}" width="90" height="${(H - 30) - fy(f[2])}" rx="5" fill="none" stroke="#94A3B8" stroke-dasharray="4 3"/><rect x="${bx}" y="${fy(f[1])}" width="90" height="${(H - 30) - fy(f[1])}" rx="5" fill="#15803D"/><text x="${bx + 45}" y="${fy(f[2]) - 6}" text-anchor="middle" font-size="11" font-weight="700" fill="#0F172A">${f[1]}M of ${f[2]}M</text><text x="${bx + 45}" y="${H - 10}" text-anchor="middle" font-size="11" fill="#475569">${f[0]}</text>`; }).join('')}</svg>`;
  return header('Dashboard') + `<section aria-label="Key figures"><div class="kpis">${tiles.map(kt).join('')}</div></section>
  <div class="grid2"><section class="card" aria-labelledby="c-perf"><div class="ch"><div><h2 id="c-perf">Performance by class</h2><small>End of term · average mark</small></div><a class="btn sm" href="#">${ic('clipboard-list', 'ic ic-s')}Enter marks</a></div><div class="grow"><div class="hbars">${perf.map(p => `<div class="hb"><span>${p[0]}</span><span class="t" role="img" aria-label="${p[0]}: ${p[1]}%"><i style="width:${p[1]}%"></i></span><b>${p[1]}%</b></div>`).join('')}</div></div></section>
  <section class="card" aria-labelledby="c-att"><div class="ch"><div><h2 id="c-att">Attendance trend</h2><small>This term, 5 weeks · whole school</small></div></div><div class="grow">${line}</div></section>
  <section class="card" aria-labelledby="c-gen"><div class="ch"><div><h2 id="c-gen">Students by gender</h2><small>${T} students · by class</small></div></div><div class="grow"><div class="hbars">${gbar('School', tot[0], tot[1], tot[2], 'tot')}${G.map(r => gbar(r[0], r[1], r[2], r[3])).join('')}</div></div><div class="legend"><span><i style="background:#B45309"></i>Girls ${tot[0]}</span><span><i style="background:#1E6FD9"></i>Boys ${tot[1]}</span><span><i style="background:#64748B"></i>Not specified ${tot[2]}</span></div></section>
  <section class="card" aria-labelledby="c-fee"><div class="ch"><div><h2 id="c-fee">Fees collection</h2><small>Collected against expected, by month</small></div></div><div class="grow">${fees}</div><div class="legend"><span><i style="background:#15803D"></i>Collected</span><span><i style="border:1px dashed #94A3B8"></i>Expected</span></div></section></div>`;
}
/* ---------------- Other page (for the quiet school line) ---------------- */
function scrStudents() { return header('Students') + `<div class="card"><p style="margin:0">The people list from the admin MVP pack goes here, unchanged. This page only shows the header row: the quiet school line above the page title, with Search and the Academic year on the right.</p></div>`; }
/* ---------------- Shared person profile ---------------- */
const P = {
  student: { id: 1040, fn: 'Amara', ln: 'Okafor', role: 'Student', status: 'Active', crumb: ['Students', 'students'],
    facts: [['Class', 'Primary 5 · Blue'], ['KLS number', 'KLS0070001'], ['Age', '11 · born 14 Mar 2015'], ['Gender', 'Female'], ['Joined', 'Jan 2024']],
    msg: ['message-circle', 'Message parent'], more: ['Edit details', 'Reset password', 'Move to class', 'Print report card'],
    kpis: [['calendar-check', 'Attendance this term', '91%', '5 days absent · 2 late', 91], ['chart-column', 'Latest exam · End of term', '74% · B', '6th of 32 in Primary 5 · Blue'], ['wallet', 'Fees balance · Term III', money(180000), 'Partly paid · ' + money(270000) + ' of ' + money(450000), null, 'owed']],
    tabs: ['Overview', 'Academics', 'Attendance', 'Fees', 'Parents and guardians', 'Health and support', 'Documents', 'Notes'] },
  teacher: { id: 3302, fn: 'Sarah', ln: 'Nakato', role: 'Teacher', status: 'Active', crumb: ['Teachers and staff', 'staff'],
    facts: [['Class teacher of', 'Primary 5 · Blue'], ['Teaches', 'Mathematics, Science'], ['Staff number', 'DJS-T014'], ['Phone', '+000 700 100 101'], ['Joined', 'Sep 2026']],
    msg: ['message-circle', 'Message'], more: ['Edit details', 'Assign classes and subjects', 'Reset password', 'Resend invite'],
    kpis: [['school', 'Classes and subjects', '3', 'Primary 5 · Blue, Primary 6 · Blue'], ['circle-alert', 'Marks still pending', '1', 'Science · Primary 5 Blue', null, 'warn'], ['clipboard-check', 'Registers this week', '4 of 5', 'Not yet taken: Friday']],
    tabs: ['Overview', 'Teaching', 'Attendance', 'Documents'] },
  parent: { id: 2210, fn: 'Grace', ln: 'Okafor', role: 'Parent', status: 'Active', crumb: ['Parents', 'parents'],
    facts: [['Children', 'Amara, Tobi'], ['Phone', '+000 772 418 205'], ['WhatsApp', 'Opted in · 12 Sep 2026'], ['Email', 'grace.okafor@example.com'], ['Last login', 'Today, 07:42']],
    msg: ['message-circle', 'Message on WhatsApp'], more: ['Edit details', 'Link a child', 'Reset password'],
    kpis: [['users', 'Children', '2', 'Primary 5 · Blue, Primary 2 · Blue'], ['wallet', 'Fees due · all children', money(180000), 'Amara ' + money(180000) + ' · Tobi cleared', null, 'owed'], ['message-circle', 'WhatsApp this term', '12 sent', '2 replies · last on 9 Oct']],
    tabs: ['Overview', 'Children', 'Fees', 'Messages'] },
  staff: { id: 3310, fn: 'Ruth', ln: 'Kim', role: 'Bursar', status: 'Active', crumb: ['Teachers and staff', 'staff'],
    facts: [['Role', 'Bursar'], ['Staff number', 'DJS-S003'], ['Phone', '+000 700 100 120'], ['Email', 'r.kim@demojunior.school'], ['Joined', 'Aug 2025']],
    msg: ['message-circle', 'Message'], more: ['Edit details', 'Change role', 'Reset password'],
    kpis: [['banknote', 'Payments recorded · Term III', '84', money(9500000) + ' in total'], ['receipt-text', 'Unmatched payments', '3', 'Waiting to be matched', null, 'warn'], ['bell', 'Reminders sent', '41', 'This term, on WhatsApp']],
    tabs: ['Overview', 'Attendance', 'Documents'] } };
const empty = (t, p, b) => `<div class="empty"><b>${t}</b><p>${p}</p>${b ? `<a class="btn" href="#">${b}</a>` : ''}</div>`;
const E = () => ST.state === 'empty';
function heat(kind) {
  if (E()) return empty(kind === 'student' ? 'No registers taken yet this term' : 'No attendance recorded yet', kind === 'student' ? 'The calendar fills in as the class teacher takes the morning register.' : 'Staff attendance appears here once it’s recorded.');
  const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
  // October 2026: Thu 1 Oct. 2026-10-05 Monday.
  const S = { 1: 'p', 2: 'p', 5: 'a', 6: 'p', 7: 'p', 8: 'l', 9: 'h', 12: 'p', 13: 'n' }, L = { p: 'Present', l: 'Late', a: 'Absent', h: 'No school', n: 'Not taken yet' }, A = { p: 'P', l: 'L', a: 'A', h: '–', n: '?' };
  const cells = [];
  for (let w = 0; w < 5; w++) for (let d = 0; d < 5; d++) { const day = w * 7 + d - 2; if (day < 1 || day > 30) { cells.push('<span class="cell st-o" aria-hidden="true"></span>'); continue; }
    const s = S[day] || (day > 13 ? 'f' : 'p'); if (s === 'f') { cells.push(`<span class="cell" style="border:1px solid #E2E8F0;color:#475569" role="img" aria-label="${days[d]} ${day} Oct: not yet">${day}</span>`); continue; }
    cells.push(`<span class="cell st-${s}" role="img" aria-label="${days[d]} ${day} Oct: ${L[s]}">${day}<span>${A[s]}</span></span>`); }
  const lg = [['p', 'Present'], ['l', 'Late'], ['a', 'Absent'], ['h', 'No school'], ['n', 'Not taken yet']];
  return `<section class="card" aria-labelledby="hm-t"><div class="hmh"><h2 id="hm-t">October 2026</h2><span style="display:flex;gap:6px"><button class="btn icon" type="button" aria-label="Previous month">${ic('chevron-left')}</button><button class="btn icon" type="button" aria-label="Next month" disabled>${ic('chevron-right')}</button></span></div>
  <div class="hm" role="group" aria-label="Attendance, October 2026">${days.map(d => `<span class="dn" aria-hidden="true">${d}</span>`).join('')}${cells.join('')}</div>
  <div class="hm-legend">${lg.map(l => `<span><i class="st-${l[0]}">${A[l[0]]}</i>${l[1]}</span>`).join('')}</div>
  <p style="margin:0;font-size:13.5px">This term: <b>91% present</b> · 5 days absent · 2 late. Each day shows its letter as well as its colour.</p></section>`;
}
function tabBody(role, tab) {
  const p = P[role];
  if (tab === 'Overview') {
    const det = role === 'student' ? [['Full name', 'Amara Okafor'], ['KLS number', 'KLS0070001'], ['Class', 'Primary 5 · Blue'], ['Gender', 'Female'], ['Date of birth', '14 Mar 2015 (age 11)'], ['Joined', 'Term 1, 2024']]
      : role === 'parent' ? [['Full name', 'Grace Okafor'], ['Relationship', 'Mother of Amara and Tobi'], ['Phone', '+000 772 418 205'], ['Email', 'grace.okafor@example.com'], ['District of residence', 'Not given']]
      : [['Full name', p.fn + ' ' + p.ln], ['Role', p.role], ['Staff number', p.facts.find(f => f[0] === 'Staff number')[1]], ['Phone', p.facts.find(f => f[0] === 'Phone')[1]], ['Joined', p.facts.find(f => f[0] === 'Joined')[1]]];
    const second = role === 'student' ? `<section class="card"><div class="ch"><h2>Parents and guardians</h2><a class="btn sm" href="#" data-tab="Parents and guardians">See all</a></div>${E() ? `<p style="margin:0">No parent linked yet.</p><a class="btn" href="#" style="align-self:flex-start">${ic('link')}Link a parent</a>` : `<div class="pc">${av({ id: 2210, fn: 'Grace', ln: 'Okafor' }, 44)}<div><b>Grace Okafor</b><div class="meta">Mother · primary contact · <span class="chip c-ok">WhatsApp opted in</span></div></div></div><div class="pc">${av({ id: 2215, fn: 'Peter', ln: 'Okafor' }, 44)}<div><b>Peter Okafor</b><div class="meta">Father · <span class="chip c-off">Not opted in</span></div></div></div>`}</section>`
      : role === 'parent' ? `<section class="card"><h2>Children</h2>${[{ id: 1040, fn: 'Amara', ln: 'Okafor', c: 'Primary 5 · Blue', b: money(180000) + ' due' }, { id: 1090, fn: 'Tobi', ln: 'Okafor', c: 'Primary 2 · Blue', b: 'Fees cleared' }].map(k => `<div class="pc">${av(k, 44)}<div><a href="#" data-role="student" style="font-weight:700">${k.fn} ${k.ln}</a><div class="meta">${k.c} · ${k.b}</div></div></div>`).join('')}</section>`
      : `<section class="card"><h2>${role === 'teacher' ? 'Teaching this term' : 'Access'}</h2>${role === 'teacher' ? `<table class="dt"><thead><tr><th scope="col">Class</th><th scope="col">Subject</th><th class="n" scope="col">Students</th></tr></thead><tbody><tr><td>Primary 5 · Blue <span class="chip c-ok">Class teacher</span></td><td>Mathematics</td><td class="n">32</td></tr><tr><td>Primary 5 · Blue</td><td>Science</td><td class="n">32</td></tr><tr><td>Primary 6 · Blue</td><td>Mathematics</td><td class="n">27</td></tr></tbody></table>` : `<dl class="kv"><dt>Sees</dt><dd>Fees, payments and reminders for every class</dd><dt>Doesn’t see</dt><dd>Marks, attendance, health notes</dd><dt>Last login</dt><dd>Today, 08:05</dd></dl>`}</section>`;
    return `<div class="grid2"><section class="card"><h2>Details</h2><dl class="kv">${det.map(d => `<dt>${d[0]}</dt><dd>${E() && /Date|District|Email/.test(d[0]) ? '<span style="color:#475569">Not given</span>' : d[1]}</dd>`).join('')}</dl></section>${second}</div>`;
  }
  if (tab === 'Academics') {
    if (E()) return empty('No marks yet', 'Marks appear here once teachers enter them for an exam. Nothing to do on this page.');
    const sub = [['English', 70, 72, 76], ['Mathematics', 66, 71, 73], ['Science', 74, 75, 79], ['Social Studies', 62, 66, 68], ['Religious Education', 81, 80, 84]];
    const W = 560, H = 210, gw = (W - 50) / sub.length, bw = (gw - 18) / 3, y = v => (H - 34) - v / 100 * (H - 50), C = ['#94A3B8', '#1E6FD9', '#15803D'];
    const svg = `<svg class="chart" viewBox="0 0 ${W} ${H}" role="img" aria-label="Marks by subject and term: ${sub.map(s => s[0] + ' ' + s.slice(1).join(', ')).join('; ')}">${[0, 50, 100].map(v => `<line x1="40" x2="${W - 6}" y1="${y(v)}" y2="${y(v)}" stroke="#E2E8F0"/><text x="34" y="${y(v) + 4}" text-anchor="end" font-size="11" fill="#475569">${v}</text>`).join('')}${sub.map((s, i) => { const gx = 46 + i * gw; return [1, 2, 3].map(t => `<rect x="${gx + (t - 1) * (bw + 3)}" y="${y(s[t])}" width="${bw}" height="${(H - 34) - y(s[t])}" rx="3" fill="${C[t - 1]}"/>`).join('') + `<text x="${gx + gw / 2 - 8}" y="${H - 14}" text-anchor="middle" font-size="11" fill="#475569">${s[0].split(' ')[0]}</text>`; }).join('')}</svg>`;
    return `<section class="card"><div class="ch"><div><h2>Marks by subject</h2><small>2026 · average % per term</small></div></div>${svg}<div class="legend"><span><i style="background:#94A3B8"></i>Term I</span><span><i style="background:#1E6FD9"></i>Term II</span><span><i style="background:#15803D"></i>Term III</span></div></section>
    <section class="card"><h2>Subjects</h2><table class="dt"><thead><tr><th scope="col">Subject</th><th class="n" scope="col">Term I</th><th class="n" scope="col">Term II</th><th class="n" scope="col">Term III</th><th scope="col">Grade</th></tr></thead><tbody>${sub.map(s => `<tr><td>${s[0]}</td><td class="n">${s[1]}</td><td class="n">${s[2]}</td><td class="n">${s[3]}</td><td>${s[3] >= 80 ? 'A' : s[3] >= 70 ? 'B' : 'C'}</td></tr>`).join('')}</tbody><tfoot><tr><td>Average</td><td class="n">71</td><td class="n">73</td><td class="n">76</td><td>B</td></tr></tfoot></table></section>`;
  }
  if (tab === 'Attendance') return heat(role);
  if (tab === 'Fees') {
    if (E()) return empty('No fee statement yet', 'Charges and payments appear once fees for this term are set up.', 'Set up fees');
    const R = [['1 Sep', 'Term III fees', 450000, 0], ['2 Sep', 'Payment · bank transfer · ref BT-20931', 0, 200000], ['30 Sep', 'Payment · mobile money · ref MM-77310', 0, 70000]];
    let bal = 0;
    const rows = R.map(r => { bal += r[2] - r[3]; return `<tr><td class="d">${r[0]}</td><td>${r[1]}</td><td class="n hide-m">${r[2] ? money(r[2]) : ''}</td><td class="n hide-m">${r[3] ? money(r[3]) : ''}</td><td class="n">${r[2] ? '+' + money(r[2]) : '−' + money(r[3])}<span class="sr">, balance ${money(bal)}</span></td></tr>`; }).join('');
    return `<section class="card"><div class="ch"><div><h2>Statement · Term III, 2026</h2><small>Amounts in ${CUR}, from school settings</small></div><span class="chip c-warn">Partly paid</span></div>
    <div class="sumrow"><div><small>Due this term</small><b>${money(450000)}</b></div><div><small>Paid</small><b>${money(270000)}</b></div><div><small>Balance</small><b class="owed">${money(180000)}</b></div></div>
    <table class="dt stackm"><thead><tr><th scope="col">Date</th><th scope="col">Description</th><th class="n hide-m" scope="col">Charged</th><th class="n hide-m" scope="col">Paid</th><th class="n" scope="col">Amount</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><td class="d"></td><td>Balance</td><td class="hide-m"></td><td class="hide-m"></td><td class="n owed">${money(180000)}</td></tr></tfoot></table>
    <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn pri" href="#">Record payment</a><a class="btn" href="#">${ic('message-circle')}Send reminder on WhatsApp</a></div></section>`;
  }
  if (tab === 'Parents and guardians' || tab === 'Children') {
    if (E()) return empty(tab === 'Children' ? 'No children linked yet' : 'No parent or guardian linked yet', 'Link one so they receive report cards and fee messages on WhatsApp.', tab === 'Children' ? 'Link a child' : 'Link a parent');
    const G = tab === 'Children' ? [{ id: 1040, fn: 'Amara', ln: 'Okafor', r: 'Primary 5 · Blue · KLS0070001', wa: '', b: money(180000) + ' due' }, { id: 1090, fn: 'Tobi', ln: 'Okafor', r: 'Primary 2 · Blue · KLS0070033', wa: '', b: 'Fees cleared' }]
      : [{ id: 2210, fn: 'Grace', ln: 'Okafor', r: 'Mother · primary contact', ph: '+000 772 418 205', wa: 'in' }, { id: 2215, fn: 'Peter', ln: 'Okafor', r: 'Father', ph: '+000 701 552 930', wa: 'none' }];
    return `<div class="gcards">${G.map(g => `<article class="gc"><div class="who">${av(g, 48)}<span style="min-width:0"><b>${g.fn} ${g.ln}</b><small>${g.r}</small></span></div>
    ${tab === 'Children' ? `<span>${g.b.includes('due') ? `<span class="owed" style="font-weight:700">${g.b}</span>` : `<span class="chip c-ok">${g.b}</span>`}</span><div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn" href="#">${ic('file-text')}Report card</a><a class="btn" href="#">${ic('wallet')}Fees</a></div>`
      : `<a class="tel" href="tel:${g.ph.replace(/\s/g, '')}">${ic('phone')}${g.ph}</a>${g.wa === 'in' ? '<span class="chip c-ok" style="align-self:flex-start">WhatsApp: opted in on 12 Sep 2026</span>' : `<span class="chip c-off" style="align-self:flex-start">WhatsApp: not opted in</span><a class="btn" href="#" style="align-self:flex-start">${ic('send')}Send opt-in request</a>`}`}</article>`).join('')}</div>`;
  }
  if (tab === 'Teaching') return E() ? empty('Not teaching any classes yet', 'Assign classes and subjects so this teacher can take registers and enter marks.', 'Assign classes and subjects') : tabBody('teacher', 'Overview').replace(/^<div class="grid2"><section class="card"><h2>Details<\/h2>[\s\S]*?<\/section>/, '<div>');
  if (tab === 'Messages') return E() ? empty('No messages yet', 'WhatsApp messages sent to this parent appear here.') : `<section class="card"><h2>Recent WhatsApp messages</h2><table class="dt stackm"><thead><tr><th scope="col">Date</th><th scope="col">Message</th></tr></thead><tbody><tr><td class="d">9 Oct</td><td>Amara was absent today.</td></tr><tr><td class="d">2 Oct</td><td>Fee reminder: ${money(180000)} due for Amara.</td></tr></tbody></table></section>`;
  if (tab === 'Health and support') return `<section class="card"><div class="ch"><h2>Health and support</h2><span class="chip c-role">Admins and head teacher only</span></div><p style="margin:0">Unchanged from the admin MVP pack. Opening this tab is logged.</p></section>`;
  if (tab === 'Documents') return empty('No documents yet', role === 'student' ? 'Birth certificates and photos sent with the admission form appear here.' : 'Contracts and certificates appear here. Only admins can see them.', 'Upload document');
  return `<section class="card"><p style="margin:0">Notes: unchanged from the admin MVP pack (admins only, each view logged).</p></section>`;
}
function scrProfile() {
  const r = ST.role, p = P[r], tab = p.tabs.includes(ST.tab) ? ST.tab : p.tabs[0];
  const facts = E() && r === 'student' ? p.facts.map(f => f[0] === 'Age' ? ['Age', 'Not given'] : f) : p.facts;
  const menu = ST.menu ? `<div class="menu" role="menu" aria-label="More actions">${p.more.map(m => `<a class="mi" role="menuitem" href="#">${m}</a>`).join('')}<div class="sep" role="separator"></div><button class="mi danger" role="menuitem" type="button" data-act="dlg">${ic('user-x')}Deactivate ${r === 'student' ? 'student' : 'account'}</button></div>` : '';
  return `<nav class="crumb" aria-label="Breadcrumb"><a href="#" data-go="${p.crumb[1]}">${p.crumb[0]}</a> <span aria-hidden="true">›</span> ${p.fn} ${p.ln}</nav>
  ${ST.plan === 'B' && !isPhone() ? `<p class="qsch" style="margin-bottom:-12px">${logo(20)}${SCHOOL}</p>` : ''}
  <header class="phd">${av(p, isPhone() ? 64 : 96)}<div style="min-width:0"><div class="nm"><h1>${p.fn} ${p.ln}</h1><span class="chip c-role">${p.role}</span>${p.status === 'Active' ? '<span class="chip c-ok">Active</span>' : ''}</div>
  <dl class="facts">${facts.map(f => `<div><dt>${f[0]}</dt><dd>${f[1]}</dd></div>`).join('')}</dl></div>
  <div class="pacts"><a class="btn" href="#">${ic('pencil')}Edit</a><a class="btn" href="#">${ic(p.msg[0])}${p.msg[1]}</a><button class="btn icon" type="button" data-act="menu" aria-haspopup="menu" aria-expanded="${ST.menu}" aria-label="More actions">${ic('ellipsis')}</button>${menu}</div></header>
  <section aria-label="Key figures"><div class="kpis k3">${p.kpis.map(k => `<div class="pk"><span class="l">${ic(k[0], 'ic ic-s')}${k[1]}</span><span class="v ${k[5] === 'owed' ? 'owed' : ''}">${E() ? '–' : k[2]}</span>${k[4] != null && !E() ? `<span class="meter" role="img" aria-label="${k[4]}%"><i style="width:${k[4]}%"></i></span>` : ''}<span class="s ${k[5] === 'warn' ? 'warn' : ''}">${E() ? 'Nothing recorded yet' : k[3]}</span></div>`).join('')}</div></section>
  <div class="tabs" role="tablist" aria-label="${p.fn} ${p.ln}">${p.tabs.map(t => `<button role="tab" type="button" data-tab="${t}" aria-selected="${t === tab}" id="tab-${t.replace(/\W/g, '')}" aria-controls="tp">${t}</button>`).join('')}</div>
  <div id="tp" role="tabpanel" aria-labelledby="tab-${tab.replace(/\W/g, '')}" style="display:flex;flex-direction:column;gap:16px">${tabBody(r, tab)}</div>`;
}
const DLG = () => `<div class="dlg-bg"><div class="dlg" role="alertdialog" aria-modal="true" aria-labelledby="dt" aria-describedby="dd"><h2 id="dt">Deactivate ${P[ST.role].fn} ${P[ST.role].ln}?</h2><p id="dd">${ST.role === 'student' ? 'She won’t appear in registers, marks or fee reminders. Her records are kept, and you can reactivate her at any time.' : 'They will no longer be able to sign in. Their records are kept, and you can reactivate the account at any time.'}</p><div class="btns"><button class="btn" type="button" data-act="dlgx">Cancel</button><button class="btn danger" type="button" data-act="dlgx">Deactivate</button></div></div></div>`;
function render() {
  document.body.className = ['plan' + ST.plan, ST.drawer ? 'drawer' : ''].join(' ');
  const body = ST.page === 'dashboard' ? scrDashboard() : ST.page === 'profile' ? scrProfile() : scrStudents();
  document.getElementById('app').innerHTML = `<header class="topA" aria-label="School">${'<img class="k" src="../../assets/brand/klassapp-icon.svg" alt="KlassApp">'}<span class="sch">${ST.logo ? '<span class="slogo">LOGO</span>' : ''}<b class="trunc">${SCHOOL}</b><span>${TERM}</span></span><button class="search" type="button" style="width:240px" aria-keyshortcuts="Control+K Meta+K">${ic('search')}<span>Search</span><kbd>${kbd()}</kbd></button></header>
  <header class="topP"><button class="ib" type="button" data-act="drawer" aria-label="Open menu" aria-expanded="${ST.drawer}" aria-controls="sb">${ic('menu')}</button><span class="t" title="${SCHOOL}">${SCHOOL}</span><button class="ib" type="button" aria-label="Search" aria-keyshortcuts="Control+K Meta+K">${ic('search')}</button><button class="ib" type="button" aria-label="${ME.fn} ${ME.ln} · ${ME.role}">${av(ME, 32, true)}</button></header>
  <div class="shell">${sidebar()}<div class="main"><div class="panel" ${isPhone() && ST.drawer ? 'inert' : ''}><main class="page" id="main">${body}</main></div></div></div>${ST.dlg ? DLG() : ''}`;
  window.lucide && lucide.createIcons({ attrs: { 'stroke-width': 2 } });
  if (ST.dlg) document.querySelector('.dlg .btn').focus();
}
window.render = render;
document.addEventListener('click', e => {
  const t = e.target.closest('[data-tab],[data-act],[data-go],[data-role]'); if (e.target.closest('a[href="#"]')) e.preventDefault(); if (!t) { if (ST.menu) { ST.menu = false; render(); } return; }
  if (t.dataset.tab) { ST.tab = t.dataset.tab; ST.menu = false; render(); document.querySelector(`[role=tab][data-tab="${ST.tab}"]`)?.focus(); return; }
  const a = t.dataset.act;
  if (a === 'menu') { ST.menu = !ST.menu; render(); if (ST.menu) document.querySelector('.menu .mi')?.focus(); return; }
  if (a === 'dlg') { ST.dlg = true; ST.menu = false; render(); return; }
  if (a === 'dlgx') { ST.dlg = false; render(); document.querySelector('[data-act=menu]')?.focus(); return; }
  if (a === 'drawer') { ST.drawer = !ST.drawer; render(); return; }
  if (t.dataset.role) { ST.page = 'profile'; ST.role = t.dataset.role; ST.tab = null; render(); return; }
  if (t.dataset.go) { const g = t.dataset.go; ST.page = g === 'dashboard' ? 'dashboard' : 'students'; ST.drawer = false; render(); scrollTo(0, 0); }
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && (ST.menu || ST.dlg || ST.drawer)) { ST.menu = false; ST.dlg = false; ST.drawer = false; render(); document.querySelector('[data-act=menu]')?.focus(); }
  const tl = e.target.closest && e.target.closest('[role=tablist]');
  if (tl && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) { const b = [...tl.querySelectorAll('[role=tab]')], i = b.indexOf(e.target), n = b[(i + (e.key === 'ArrowRight' ? 1 : -1) + b.length) % b.length]; ST.tab = n.dataset.tab; render(); document.querySelector(`[role=tab][data-tab="${ST.tab}"]`)?.focus(); e.preventDefault(); }
  if (e.key === 'Tab' && ST.dlg) { const f = [...document.querySelectorAll('.dlg .btn')]; if (e.shiftKey && document.activeElement === f[0]) { f[f.length - 1].focus(); e.preventDefault(); } else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { f[0].focus(); e.preventDefault(); } }
});
let rz; addEventListener('resize', () => { clearTimeout(rz); rz = setTimeout(render, 100); });
render();
addEventListener('load', () => setTimeout(() => { const s = document.createElement('script'); s.src = 'https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js'; s.async = true; s.onload = () => lucide.createIcons({ attrs: { 'stroke-width': 2 } }); document.body.appendChild(s); }, 0));
window.__m = () => {
  const vw = document.documentElement.clientWidth, R = e => e.getBoundingClientRect();
  const small = [...document.querySelectorAll('.btn,.tabs button,select,.ib,.search,.mi,.row,.acct')].filter(e => e.offsetParent !== null && R(e).width > 0 && R(e).height < 43.5 && !e.classList.contains('row') && !e.classList.contains('sm'));
  const sm = [...document.querySelectorAll('.btn.sm')].filter(e => e.offsetParent !== null && R(e).height < 35.5);
  const h1 = document.querySelector('main h1'), cs = h1 && getComputedStyle(h1);
  const q = document.querySelector('main .qsch'), qs = q && getComputedStyle(q);
  const rows = []; for (const g of document.querySelectorAll('.grid2')) { const c = [...g.children]; for (let i = 0; i + 1 < c.length; i += 2) { const a = R(c[i]), b = R(c[i + 1]); if (Math.abs(a.top - b.top) < 1) rows.push(Math.round(Math.abs(a.height - b.height))); } }
  return { vw, over: document.documentElement.scrollWidth - vw, small: small.length, smallList: small.slice(0, 4).map(e => (e.textContent.trim() || e.getAttribute('aria-label') || '').slice(0, 18)), secondary36: sm.length,
    h1: h1 ? `${h1.textContent.trim().slice(0, 24)} · ${cs.fontSize}/${cs.fontWeight}` : null, quiet: qs ? `${qs.fontSize}/${qs.fontWeight}` : null,
    topbar: [...document.querySelectorAll('.topA,.topP')].filter(e => getComputedStyle(e).display !== 'none').map(e => e.className + ' ' + Math.round(R(e).height)),
    tileLinks: document.querySelectorAll('.kt .kl').length, tileActs: document.querySelectorAll('.kt .acts .btn').length, quickActions: document.querySelectorAll('.qa,.qt').length,
    pairDiff: rows, tabs: document.querySelectorAll('.tabs [role=tab]').length };
};
})();
