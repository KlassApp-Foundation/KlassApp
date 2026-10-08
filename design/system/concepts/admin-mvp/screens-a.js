// Shell, dashboard, people list, classes — admin MVP concept
(() => {
const { SCHOOL, CLASSES, T, S, P, av, money, grade, ic } = KD;
const ST = window.ST;
// Groups follow dashboard v2 (handoff-2026-09-30-profiles Part B)
const NAV = [['', [['dashboard', 'layout-dashboard', 'Dashboard']]], ['People', [['students', 'graduation-cap', 'Students'], ['teachers', 'presentation', 'Teachers and staff'], ['parents', 'users', 'Parents']]],
  ['Academics', [['classes', 'school', 'Classes and streams'], ['subjects', 'book-open', 'Subjects'], ['attendance', 'calendar-check', 'Attendance'], ['exams', 'clipboard-list', 'Exams and marks'], ['reports', 'file-text', 'Report cards']]],
  ['Money', [['fees', 'wallet', 'Fees']]], ['Messages', [['messages', 'message-circle', 'WhatsApp']]], ['School', [['settings', 'settings', 'Settings'], ['help', 'life-buoy', 'Help']]]];
const ME = { id: 3300, fn: 'Mucunguzi', ln: 'Moses', email: 'mucunguzi.moses.admin@demojunior.school' };
function acctPop(mob) {
  return `<div class="pop" role="menu" aria-label="Account" id="acct-pop">
  <div class="who">${av(ME, 40)}<span style="min-width:0"><b class="trunc">${ME.fn} ${ME.ln}</b><small class="trunc">${ME.email}</small></span></div>
  <a class="mi" role="menuitem" href="#" data-go="me">${ic('user-round')}Edit profile</a>
  <a class="mi" role="menuitem" href="#">${ic('key-round')}Change password</a>
  <a class="mi" role="menuitem" href="#">${ic('settings')}Settings</a>
  <div class="sep" role="separator"></div>
  <button class="mi" role="menuitem" type="button">${ic('log-out')}Log out</button></div>`;
}
window.shell = (active, title, body) => {
  const nav = NAV.map(([g, items]) => `${g ? `<div class="grp">${g}</div>` : ''}<ul class="nav">${items.map(([k, i, l]) => `<li><a href="#" data-go="${k}" ${k === active ? 'aria-current="page"' : ''}>${ic(i)}${l}</a></li>`).join('')}</ul>`).join('');
  return `<div class="shell">
  <aside class="side ${ST.drawer ? 'open' : ''}" aria-label="Main menu" id="side">
    <div class="brand"><img src="../../assets/brand/klassapp-horizontal-light.svg" alt="KlassApp"></div>
    <nav aria-label="Main">${nav}</nav>
    <div class="grow"></div>
    ${ST.hideSetup && ST.state !== 'data' ? `<a class="schip" href="#" aria-label="Finish setup, ${ST.state === 'new' ? 1 : 4} of 7 steps done"><span><b>Finish setup</b><span class="pill">${ST.state === 'new' ? 1 : 4}/7</span></span><small>Next: ${ST.state === 'new' ? 'Add your students' : 'Set up fees for Term 3'}</small><span class="bar"><i style="width:${(ST.state === 'new' ? 1 : 4) / 7 * 100}%"></i></span></a>` : ''}
    <div class="soon"><img src="../../assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant · coming soon</div>
    <div class="acct">${ST.acct && !ST.mob ? acctPop() : ''}
      <button class="acct-btn" type="button" data-act="acct" aria-haspopup="menu" aria-expanded="${ST.acct && !ST.mob}" aria-controls="acct-pop">${av(ME, 40)}<span style="min-width:0"><b class="trunc">${ME.fn} ${ME.ln}</b><small class="trunc">${ME.email}</small></span>${ic('chevrons-up-down', 'ic ic-sm')}</button></div>
  </aside>
  <div class="mainw">
    <header class="topbar"><button class="btn icon ghost" type="button" data-act="drawer" aria-label="Open menu" aria-expanded="${ST.drawer}" aria-controls="side">${ic('menu')}</button><span class="t">${title}</span>
      <div class="acct"><button class="btn icon ghost" type="button" data-act="acct" aria-label="Account" aria-haspopup="menu" aria-expanded="${ST.acct && ST.mob}">${av(ME, 32)}</button>${ST.acct && ST.mob ? acctPop(1) : ''}</div></header>
    <div class="panel"><main class="page" id="main">${body}</main></div>
  </div></div>${ST.dlg ? dialog(ST.dlg) : ''}`;
};
function dialog(d) {
  return `<div class="dlg-bg" data-act="dlg-x"><div class="dlg" role="alertdialog" aria-modal="true" aria-labelledby="dlg-t" aria-describedby="dlg-d" data-stop>
  <h2 id="dlg-t">${d.t}</h2><p id="dlg-d">${d.p}</p><div class="row-acts"><button class="btn" type="button" data-act="dlg-x">Cancel</button><button class="btn danger" type="button" data-act="dlg-x">${d.b}</button></div></div></div>`;
}
const ayPick = () => `<label class="ay">Academic year <select class="sel" aria-label="Academic year"><option selected>2026</option><option>2025</option></select></label>`;
/* ---------- 1. Dashboard ---------- */
const bars = (rows, max = 100) => `<div class="hbars">${rows.map(r => `<div class="hb"><span>${r[0]}</span><span class="t" role="img" aria-label="${r[0]}: ${r[1]}%"><i style="width:${r[1] / max * 100}%;${r[2] ? 'background:' + r[2] : ''}"></i></span><b>${r[1]}%</b></div>`).join('')}</div>`;
function line(pts, w = 520, h = 160) {
  const mn = 80, mx = 100, x = i => 36 + i * (w - 48) / (pts.length - 1), y = v => 12 + (mx - v) / (mx - mn) * (h - 40);
  const d = pts.map((p, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(p[1]).toFixed(1)).join(' ');
  return `<svg class="ch" viewBox="0 0 ${w} ${h}" role="img" aria-label="Attendance trend: ${pts.map(p => p[0] + ' ' + p[1] + '%').join(', ')}">
  ${[80, 90, 100].map(v => `<line x1="36" x2="${w - 12}" y1="${y(v)}" y2="${y(v)}" stroke="#E2E8F0"/><text x="30" y="${y(v) + 4}" text-anchor="end" font-size="11" fill="#475569">${v}%</text>`).join('')}
  <path d="${d}" fill="none" stroke="#15803D" stroke-width="2.5"/>${pts.map((p, i) => `<circle cx="${x(i)}" cy="${y(p[1])}" r="3.5" fill="#15803D"/><text x="${x(i)}" y="${h - 8}" text-anchor="middle" font-size="11" fill="#475569">${p[0]}</text>`).join('')}</svg>`;
}
function cols(vals, exp, w = 520, h = 170) {
  const mx = Math.max(...exp), bw = (w - 60) / vals.length, y = v => (h - 34) - v / mx * (h - 50);
  return `<svg class="ch" viewBox="0 0 ${w} ${h}" role="img" aria-label="Fees collected by month: ${vals.map(v => v[0] + ' ' + money(v[1])).join(', ')}">
  ${vals.map((v, i) => { const x = 40 + i * bw; return `<rect x="${x + 8}" y="${y(exp[i])}" width="${bw - 16}" height="${(h - 34) - y(exp[i])}" fill="none" stroke="#94A3B8" stroke-dasharray="4 3" rx="4"/><rect x="${x + 8}" y="${y(v[1])}" width="${bw - 16}" height="${(h - 34) - y(v[1])}" fill="#15803D" rx="4"/><text x="${x + bw / 2}" y="${h - 14}" text-anchor="middle" font-size="11" fill="#475569">${v[0]}</text><text x="${x + bw / 2}" y="${y(v[1]) - 6}" text-anchor="middle" font-size="11" font-weight="700" fill="#0F172A">${Math.round(v[1] / 1e6 * 10) / 10}M</text>`; }).join('')}</svg>`;
}
function kpi(icon, l, v, s, cls = '', meter) {
  return `<div class="kpi"><span class="l">${ic(icon, 'ic ic-sm')}${l}</span><span class="v">${v}</span>${meter != null ? `<span class="meter" role="img" aria-label="${meter}%"><i style="width:${meter}%"></i></span>` : ''}<span class="s ${cls}">${s}</span></div>`;
}
const gender = (g, b, u) => `<div class="stack" role="img" aria-label="Girls ${g}%, boys ${b}%, not specified ${u}%"><i style="width:${g}%;background:#B45309"></i><i style="width:${b}%;background:#1E6FD9"></i><i style="width:${u}%;background:#64748B"></i></div><div class="legend"><span><i style="background:#B45309"></i>Girls ${g}%</span><span><i style="background:#1E6FD9"></i>Boys ${b}%</span><span><i style="background:#64748B"></i>Not specified ${u}%</span></div>`;
window.scrDashboard = () => {
  const st = ST.state;
  const steps = st === 'new' ? 1 : st === 'mid' ? 4 : 7;
  const next = st === 'new' ? 'Add your students' : 'Set up fees for Term 3';
  const setup = steps < 7 && !ST.hideSetup ? `<div class="setup" role="region" aria-label="School setup">${ic('list-checks')}<b>Setup ${steps} of 7 done</b><span class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="7" aria-valuenow="${steps}" aria-label="Setup progress"><i style="width:${steps / 7 * 100}%"></i></span><span class="nx">Next: ${next}</span><a class="btn pri" href="#">Continue setup</a><button class="btn icon ghost" type="button" data-act="hideSetup" aria-label="Hide setup bar. Progress stays in the sidebar.">${ic('x')}</button></div>` : '';
  const QA = [['user-plus', 'Add students', 'One by one or from a spreadsheet', ''], ['clipboard-list', 'Enter marks', st === 'new' ? 'Needs students and an exam' : 'Mid-term exams are open', st === 'new'], ['file-text', 'Generate report cards', st === 'data' ? '140 ready to generate' : 'Needs marks for an exam', st !== 'data'], ['message-circle', 'Send report cards on WhatsApp', st === 'data' ? '142 sent last term' : 'Needs report cards', st !== 'data'], ['wallet', 'Fees', st === 'data' ? 'Record payments and send reminders' : 'Set up Term 3 fees first', st !== 'data']];
  const qaRow = `<nav class="qa" aria-label="Quick actions">${QA.map(q => `<a class="btn" href="#">${ic(q[0])}${q[1]}</a>`).join('')}</nav>`;
  const qaTiles = `<section aria-label="Quick actions"><h2 class="sh">Quick actions</h2><div class="qat">${QA.map(q => `<a class="qt" href="#"><span class="ib">${ic(q[0])}</span><span><b>${q[1]}</b><span class="${q[3] ? 'pre' : ''}">${q[2]}</span></span></a>`).join('')}</div></section>`;
  const head = `<div class="ph"><div><h1>${greet()}, ${ME.fn}</h1><p>${SCHOOL.name} · ${SCHOOL.term}, ${SCHOOL.year}</p></div><div class="row-acts">${ayPick()}</div></div>`;
  if (st === 'new') return head + setup + qaTiles + `<div class="kpis">${kpi('graduation-cap', 'Students', '0', 'None added yet')}${kpi('presentation', 'Staff', '1', 'Just you')}${kpi('calendar-check', 'Attendance this week', '–', 'Starts after students are added')}${kpi('wallet', 'Fees collected', '–', 'No fee structure yet')}${kpi('file-text', 'Report cards ready', '–', 'After the first exam')}</div>
  <div class="empty"><b>Add your students to get started</b><p>Your dashboard fills in as you add students, take attendance and enter marks. You can add students one by one or import a spreadsheet.</p><div class="row-acts"><a class="btn pri" href="#">${ic('user-plus')}Add student</a><a class="btn" href="#">${ic('upload')}Import a list</a></div></div>
  <p class="toshi-soon"><img src="../../assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant, is coming soon.</p>`;
  const mid = st === 'mid';
  return head + setup + (mid ? '' : qaRow) + `<div class="kpis">
  ${kpi('graduation-cap', 'Students', '248', '+12 this term', 'up')}${kpi('presentation', 'Staff', '18', '16 teachers · 2 admin')}
  ${kpi('calendar-check', 'Attendance this week', '93.4%', '▲ 1.2 pts on last week', 'up')}
  ${mid ? kpi('wallet', 'Fees collected', '–', 'Set up Term 3 fees first', 'warn') : kpi('wallet', 'Fees collected', '62%', money(46500000) + ' of ' + money(75000000), '', 62)}
  ${kpi('file-text', 'Report cards ready', mid ? '0' : '140', mid ? 'No exam closed yet' : 'of 248 · Mid-term exams', mid ? '' : '', mid ? null : 56)}</div>
  ${mid ? qaTiles : ''}<div class="grid2">
   <div class="card"><div class="hd"><h2>Performance by class</h2><small>${mid ? 'No exam yet' : 'Mid-term exams · average mark'}</small></div>${mid ? `<div class="empty in"><b>No marks entered yet</b><p>Averages appear when teachers enter marks for an exam.</p><a class="btn" href="#">Go to exams</a></div>` : bars(CLASSES.filter(c => c.avg).map(c => [c.name, c.avg]))}</div>
   <div class="card"><div class="hd"><h2>Attendance trend</h2><small>Last 8 weeks · whole school</small></div>${line([['W1', 91], ['W2', 92], ['W3', 90], ['W4', 93], ['W5', 94], ['W6', 92], ['W7', 92.2], ['W8', 93.4]])}</div>
   <div class="card"><div class="hd"><h2>Students by gender</h2><small>248 students</small></div>${gender(51, 48, 1)}</div>
   <div class="card"><div class="hd"><h2>Fees collection</h2><small>Collected against expected, by month</small></div>${mid ? `<div class="empty in"><b>No fee structure for Term 3</b><p>Set the term's fees to start recording payments.</p><a class="btn pri" href="#">Set up fees</a></div>` : cols([['Sep', 24100000], ['Oct', 14900000], ['Nov', 7500000]], [30000000, 25000000, 20000000]) + `<div class="legend"><span><i style="background:#15803D"></i>Collected</span><span><i style="border:1px dashed #94A3B8"></i>Expected</span></div>`}</div>
  </div>
  <div class="card"><div class="hd"><h2>Recent activity</h2><a href="#">See all</a></div><ul class="act">
   <li><span class="ib">${ic('calendar-check', 'ic ic-sm')}</span><span>Sarah Nakato took attendance for <b>Primary 5 · Blue</b> (30 of 32 present)</span><time>08:12</time></li>
   <li><span class="ib">${ic('wallet', 'ic ic-sm')}</span><span>Payment of ${money(270000)} recorded for <b>Amara Okafor</b></span><time>Yesterday</time></li>
   <li><span class="ib">${ic('clipboard-list', 'ic ic-sm')}</span><span>Mathematics marks entered for <b>Primary 6 · Blue</b></span><time>Yesterday</time></li>
   <li><span class="ib">${ic('message-circle', 'ic ic-sm')}</span><span>142 report cards sent to parents on WhatsApp</span><time>Mon</time></li>
   <li><span class="ib">${ic('user-plus', 'ic ic-sm')}</span><span>3 students added to <b>Reception · Sunflower</b></span><time>Mon</time></li></ul></div>
  <p class="toshi-soon"><img src="../../assets/brand/klassapp-icon.svg" alt="">Toshi, your school's AI assistant, is coming soon.</p>`;
};
function greet() { const h = new Date().getHours(); return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening'; }
/* ---------- 2. People list (students / teachers / parents) ---------- */
const CFG = {
  students: { t: 'Students', one: 'student', add: 'Add student', rows: () => S.slice(0, 10), chips: [['all', 'All', 248], ['cls', 'Class: All', null, 'chevron-down'], ['active', 'Active', 241], ['inactive', 'Inactive', 7], ['nocls', 'No class', 3], ['nopar', 'No parent', 5]],
    cols: ['Student', 'KLS number', 'Class', 'Parent or guardian', 'Status'],
    cell: s => [`<span class="who">${av(s, 36)}<span style="min-width:0"><a href="#" data-go="student">${s.fn} ${s.ln}</a></span></span>`, `<span class="mono">${s.kls}</span>`, s.cls || '<span class="badge b-warn">No class</span>', s.parent ? `${s.parent.fn} ${s.parent.ln}` : '<span class="badge b-warn">No parent</span>', st(s.status)],
    card: s => [`${s.fn} ${s.ln}`, `<span class="mono">${s.kls}</span><span>${s.cls || '<span class="badge b-warn">No class</span>'}</span>${s.status !== 'active' ? st(s.status) : ''}`],
    menu: ['View profile', 'Edit', 'Move to class', 'Message parent'], bulk: ['Message parents', 'Move to class', 'Export'], go: 'student' },
  teachers: { t: 'Teachers', one: 'teacher', add: 'Add teacher', rows: () => T, chips: [['all', 'All', 18], ['active', 'Active', 17], ['inv', 'Not yet invited', 1], ['pend', 'Invite pending', 1], ['ct', 'Class teachers', 8]],
    cols: ['Teacher', 'Teaches', 'Class teacher of', 'Invite', 'Status'],
    cell: t => [`<span class="who">${av(t, 36)}<span style="min-width:0"><a href="#" data-go="teacher">${t.fn} ${t.ln}</a><span class="sub trunc">${t.role}</span></span></span>`, t.cls.map(c => c[1]).join(', '), t.ctOf || '–', inv(t.invite), st(t.status)],
    card: t => [`${t.fn} ${t.ln}`, `<span>${t.ctOf ? 'Class teacher · ' + t.ctOf : t.role}</span>${t.invite !== 'accepted' ? inv(t.invite) : ''}`],
    menu: ['View profile', 'Edit', 'Send invite', 'Assign classes'], bulk: ['Send invites', 'Export'], go: 'teacher' },
  parents: { t: 'Parents', one: 'parent', add: 'Add parent', rows: () => P.concat(P.map(p => ({ ...p, id: p.id + 10, fn: p.fn === 'Grace' ? 'Joy' : p.fn === 'Peter' ? 'Ruth' : p.fn === 'Ana' ? 'Ade' : 'Lina' }))), chips: [['all', 'All', 211], ['wa', 'On WhatsApp', 188], ['nowa', 'Not opted in', 23], ['never', 'Never logged in', 41]],
    cols: ['Parent or guardian', 'Children', 'Phone', 'WhatsApp', 'Last login'],
    cell: p => [`<span class="who">${av(p, 36)}<span style="min-width:0"><a href="#" data-go="parent">${p.fn} ${p.ln}</a></span></span>`, p.kids.map(k => k.fn).join(', '), `<span class="mono">${p.ph}</span>`, wa(p.wa), p.last],
    card: p => [`${p.fn} ${p.ln}`, `<span>${p.kids.map(k => k.fn).join(', ')}</span>${wa(p.wa)}`],
    menu: ['View profile', 'Edit', 'Link a child', 'Send WhatsApp opt-in'], bulk: ['Send WhatsApp opt-in', 'Export'], go: 'parent' } };
const st = s => s === 'active' ? '<span class="badge b-ok">Active</span>' : '<span class="badge b-off">Inactive</span>';
const inv = s => s === 'accepted' ? '<span class="badge b-ok">Joined</span>' : s === 'invited' ? '<span class="badge b-info">Invited</span>' : '<span class="badge b-warn">Not invited</span>';
const wa = s => s === 'in' ? '<span class="badge b-ok">Opted in</span>' : s === 'pending' ? '<span class="badge b-info">Asked</span>' : '<span class="badge b-off">Not opted in</span>';
window.peopleList = (kind, opts = {}) => {
  const c = CFG[kind], rows = opts.rows || c.rows(), state = opts.state || ST.state;
  const chipList = opts.chips || c.chips.filter(x => !(opts.inClass && x[0] === 'cls'));
  const tools = `<div class="lt"><label class="search"><span class="sr">Search ${c.t.toLowerCase()}</span>${ic('search')}<input type="search" placeholder="Search by name${kind === 'students' ? ', KLS number' : kind === 'parents' ? ', phone' : ', email'}"></label>
   <div class="chips" role="group" aria-label="Filters">${chipList.map((x, i) => `<button class="chip" type="button" aria-pressed="${i === 0}">${x[1]}${x[2] != null ? ` <span class="n">${x[2]}</span>` : ''}${x[3] ? ic(x[3], 'ic ic-sm') : ''}</button>`).join('')}</div></div>`;
  const sel = ST.sel.size;
  const bulk = sel ? `<div class="bulk" role="region" aria-label="Bulk actions"><b>${sel} selected</b>${c.bulk.map(b => `<button class="btn" type="button">${b}</button>`).join('')}<button class="btn" type="button" data-act="clr">Clear</button></div>` : '';
  if (state === 'empty') return tools + `<div class="empty"><b>No ${c.t.toLowerCase()} yet</b><p>${kind === 'students' ? 'Add students one by one, or import a spreadsheet with names and classes.' : kind === 'teachers' ? 'Add your teaching staff, then send each one an invite to join.' : 'Parents are added with their children, or you can add them here and link them.'}</p><div class="row-acts"><a class="btn pri" href="#">${ic('user-plus')}${c.add}</a><a class="btn" href="#">${ic('upload')}Import a list</a></div></div>`;
  if (state === 'nomatch') return tools + `<div class="empty"><b>No ${c.t.toLowerCase()} match “Zed”</b><p>Check the spelling or clear the filters to search all ${c.t.toLowerCase()}.</p><button class="btn" type="button">Clear filters</button></div>`;
  const loading = state === 'loading';
  const menu = r => `<button class="btn icon ghost" type="button" data-act="rmenu" data-id="${r.id}" aria-haspopup="menu" aria-expanded="${ST.menu == r.id}" aria-label="Actions for ${r.fn} ${r.ln}">${ic('ellipsis-vertical')}</button>${ST.menu == r.id ? `<div class="rmenu" role="menu">${c.menu.map((m, i) => `<a class="mi" role="menuitem" href="#" ${i === 0 ? `data-go="${c.go}"` : ''}>${m}</a>`).join('')}</div>` : ''}`;
  const ck = r => `<label class="ckb"><input type="checkbox" data-act="ck" data-id="${r.id}" ${ST.sel.has(String(r.id)) ? 'checked' : ''} aria-label="Select ${r.fn} ${r.ln}"></label>`;
  const head = `<thead><tr><th class="ck"><label class="ckb"><input type="checkbox" data-act="ckall" ${sel === rows.length ? 'checked' : ''} aria-label="Select all on this page"></label></th>${c.cols.map(h => `<th scope="col">${h}</th>`).join('')}<th class="menu"><span class="sr">Actions</span></th></tr></thead>`;
  const sk = '<span class="skel" style="width:70%"></span>';
  const body = loading ? Array.from({ length: 6 }, () => `<tr aria-hidden="true"><td class="ck"></td>${c.cols.map((_, i) => `<td>${i ? sk : '<span class="who"><span class="skel" style="width:36px;height:36px;border-radius:12px"></span><span class="skel" style="width:140px"></span></span>'}</td>`).join('')}<td></td></tr>`).join('')
    : rows.map(r => `<tr class="${ST.sel.has(String(r.id)) ? 'sel' : ''}"><td class="ck">${ck(r)}</td>${c.cell(r).map(x => `<td>${x}</td>`).join('')}<td class="menu">${menu(r)}</td></tr>`).join('');
  const cards = loading ? Array.from({ length: 5 }, () => `<div class="pc" aria-hidden="true"><span></span><span class="skel" style="width:40px;height:40px;border-radius:12px"></span><span><span class="skel" style="width:60%"></span><span class="skel" style="width:40%;margin-top:6px"></span></span><span></span></div>`).join('')
    : rows.map(r => { const [n, l2] = c.card(r); return `<div class="pc ${ST.sel.has(String(r.id)) ? 'sel' : ''}">${ck(r)}${av(r, 40)}<div style="min-width:0"><a class="nm trunc" href="#" data-go="${c.go}">${n}</a><div class="ln2">${l2}</div></div><div style="position:relative">${menu(r)}</div></div>`; }).join('');
  return tools + bulk + `<div class="tbl" ${loading ? 'aria-busy="true"' : ''}>${loading ? '<span class="sr" role="status">Loading ' + c.t.toLowerCase() + '…</span>' : ''}<table class="pl">${head}<tbody>${body}</tbody></table><div class="cards">${cards}</div>
  <div class="pager"><span>${loading ? '&nbsp;' : `Showing 1–${rows.length} of ${opts.total || c.chips[0][2]}`}</span><span class="row-acts"><button class="btn icon" type="button" aria-label="Previous page" disabled>${ic('chevron-left')}</button><button class="btn icon" type="button" aria-label="Next page">${ic('chevron-right')}</button></span></div></div>`;
};
window.scrList = kind => {
  const c = CFG[kind];
  return `<div class="ph"><div><h1>${c.t}</h1><p>${c.chips[0][2]} ${c.t.toLowerCase()} at ${SCHOOL.name}</p></div><div class="row-acts"><a class="btn" href="#">${ic('upload')}Import</a><a class="btn pri" href="#">${ic('user-plus')}${c.add}</a></div></div>` + peopleList(kind);
};
/* ---------- 6. Classes (class teacher per stream, class-level default) ---------- */
const streamTeacher = (c, i) => i === 0 || c.streams.length === 1 ? { t: T.find(x => x.id === c.ct), own: true } : { t: T.find(x => x.id === c.ct), own: false };
const lvl = c => c.id === 'n1' || c.id === 'rc' ? 'Nursery' : 'Primary';
window.scrClasses = () => {
  if (ST.state === 'empty') return `<div class="ph"><div><h1>Classes and streams</h1></div></div><div class="empty"><b>No classes yet</b><p>Add the classes your school teaches, from nursery to the final year. Add streams if a class is split into groups.</p><div class="row-acts"><a class="btn pri" href="#">${ic('plus')}Add class</a></div></div>`;
  return `<div class="ph"><div><h1>Classes and streams</h1><p>${CLASSES.length} classes · ${CLASSES.reduce((a, c) => a + c.streams.length, 0)} streams · Mid-term exams</p></div><div class="row-acts"><a class="btn" href="#">${ic('plus')}Add stream</a><a class="btn pri" href="#">${ic('plus')}Add class</a></div></div>
  <div class="lt"><label class="search"><span class="sr">Find a class</span>${ic('search')}<input type="search" placeholder="Find a class"></label><div class="chips" role="group" aria-label="Level"><button class="chip" type="button" aria-pressed="true">All <span class="n">8</span></button><button class="chip" type="button" aria-pressed="false">Nursery <span class="n">2</span></button><button class="chip" type="button" aria-pressed="false">Primary <span class="n">6</span></button></div></div>
  <div class="ccards">${CLASSES.map(c => `<article class="cc"><span class="kick">${lvl(c)}</span><a class="t" href="#" data-go="class">${c.name}</a>
    <ul class="strl">${c.streams.map((s, i) => { const x = streamTeacher(c, i); return `<li><span>${s}</span><span class="tch">${av(x.t, 24)}<span class="trunc">${x.t.fn} ${x.t.ln}</span>${x.own ? '' : '<span class="badge b-off">Class default</span>'}</span></li>`; }).join('')}</ul>
    <div class="st"><span>${c.n} students</span><span>Attendance ${c.att}%</span></div>
    <div class="hint">${c.avg ? `<span>Average <b>${c.avg}%</b> · ${grade(c.avg)}</span><span>${c.id === 'p6' ? '▼ 2 since last exam' : '▲ 3 since last exam'}</span>` : '<span>No exams for this class</span>'}</div></article>`).join('')}</div>`;
};
window.scrClass = () => {
  const c = CLASSES.find(x => x.id === 'p5'), t = T[0];
  const subj = [['Mathematics', T[0]], ['English', T[2]], ['Science', T[0]], ['Social Studies', T[3]], ['Religious Education', null], ['Creative Arts', T[6]]];
  const dist = [['A', 6, '#15803D'], ['B', 9, '#1E6FD9'], ['C', 10, '#B45309'], ['D', 5, '#1E293B'], ['E', 2, '#B91C1C']];
  const ST2 = [{ s: 'Blue', n: 16, avg: 70, att: 95, own: true }, { s: 'Red', n: 16, avg: 66, att: 93, own: false }];
  const blue = S.filter(s => s.cls.endsWith('Blue')).slice(0, 8);
  return `<nav aria-label="Breadcrumb" style="font-size:14px"><a href="#" data-go="classes">Classes</a> <span aria-hidden="true">›</span> Primary 5</nav>
  <div class="ph"><div><h1>Primary 5</h1><p>2 streams · ${SCHOOL.term}, ${SCHOOL.year}</p></div><div class="row-acts"><a class="btn" href="#">${ic('calendar-check')}Take attendance</a><a class="btn" href="#">${ic('clipboard-list')}Enter marks</a><button class="btn icon" type="button" aria-label="More actions">${ic('ellipsis')}</button></div></div>
  <div class="kpis k4">
   <div class="kpi"><span class="l">${ic('user-round-check', 'ic ic-sm')}Class teacher (default)</span><span style="display:flex;align-items:center;gap:10px;min-width:0">${av(t, 32)}<a href="#" data-go="teacher" class="trunc" style="font-weight:700">${t.fn} ${t.ln}</a></span><span class="s">For any stream without its own</span></div>
   ${kpi('graduation-cap', 'Students', '32', '17 girls · 15 boys')}${kpi('chart-column', 'Average · Mid-term', '68% · C', '▲ 3 pts since last exam', 'up')}${kpi('calendar-check', 'Attendance this week', '94%', '30 of 32 present today')}</div>
  <div class="card"><div class="hd"><h2>Streams</h2><a href="#">${ic('plus', 'ic ic-sm')} Add stream</a></div><div class="sgrid">${ST2.map(x => `<div class="scard"><b>Primary 5 · ${x.s}</b>
   <div class="tch">${av(t, 32)}<span style="min-width:0"><a href="#" data-go="teacher" class="trunc" style="font-weight:700">${t.fn} ${t.ln}</a><span class="sub">${x.own ? 'Class teacher of this stream' : 'Class default · no teacher assigned to this stream'}</span></span></div>
   ${x.own ? '' : '<a class="btn" href="#">Assign a class teacher</a>'}
   <div class="st"><span>${x.n} students</span><span>Average ${x.avg}%</span><span>Attendance ${x.att}%</span></div></div>`).join('')}</div></div>
  <div class="grid3"><div class="card"><div class="hd"><h2>Grade distribution</h2><small>Mid-term exams · 32 students</small></div>
   <div class="stack" role="img" aria-label="${dist.map(d => d[0] + ': ' + d[1]).join(', ')}">${dist.map(d => `<i style="width:${d[1] / 32 * 100}%;background:${d[2]}"></i>`).join('')}</div><div class="legend">${dist.map(d => `<span><i style="background:${d[2]}"></i>${d[0]} · ${d[1]}</span>`).join('')}</div>
   <div class="hd" style="margin-top:6px"><h3>Students by gender</h3></div>${gender(53, 47, 0)}</div>
   <div class="card"><div class="hd"><h2>Subjects</h2><small>6</small></div><ul class="subj">${subj.map(([s, tt]) => `<li><span>${s}</span><span class="tch">${tt ? av(tt, 28) + `<span class="trunc">${tt.fn} ${tt.ln}</span>` : '<span class="badge b-warn">No teacher</span>'}</span></li>`).join('')}</ul></div></div>
  <h2 class="sh">Students</h2>` + peopleList('students', { inClass: 1, rows: blue, total: 32, chips: [['all', 'All streams', 32], ['b', 'Blue', 16], ['r', 'Red', 16], ['active', 'Active', 31], ['nopar', 'No parent', 2]] });
};
window.KC = { bars, line, cols, kpi, gender, ayPick };
})();
