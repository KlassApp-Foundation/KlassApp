const { useState } = React;
const { Card, Button, Icon } = window.KlassAppDesignSystem_df5836;

/* Step keys, order, labels and the optional/checkpoint rules are taken from the
   real ManualOnboardingWizard Livewire component and its Blade partial.
   OPTIONAL_STEPS: teachers, students — Next with an empty draft list = skip. */
const STEPS = [
  { key: 'school_name', label: 'School name', icon: '🏫', title: 'School name' },
  { key: 'student_size', label: 'Student size', icon: '👥', title: 'Approximate number of students' },
  { key: 'country', label: 'Country', icon: '📍', title: 'Country' },
  { key: 'curriculum', label: 'Curriculum', icon: '📘', title: 'Board / Curriculum' },
  { key: 'school_category', label: 'Category', icon: '🏷️', title: 'School category' },
  { key: 'emis', label: 'EMIS code', icon: '🆔', title: 'EMIS / Ministry code' },
  { key: 'uneb_center', label: 'UNEB centre', icon: '📄', title: 'UNEB centre number' },
  { key: 'academic_year', label: 'Academic year', icon: '📅', title: 'Academic year' },
  { key: 'standards', label: 'Classes & streams', icon: '🚪', title: 'Structure & class teachers' },
  { key: 'subjects', label: 'Subjects', icon: '📚', title: 'Subjects' },
  { key: 'teachers', label: 'Teachers', icon: '👨‍🏫', title: 'Add your teachers', optional: true },
  { key: 'students', label: 'Students', icon: '🎒', title: 'Add your students', optional: true },
  { key: 'terms', label: 'Terms', icon: '🗓️', title: 'First term' },
  { key: 'fees', label: 'Fees', icon: '💰', title: 'First fee' },
  { key: 'whatsapp_verify', label: 'WhatsApp', icon: '💬', title: 'Verify WhatsApp' },
  { key: 'plan_selection', label: 'Plan', icon: '💳', title: 'Choose a plan' },
  { key: 'review', label: 'Review', icon: '✅', title: 'Review your setup' },
];

const PLANS = [
  { id: 1, name: 'Freemium', price: 'Free to start', hint: 'Default on signup' },
  { id: 2, name: 'School', price: 'UGX 420,000 / term', hint: 'Most schools pick this' },
  { id: 3, name: 'Group', price: 'Talk to us', hint: 'Multiple campuses' },
];

const SEEDED_CLASSES = [
  { section_id: 1, name: 'P1', streams: [], class_teacher_id: null, class_teacher_name: '', class_teacher_email: '' },
  { section_id: 2, name: 'P2', streams: [{ label: 'A' }, { label: 'B' }], class_teacher_id: 7, class_teacher_name: 'Grace Nakamya', class_teacher_email: 'grace@school.ug' },
  { section_id: 3, name: 'P3', streams: [], class_teacher_id: null, class_teacher_name: '', class_teacher_email: '' },
];

