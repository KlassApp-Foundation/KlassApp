const { useState } = React;
const { Button, FormGroup, Icon } = window.KlassAppDesignSystem_df5836;

/* Step keys, order and copy are verbatim from manual-wizard-step-fields.blade.php.
   Values marked INFERRED come from PHP constants that were not supplied. */
const CURRICULA = { uneb: 'UNEB (Uganda National Examinations Board)', cambridge: 'Cambridge', montessori: 'Montessori', other: 'Other' };
const COUNTRIES = { Uganda: 'Uganda', Kenya: 'Kenya', Tanzania: 'Tanzania' }; // blade's own empty-DB fallback
const SIZES = { '1-100': '1-100', '101-300': '101-300', '301-600': '301-600', '601-1000': '601-1000', '1000+': '1000+' }; // INFERRED: OnboardingStepsService::STUDENT_SIZE_OPTIONS not supplied
const CATEGORIES = { nursery: 'Nursery / Kindergarten', primary: 'Primary (P1-P7)', o_level: 'Secondary O-Level (S1-S4)', a_level: 'Secondary A-Level (S5-S6)', primary_secondary: 'Primary & Secondary' }; // INFERRED: SchoolCategorySeeder::CATEGORIES not supplied

function Help({ children }) {
  return <p style={{ fontSize: 12, color: '#64748B', margin: '4px 0 0' }}>{children}</p>;
}

function StepSchoolName({ d, set }) {
  return <FormGroup label="School name" name="schoolName" required placeholder="e.g. Sunrise Academy" value={d.schoolName} onChange={(e) => set('schoolName', e.target.value)} />;
}

function StepStudentSize({ d, set }) {
  return <FormGroup label="Approximate number of students" name="studentSize" required type="select" placeholder="Select range" options={SIZES} value={d.studentSize} onChange={(e) => set('studentSize', e.target.value)} help="Helps tailor setup defaults for your school. You can change this later." />;
}

function StepCountry({ d, set }) {
  return <FormGroup label="Country" name="countryName" required type="select" options={COUNTRIES} value={d.countryName} onChange={(e) => set('countryName', e.target.value)} help="Saves both country and Toshi registration country." />;
}

function StepCurriculum({ d, set }) {
  return <FormGroup label="Board / Curriculum" name="curriculum" required type="select" options={CURRICULA} value={d.curriculum} onChange={(e) => set('curriculum', e.target.value)} />;
}

function StepCategory({ d, set }) {
  return (
    <div className="ds-form-group">
      <label className="ds-form-label ds-form-label-required">School category</label>
      <Help>Sets the default classes, subjects, and grading system. Everything stays editable later.</Help>
      <div className="manual-wizard-plan-grid" role="radiogroup" aria-label="School category" style={{ marginTop: 10 }}>
        {Object.entries(CATEGORIES).map(([value, label]) => (
          <button type="button" key={value} role="radio" aria-checked={d.schoolCategory === value}
            className={'manual-wizard-plan-card' + (d.schoolCategory === value ? ' is-selected' : '')}
            onClick={() => set('schoolCategory', value)}>
            <span className="manual-wizard-plan-name">{label}</span>
          </button>
        ))}
      </div>
    </div>
  );
}

function StepEmis({ d, set }) {
  return <FormGroup label="EMIS / Ministry code" name="ministryCode" required placeholder="e.g. EMIS-1001" value={d.ministryCode} onChange={(e) => set('ministryCode', e.target.value)} />;
}

function StepUneb({ d, set }) {
  return <FormGroup label="UNEB centre number" name="unebCenterNumber" placeholder="Optional — leave blank to skip" value={d.unebCenterNumber} onChange={(e) => set('unebCenterNumber', e.target.value)} help="Optional for UNEB schools. Leave blank if you do not have one yet." />;
}

