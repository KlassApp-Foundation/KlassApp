// Demo data — Demo Junior School (nursery and primary). Demo only; no real people.
window.KD = (() => {
  const PAL = ['#1E6FD9', '#15803D', '#B45309', '#1E293B'];
  const SCHOOL = { name: 'Demo Junior School', no: '007', currency: 'UGX', year: '2026', term: 'Term 3' }; // currency comes from school settings
  const FIRST = ['Amara','Liam','Sofia','Noah','Aisha','Mateo','Grace','Yusuf','Priya','Ethan','Zara','Kofi','Mei','Omar','Lucía','Daniel','Nia','Arjun','Hana','Samuel','Leila','Tomás','Imani','Ravi','Elena','Musa','Chloe','Ibrahim','Ana','Joseph','Fatima','Lucas'];
  const LAST = ['Okafor','Chen','Haddad','Mensah','Rahman','García','Wanjiru','Demir','Nair','Brooks','Ali','Asante','Tanaka','Farouk','Morales','Kim','Ndlovu','Patel','Sato','Okello','Karimi','Silva','Mwangi','Iyer','Petrova','Bello','Martin','Hassan','Costa','Achieng','Yilmaz','Rossi'];
  const CLASSES = [
    { id: 'n1', name: 'Nursery', streams: ['Sunflower'], n: 22, ct: 't7', avg: null, att: 95 },
    { id: 'rc', name: 'Reception', streams: ['Sunflower'], n: 24, ct: 't8', avg: null, att: 94 },
    { id: 'p1', name: 'Primary 1', streams: ['Blue', 'Red'], n: 31, ct: 't2', avg: 74, att: 96 },
    { id: 'p2', name: 'Primary 2', streams: ['Blue', 'Red'], n: 29, ct: 't3', avg: 71, att: 93 },
    { id: 'p3', name: 'Primary 3', streams: ['Blue'], n: 28, ct: 't4', avg: 69, att: 92 },
    { id: 'p4', name: 'Primary 4', streams: ['Blue'], n: 30, ct: 't5', avg: 66, att: 91 },
    { id: 'p5', name: 'Primary 5', streams: ['Blue', 'Red'], n: 32, ct: 't1', avg: 68, att: 94 },
    { id: 'p6', name: 'Primary 6', streams: ['Blue'], n: 27, ct: 't6', avg: 63, att: 90 },
  ];
  const T = [
    { id: 't1', fn: 'Sarah', ln: 'Nakato', email: 's.nakato@demojunior.school', ph: '+000 700 100 101', role: 'Teacher', invite: 'accepted', cls: [['Primary 5 · Blue', 'Mathematics, Science'], ['Primary 6 · Blue', 'Mathematics']], ctOf: 'Primary 5 · Blue', lessons: 24, pendAtt: 0, pendMarks: ['Science · Primary 5 Blue'], last: '2 hours ago' },
    { id: 't2', fn: 'Daniel', ln: 'Mensah', email: 'd.mensah@demojunior.school', ph: '+000 700 100 102', role: 'Teacher', invite: 'accepted', cls: [['Primary 1 · Blue', 'English, Literacy']], ctOf: 'Primary 1 · Blue', lessons: 20, pendAtt: 1, pendMarks: [], last: 'Yesterday' },
    { id: 't3', fn: 'Mei', ln: 'Tanaka', email: 'm.tanaka@demojunior.school', ph: '+000 700 100 103', role: 'Teacher', invite: 'invited', cls: [['Primary 2 · Blue', 'English']], ctOf: 'Primary 2 · Blue', lessons: 18, pendAtt: 0, pendMarks: ['English · Primary 2 Blue'], last: '' },
    { id: 't4', fn: 'Omar', ln: 'Farouk', email: '', ph: '+000 700 100 104', role: 'Teacher', invite: 'none', cls: [['Primary 3 · Blue', 'Social Studies']], ctOf: 'Primary 3 · Blue', lessons: 16, pendAtt: 1, pendMarks: [], last: '' },
    { id: 't5', fn: 'Elena', ln: 'Petrova', email: 'e.petrova@demojunior.school', ph: '+000 700 100 105', role: 'Teacher', invite: 'accepted', cls: [['Primary 4 · Blue', 'Mathematics']], ctOf: 'Primary 4 · Blue', lessons: 22, pendAtt: 0, pendMarks: [], last: '3 days ago' },
    { id: 't6', fn: 'Samuel', ln: 'Asante', email: 's.asante@demojunior.school', ph: '+000 700 100 106', role: 'Head teacher', invite: 'accepted', cls: [['Primary 6 · Blue', 'English']], ctOf: 'Primary 6 · Blue', lessons: 10, pendAtt: 0, pendMarks: [], last: 'Today' },
    { id: 't7', fn: 'Hana', ln: 'Sato', email: 'h.sato@demojunior.school', ph: '+000 700 100 107', role: 'Teacher', invite: 'accepted', cls: [['Nursery · Sunflower', 'All areas']], ctOf: 'Nursery · Sunflower', lessons: 25, pendAtt: 0, pendMarks: [], last: 'Today' },
    { id: 't8', fn: 'Ravi', ln: 'Iyer', email: 'r.iyer@demojunior.school', ph: '+000 700 100 108', role: 'Teacher', invite: 'accepted', cls: [['Reception · Sunflower', 'All areas']], ctOf: 'Reception · Sunflower', lessons: 25, pendAtt: 0, pendMarks: [], last: 'Today' },
  ].map((t, i) => ({ ...t, n: i + 11, status: 'active' }));
  const S = Array.from({ length: 32 }, (_, i) => {
    const fn = FIRST[i], ln = LAST[(i * 7) % 32], g = i % 2 ? 'Male' : 'Female';
    const bal = [0, 180000, 0, 95000, 0, 0, 240000, 0][i % 8];
    return { id: 1040 + i, fn, ln, sex: i === 9 ? '' : g, kls: 'KLS007' + String(1 + i).padStart(4, '0'), cls: i === 5 ? '' : 'Primary 5 · ' + (i % 3 ? 'Blue' : 'Red'),
      status: i === 12 ? 'inactive' : 'active', parent: i === 7 ? null : { fn: ['Grace','Peter','Ana','Musa'][i % 4], ln, ph: '+000 772 418 2' + String(10 + i).padStart(2, '0') },
      att: 88 + (i * 3) % 12, avg: 58 + (i * 11) % 34, bal, dob: '14 Mar 2015' };
  });
  S[0] = { ...S[0], fn: 'Amara', ln: 'Okafor', avg: 74, att: 91, bal: 180000, pos: 6 };
  const P = [{ id: 2210, fn: 'Grace', ln: 'Okafor', ph: '+000 772 418 205', email: 'grace.okafor@example.com', wa: 'in', waDate: '12 Sep 2026', last: 'Today, 07:42', kids: [S[0], { id: 1090, fn: 'Tobi', ln: 'Okafor', kls: 'KLS0070033', cls: 'Primary 2 · Blue', bal: 0, att: 97, avg: 81 }] },
    { id: 2211, fn: 'Peter', ln: 'Chen', ph: '+000 701 552 930', email: '', wa: 'none', last: 'Never', kids: [S[1]] },
    { id: 2212, fn: 'Ana', ln: 'Haddad', ph: '+000 755 003 118', email: 'ana.h@example.com', wa: 'in', waDate: '2 Oct 2026', last: '3 days ago', kids: [S[2]] },
    { id: 2213, fn: 'Musa', ln: 'Mensah', ph: '+000 782 660 471', email: '', wa: 'pending', last: 'Never', kids: [S[3], S[11]] }];
  const ini = (f, l) => { f = (f || '').trim().split(/\s+/)[0] || ''; l = (l || '').trim(); return (([...f][0] || '') + ([...l][0] || '')).toLocaleUpperCase(); };
  const av = (p, s = 40) => { const i = ini(p.fn, p.ln); const r = s <= 32 ? 8 : 12; return `<span class="av" style="width:${s}px;height:${s}px;font-size:${Math.round(s * .4)}px;border-radius:${r}px;background:${i ? PAL[p.id ? (typeof p.id === 'number' ? p.id : p.n || 0) % 4 : 0] : '#64748B'}" aria-hidden="true">${i}</span>`; };
  const money = v => SCHOOL.currency + ' ' + Number(v).toLocaleString('en');
  const grade = a => a >= 80 ? 'A' : a >= 70 ? 'B' : a >= 60 ? 'C' : a >= 50 ? 'D' : 'E';
  const ic = (n, c = 'ic') => `<i data-lucide="${n}" class="${c}" aria-hidden="true"></i>`;
  return { SCHOOL, CLASSES, T, S, P, av, ini, money, grade, ic, PAL };
})();