function WizardApp() {
  const [i, setI] = useState(0);
  const [finished, setFinished] = useState(false);
  const [returnToReview, setReturnToReview] = useState(false);
  const [skipped, setSkipped] = useState([]);
  const [d, setD] = useState({
    schoolName: '', studentSize: '', countryName: 'Uganda', curriculum: 'uneb', schoolCategory: '',
    ministryCode: '', unebCenterNumber: '', academicYearDescription: '2026', academicYearStart: '2026-02-02', academicYearEnd: '2026-12-04',
    structureClasses: SEEDED_CLASSES, structureTeachers: [{ id: 7, name: 'Grace Nakamya', email: 'grace@school.ug' }, { id: 9, name: 'John Ssali', email: 'john@school.ug' }],
    existingSubjectNames: ['Mathematics', 'English', 'Science', 'Social Studies'], subjectName: '',
    teacherDrafts: [], studentDrafts: [],
    termName: 'Term 1', termStart: '2026-02-02', termEnd: '2026-05-08',
    feeName: '', feeAmount: '', waNumber: '', plans: PLANS, selectedPlanId: 1, errorMessage: '',
  });
  const set = (k, v) => setD((p) => ({ ...p, [k]: v }));

  const reviewIndex = STEPS.length - 1;
  const step = STEPS[i];

  function go(n) { setI(n); set('errorMessage', ''); }

  function next() {
    if (returnToReview) { setReturnToReview(false); go(reviewIndex); return; }
    // Real checkpoints: academic_year always lands on standards once; standards always lands on subjects once.
    go(Math.min(i + 1, reviewIndex));
  }

  function skipOptional() {
    if (!step.optional) return;
    setSkipped((s) => s.includes(step.key) ? s : [...s, step.key]);
    next();
  }

  const reviewRows = [
    { key: 'school_name', icon: '🏫', label: 'School', value: d.schoolName || 'Not set' },
    { key: 'country', icon: '📍', label: 'Country', value: d.countryName },
    { key: 'school_category', icon: '🏷️', label: 'Category', value: d.schoolCategory || 'Not set' },
    { key: 'academic_year', icon: '📅', label: 'Academic year', value: d.academicYearDescription },
    { key: 'standards', icon: '🚪', label: 'Classes', value: d.structureClasses.map((c) => c.name + (c.streams.length ? ' (' + c.streams.map((s) => s.label).join(', ') + ')' : '')).join(', ') },
    { key: 'subjects', icon: '📚', label: 'Subjects', value: d.existingSubjectNames.length + ' set up' },
    { key: 'teachers', icon: '👨‍🏫', label: 'Teachers', value: skipped.includes('teachers') ? 'Skipped' : (d.teacherDrafts.length ? d.teacherDrafts.length + ' added' : 'None yet') },
    { key: 'students', icon: '🎒', label: 'Students', value: skipped.includes('students') ? 'Skipped' : (d.studentDrafts.length ? d.studentDrafts.length + ' added' : 'None yet') },
    { key: 'plan_selection', icon: '💳', label: 'Plan', value: (PLANS.find((p) => p.id === d.selectedPlanId) || {}).name || 'Not chosen' },
  ];

  const SUGGESTIONS = [
    { title: 'Manage classes & streams', body: 'Split a class into streams, or assign the class teachers you skipped.', href: '#' },
    { title: 'Add more students', body: 'A spreadsheet with names and parent numbers is enough.', href: '#' },
    { title: 'Review your plan', body: 'Capacity limits are based on the plan you picked.', href: '#' },
  ];

  function editSection(key) {
    const idx = STEPS.findIndex((s) => s.key === key);
    if (idx < 0) return;
    setReturnToReview(true);
    go(idx);
  }

  let body;
  if (finished) body = <StepDone d={d} suggestions={SUGGESTIONS} />;
  else if (step.key === 'school_name') body = <StepSchoolName d={d} set={set} />;
  else if (step.key === 'student_size') body = <StepStudentSize d={d} set={set} />;
  else if (step.key === 'country') body = <StepCountry d={d} set={set} />;
  else if (step.key === 'curriculum') body = <StepCurriculum d={d} set={set} />;
  else if (step.key === 'school_category') body = <StepCategory d={d} set={set} />;
  else if (step.key === 'emis') body = <StepEmis d={d} set={set} />;
  else if (step.key === 'uneb_center') body = <StepUneb d={d} set={set} />;
  else if (step.key === 'academic_year') body = <StepAcademicYear d={d} set={set} />;
  else if (step.key === 'standards') body = <StepStandards d={d} set={set} />;
  else if (step.key === 'subjects') body = <StepSubjects d={d} set={set} />;
  else if (step.key === 'teachers') body = <StepTeachers d={d} set={set} onSkip={skipOptional} />;
  else if (step.key === 'students') body = <StepStudents d={d} set={set} onSkip={skipOptional} />;
  else if (step.key === 'terms') body = <StepTerms d={d} set={set} />;
  else if (step.key === 'fees') body = <StepFees d={d} set={set} />;
  else if (step.key === 'whatsapp_verify') body = <StepWhatsApp d={d} set={set} />;
  else if (step.key === 'plan_selection') body = <StepPlan d={d} set={set} />;
  else body = <StepReview rows={reviewRows} onEdit={editSection} />;

  return (
    <div style={{ minHeight: '100vh', background: 'var(--d-canvas)', padding: '32px 20px' }}>
      <div className="manual-wizard">
        <div style={{ display: 'flex', alignItems: 'center', gap: 11, marginBottom: 22 }}>
          <img src="../../assets/brand/klassapp-icon.svg" height="32" alt="" />
          <span style={{ fontFamily: 'var(--d-font-display)', fontWeight: 700, fontSize: 21, letterSpacing: '-0.02em', color: 'var(--d-dark)' }}>KlassApp</span>
          <span style={{ marginLeft: 'auto', fontSize: 13, color: 'var(--d-muted)' }}>Setting up without Toshi</span>
        </div>

        {finished ? null : (
          <div className="setup-banner" style={{ marginBottom: 4 }}>
            <div className="setup-banner-icon"><Icon name="whatsapp" size={20} /></div>
            <div className="setup-banner-body">
              <div className="setup-banner-title">Rather not fill forms?</div>
              <div className="setup-banner-text">Toshi can set the whole school up from a spreadsheet and a short chat.</div>
              <div className="setup-banner-actions">
                <a className="ds-btn ds-btn-outline ds-btn-sm" href="../toshi-assistant/index.html">Let Toshi do it</a>
              </div>
            </div>
          </div>
        )}

        <Card className="manual-wizard-card" padding="lg">
          {finished ? null : (
            <div style={{ marginBottom: 18 }}>
              <h1 className="ds-page-head-title" style={{ marginBottom: 4 }}>{step.title}</h1>
              <p className="ds-page-head-sub">
                {`Step ${i + 1} of ${STEPS.length}`}
                {step.optional ? ' · optional' : ''}
                {returnToReview ? ' · Next returns you to review' : ''}
              </p>
            </div>
          )}
          {d.errorMessage ? <p className="ds-form-error" style={{ marginTop: 0 }}>{d.errorMessage}</p> : null}
          {body}
        </Card>

        {finished ? (
          <div style={{ textAlign: 'center', marginTop: 24 }}>
            <Button variant="primary" href="../school-dashboard/index.html">Go to the dashboard</Button>
          </div>
        ) : (
          <div className="manual-wizard-nav">
            <Button variant="ghost" size="sm" onClick={() => go(Math.max(0, i - 1))} disabled={i === 0}>← Previous</Button>
            <div className="manual-wizard-progress">
              {STEPS.map((s, n) => (
                <button key={s.key} title={s.label}
                  className={'manual-wizard-dot' + (n < i ? ' is-complete' : '') + (n === i ? ' is-current' : '') + (s.key === 'review' ? ' is-review' : '')}
                  onClick={() => go(n)} />
              ))}
            </div>
            {i === reviewIndex
              ? <Button variant="success" size="sm" onClick={() => setFinished(true)}>Confirm &amp; finish</Button>
              : <Button variant="primary" size="sm" onClick={next}>Continue →</Button>}
          </div>
        )}
      </div>
    </div>
  );
}

document.body.classList.add('toshi-manual-wizard');
ReactDOM.createRoot(document.getElementById('root')).render(<WizardApp />);