function StepAcademicYear({ d, set }) {
  return (
    <div>
      <FormGroup label="Description" name="academicYearDescription" value={d.academicYearDescription} onChange={(e) => set('academicYearDescription', e.target.value)} />
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '0 16px' }}>
        <FormGroup label="Starts on" name="academicYearStart" required type="date" value={d.academicYearStart} onChange={(e) => set('academicYearStart', e.target.value)} />
        <FormGroup label="Ends on" name="academicYearEnd" required type="date" value={d.academicYearEnd} onChange={(e) => set('academicYearEnd', e.target.value)} />
      </div>
    </div>
  );
}

/* The Structure & Class Teacher checkpoint. Classes arrive pre-seeded from the
   school category; streams and CT invites are both optional and persist
   immediately (addStructureStream / inviteStructureClassTeacher). */
function StepStandards({ d, set }) {
  const [flash, setFlash] = useState('');
  const [streamDrafts, setStreamDrafts] = useState({});
  const [ctDrafts, setCtDrafts] = useState({});

  function addStream(sid, name) {
    const label = (streamDrafts[sid] || '').trim();
    if (!label) { setFlash(''); set('errorMessage', 'Enter a stream name (e.g. A, East, Science).'); return; }
    set('errorMessage', '');
    set('structureClasses', d.structureClasses.map((c) => c.section_id === sid ? { ...c, streams: [...c.streams, { label }] } : c));
    setStreamDrafts({ ...streamDrafts, [sid]: '' });
    setFlash('Added stream \u201C' + label + '\u201D to ' + name + '.');
  }

  function invite(sid, name) {
    const draft = ctDrafts[sid] || {};
    const email = (draft.email || '').trim();
    if (!email && !draft.existing_teacher_id) { set('errorMessage', 'Enter an email address for the class teacher.'); return; }
    set('errorMessage', '');
    const label = draft.existing_teacher_id ? d.structureTeachers.find((t) => String(t.id) === String(draft.existing_teacher_id)).name : (draft.name || '').trim() || email;
    set('structureClasses', d.structureClasses.map((c) => c.section_id === sid ? { ...c, class_teacher_id: 1, class_teacher_name: label, class_teacher_email: email } : c));
    setFlash('Invited ' + label + ' as class teacher for ' + name + '.');
  }

  const setCt = (sid, k, v) => setCtDrafts({ ...ctDrafts, [sid]: { ...(ctDrafts[sid] || {}), [k]: v } });

  return (
    <div className="manual-wizard-structure">
      <p style={{ fontSize: 14, color: '#64748B', margin: '0 0 16px', lineHeight: 1.5 }}>
        Your classes are ready from school category. Optionally add streams or invite a class teacher — both are optional. Click Continue anytime to skip.
      </p>
      {flash ? <div style={{ fontSize: 14, fontWeight: 500, color: '#15803D', marginBottom: 16 }}>{flash}</div> : null}
      <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
        {d.structureClasses.map((c) => (
          <div key={c.section_id} style={{ border: '1px solid #E2E8F0', borderRadius: 10, padding: 16 }}>
            <div style={{ display: 'flex', flexWrap: 'wrap', alignItems: 'flex-start', justifyContent: 'space-between', gap: 8, marginBottom: 12 }}>
              <div>
                <h3 style={{ fontFamily: 'var(--d-font-display)', fontWeight: 600, fontSize: 15, color: '#0F172A', margin: 0 }}>{c.name}</h3>
                {c.streams.length ? (
                  <p style={{ fontSize: 12, color: '#64748B', margin: '4px 0 0' }}>Streams:{' '}
                    {c.streams.map((s) => <span key={s.label} style={{ display: 'inline-block', background: '#F1F5F9', padding: '1px 8px', borderRadius: 4, marginRight: 4 }}>{s.label}</span>)}
                  </p>
                ) : <p style={{ fontSize: 12, color: '#64748B', margin: '4px 0 0' }}>No streams yet — undivided base class.</p>}
              </div>
              <div style={{ fontSize: 12, color: '#64748B' }}>
                {c.class_teacher_name
                  ? <span>CT: <strong>{c.class_teacher_name}</strong> {c.class_teacher_email ? <span style={{ color: '#94A3B8' }}>({c.class_teacher_email})</span> : null}</span>
                  : <span style={{ color: '#94A3B8' }}>No class teacher yet</span>}
              </div>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 16 }}>
              <div className="ds-form-group" style={{ marginBottom: 0 }}>
                <label className="ds-form-label">Add stream</label>
                <div style={{ display: 'flex', gap: 8 }}>
                  <input className="ds-form-input" placeholder="e.g. A, East, Science" value={streamDrafts[c.section_id] || ''}
                    onChange={(e) => setStreamDrafts({ ...streamDrafts, [c.section_id]: e.target.value })} />
                  <Button variant="outline" size="sm" onClick={() => addStream(c.section_id, c.name)}>Add</Button>
                </div>
              </div>
              {!c.class_teacher_id ? (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                  <label className="ds-form-label">Invite Class Teacher</label>
                  <input className="ds-form-input" inputMode="email" placeholder="teacher@school.ug" value={(ctDrafts[c.section_id] || {}).email || ''} onChange={(e) => setCt(c.section_id, 'email', e.target.value)} />
                  <select className="ds-form-input ds-form-select" value={(ctDrafts[c.section_id] || {}).existing_teacher_id || ''} onChange={(e) => setCt(c.section_id, 'existing_teacher_id', e.target.value)}>
                    <option value="">— Create a new teacher —</option>
                    {d.structureTeachers.map((t) => <option key={t.id} value={t.id}>{t.name} ({t.email})</option>)}
                  </select>
                  {!((ctDrafts[c.section_id] || {}).existing_teacher_id) ? (
                    <React.Fragment>
                      <input className="ds-form-input" placeholder="Teacher name" value={(ctDrafts[c.section_id] || {}).name || ''} onChange={(e) => setCt(c.section_id, 'name', e.target.value)} />
                      <input className="ds-form-input" placeholder="Phone (optional)" value={(ctDrafts[c.section_id] || {}).phone || ''} onChange={(e) => setCt(c.section_id, 'phone', e.target.value)} />
                    </React.Fragment>
                  ) : null}
                  <Button variant="outline" size="sm" onClick={() => invite(c.section_id, c.name)}>Send invite</Button>
                </div>
              ) : null}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

function StepSubjects({ d, set }) {
  const seeded = d.existingSubjectNames;
  return (
    <div className="manual-wizard-subjects">
      {seeded.length ? (
        <div style={{ marginBottom: 16, borderRadius: 10, border: '1px solid #BBF7D0', background: '#F0FDF4', padding: '12px 16px' }}>
          <p style={{ fontSize: 14, fontWeight: 500, color: '#14532D', margin: 0 }}>Subjects already set up</p>
          <p style={{ fontSize: 12, color: '#166534', margin: '4px 0 0', lineHeight: 1.5 }}>These came from your school category (or a previous save). Review them below — click Next to continue, or add another subject.</p>
          <ul style={{ display: 'flex', flexWrap: 'wrap', gap: 8, listStyle: 'none', margin: '8px 0 0', padding: 0 }}>
            {seeded.map((s) => <li key={s} style={{ fontSize: 12, fontWeight: 500, padding: '4px 8px', borderRadius: 4, background: '#FFFFFF', border: '1px solid #BBF7D0', color: '#14532D' }}>{s}</li>)}
          </ul>
        </div>
      ) : null}
      <FormGroup label={seeded.length ? 'Add another subject (optional)' : 'First subject'} name="subjectName" required={!seeded.length}
        placeholder={seeded.length ? 'e.g. Music' : 'e.g. Mathematics'} value={d.subjectName} onChange={(e) => set('subjectName', e.target.value)} />
    </div>
  );
}

/* Optional bulk step. Three real input modes: file upload (with template
   download), a paste block, and single-add with name/email/phone. */
function BulkStep({ noun, drafts, onAdd, onRemove, onPaste, nameHelp, namePlaceholder, onSkip }) {
  const [paste, setPaste] = useState('');
  const [row, setRow] = useState({ name: '', email: '', phone: '' });
  return (
    <div className="manual-wizard-bulk">
      <div className="manual-wizard-bulk-toolbar">
        <a className="manual-wizard-bulk-link" href="#" download>Download template</a>
        <label className="manual-wizard-bulk-upload"><Icon name="upload" size={15} /><span>Upload file</span></label>
      </div>
      {drafts.length ? (
        <ul className="manual-wizard-bulk-list">
          {drafts.map((r, i) => (
            <li className="manual-wizard-bulk-item" key={r.name + i}>
              <span className="manual-wizard-bulk-item-main">
                <strong>{r.name}</strong>
                <span className="manual-wizard-bulk-meta">{r.email}{r.phone ? ' \u00B7 ' + r.phone : ''}</span>
              </span>
              <button className="manual-wizard-bulk-remove" aria-label="Remove" onClick={() => onRemove(i)}>&#10005;</button>
            </li>
          ))}
        </ul>
      ) : null}
      <div className="ds-form-group">
        <label className="ds-form-label">Paste names (one per line)</label>
        <textarea className="ds-form-input ds-form-textarea" rows={3} value={paste} placeholder={'John Ssali\nGrace Nakamya'} onChange={(e) => setPaste(e.target.value)} />
        <Button variant="outline" size="sm" className="mt-2" onClick={() => { onPaste(paste); setPaste(''); }}>Add from paste</Button>
      </div>
      <div className="manual-wizard-bulk-divider"><span>or add one at a time</span></div>
      <FormGroup label={noun === 'teacher' ? 'Teacher name' : 'Student name'} value={row.name} placeholder={namePlaceholder} help={nameHelp} onChange={(e) => setRow({ ...row, name: e.target.value })} />
      <FormGroup label="Email" value={row.email} placeholder={noun + '@school.ug'} onChange={(e) => setRow({ ...row, email: e.target.value })} />
      <FormGroup label="Phone (optional)" type="text" value={row.phone} placeholder="+2567…" onChange={(e) => setRow({ ...row, phone: e.target.value })} />
      <Button variant="outline" size="sm" onClick={() => { if (row.name.trim()) { onAdd(row); setRow({ name: '', email: '', phone: '' }); } }}>{`+ Add ${noun}`}</Button>
      <p style={{ fontSize: 12, color: '#64748B', margin: '12px 0 0' }}>{`Optional — skip if you’ll add ${noun}s later. Continue saves everyone in the list.`}</p>
      <div style={{ marginTop: 8 }}>
        <Button variant="ghost" size="sm" onClick={() => {
          if (drafts.length && !window.confirm(`You have ${noun}s in the list that will not be saved. Skip anyway?`)) return;
          onSkip();
        }}>Skip for now</Button>
      </div>
    </div>
  );
}

function StepTeachers({ d, set, onSkip }) {
  return <BulkStep noun="teacher" drafts={d.teacherDrafts} onSkip={onSkip}
    namePlaceholder="e.g. Jane Nabirye" nameHelp="Full name only — put the phone number in the Phone field below."
    onAdd={(r) => set('teacherDrafts', [...d.teacherDrafts, r])}
    onRemove={(i) => set('teacherDrafts', d.teacherDrafts.filter((_, j) => j !== i))}
    onPaste={(t) => set('teacherDrafts', [...d.teacherDrafts, ...t.split('\n').map((n) => n.trim()).filter(Boolean).map((n) => ({ name: n, email: '', phone: '' }))])} />;
}

function StepStudents({ d, set, onSkip }) {
  return <BulkStep noun="student" drafts={d.studentDrafts} onSkip={onSkip}
    namePlaceholder="e.g. Nakato Sarah" nameHelp="Full name only — class and stream are set after setup."
    onAdd={(r) => set('studentDrafts', [...d.studentDrafts, r])}
    onRemove={(i) => set('studentDrafts', d.studentDrafts.filter((_, j) => j !== i))}
    onPaste={(t) => set('studentDrafts', [...d.studentDrafts, ...t.split('\n').map((n) => n.trim()).filter(Boolean).map((n) => ({ name: n, email: '', phone: '' }))])} />;
}

function StepTerms({ d, set }) {
  return (
    <div>
      <FormGroup label="Term name" name="termName" required value={d.termName} onChange={(e) => set('termName', e.target.value)} />
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: '0 16px' }}>
        <FormGroup label="Starts on" name="termStart" required type="date" value={d.termStart} onChange={(e) => set('termStart', e.target.value)} />
        <FormGroup label="Ends on" name="termEnd" required type="date" value={d.termEnd} onChange={(e) => set('termEnd', e.target.value)} />
      </div>
    </div>
  );
}

function StepFees({ d, set }) {
  return (
    <div>
      <FormGroup label="Fee name" name="feeName" required placeholder="e.g. Tuition" value={d.feeName} onChange={(e) => set('feeName', e.target.value)} />
      <FormGroup label="Amount" name="feeAmount" required type="number" placeholder="450000" value={d.feeAmount} onChange={(e) => set('feeAmount', e.target.value)} />
    </div>
  );
}

function StepWhatsApp({ d, set }) {
  return (
    <div>
      <FormGroup label="Your WhatsApp number" name="waNumber" required placeholder="+2567…" value={d.waNumber} onChange={(e) => set('waNumber', e.target.value)} help="We send a one-time code to confirm the number parents will see." />
      <Button variant="outline" size="sm">Send code</Button>
    </div>
  );
}

function StepPlan({ d, set }) {
  return (
    <div>
      <p style={{ fontSize: 14, color: '#64748B', margin: '0 0 12px', lineHeight: 1.5 }}>
        Schools are free to start — pick a plan so capacity limits are clear. No payment is required now.
      </p>
      <div className="manual-wizard-plan-grid">
        {d.plans.map((p) => (
          <div key={p.id} className={'manual-wizard-plan-card' + (d.selectedPlanId === p.id ? ' is-selected' : '')} onClick={() => set('selectedPlanId', p.id)}>
            <span className="manual-wizard-plan-name">{p.name}</span>
            <span className="manual-wizard-plan-price">{p.price}</span>
            <span className="manual-wizard-plan-hint">{p.hint}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function StepReview({ rows, onEdit }) {
  return (
    <div className="manual-wizard-review">
      <p className="manual-wizard-review-intro">Check this over. Nothing is sent to parents until you publish something yourself.</p>
      <div className="manual-wizard-review-card">
        <div className="manual-wizard-review-header">Your school on KlassApp</div>
        <div className="manual-wizard-review-body">
          {rows.map((r) => (
            <div className="manual-wizard-review-row" key={r.key}>
              <span className="manual-wizard-review-row-main">
                <span className="manual-wizard-review-icon">{r.icon}</span>
                <span style={{ display: 'flex', flexDirection: 'column', gap: 2, minWidth: 0 }}>
                  <span className="manual-wizard-review-label">{r.label}</span>
                  <span className="manual-wizard-review-value">{r.value}</span>
                </span>
              </span>
              <a className="manual-wizard-review-edit" onClick={() => onEdit(r.key)}>Edit</a>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function StepDone({ d, suggestions }) {
  return (
    <div style={{ textAlign: 'center', padding: '12px 0 4px' }}>
      <div className="manual-wizard-done-icon">&#10003;</div>
      <h2 className="ds-section-title" style={{ marginBottom: 4 }}>{d.schoolName || 'Your school'} is set up</h2>
      <p className="ds-section-subtitle">Here is what most schools do next.</p>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 10, textAlign: 'left', marginTop: 16 }}>
        {suggestions.map((s) => (
          <a className="manual-wizard-suggestion" href={s.href} key={s.title}>
            <span className="manual-wizard-suggestion-title">{s.title}</span>
            <span className="manual-wizard-suggestion-body">{s.body}</span>
          </a>
        ))}
      </div>
    </div>
  );
}

Object.assign(window, { StepSchoolName, StepStudentSize, StepCountry, StepCurriculum, StepCategory, StepEmis, StepUneb, StepAcademicYear, StepStandards, StepSubjects, StepTeachers, StepStudents, StepTerms, StepFees, StepWhatsApp, StepPlan, StepReview, StepDone });
