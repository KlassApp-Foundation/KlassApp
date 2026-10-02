const { Card, Button, Badge } = window.KlassAppDesignSystem_df5836;

const SUBJECTS = ['MTC', 'ENG', 'PHY', 'CHE', 'BIO', 'HIS'];
const PUPILS = [
  ['01', 'Nakato Sarah', [82, 74, 68, 71, 79, 65], 439, 12, 3],
  ['02', 'Mukasa David', [64, 70, 59, 62, 66, 58], 379, 18, 11],
  ['03', 'Namuli Grace', [91, 85, 80, 77, 88, 74], 495, 8, 1],
  ['04', 'Ssempala Isaac', [55, 61, 49, 53, 60, 52], 330, 24, 19],
];

function ExamsScreen() {
  return (
    <div className="dashboard-shell dashboard-shell--teacher">
      <PageHead
        title="End of term marks"
        sub="Senior 2 · Term 2 2026 · 46 students"
        action={<div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
          <span className="ds-save-indicator ds-save-indicator--saved"><span className="ds-save-indicator__dot"></span>All marks saved</span>
          <Button variant="outline" size="sm">Download sheet</Button>
          <Button variant="primary" size="sm">Publish to parents</Button>
        </div>}
      />
      <Card padding="none">
        <div className="ds-table-wrap">
          <table className="ds-grid-marks">
            <thead>
              <tr>
                <th>#</th>
                <th>Student</th>
                {SUBJECTS.map((s) => <th key={s}>{s}<span className="gm-subject-code">/100</span></th>)}
                <th>Total</th>
                <th>Agg</th>
                <th>Pos</th>
              </tr>
            </thead>
            <tbody>
              {PUPILS.map((p) => (
                <tr key={p[0]}>
                  <td>{p[0]}</td>
                  <td>{p[1]}</td>
                  {p[2].map((m, i) => <td key={i}>{m}</td>)}
                  <td className="gm-total">{p[3]}</td>
                  <td className="gm-agg">{p[4]}</td>
                  <td className="gm-pos">{p[5]}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
      <div className="ds-reminder-banner" style={{ marginTop: 16 }}>
        <div style={{ fontFamily: 'var(--d-font-display)', fontWeight: 600, fontSize: 14, color: 'var(--d-dark)' }}>Two subjects still missing marks</div>
        <div style={{ fontSize: 13, color: 'var(--d-text-secondary)', marginTop: 4 }}>
          Geography and Agriculture have no entries for Senior 2. Publishing now sends incomplete report cards to 46 parents.
        </div>
        <div style={{ marginTop: 10, display: 'flex', gap: 8 }}>
          <Button variant="warning" size="sm">Remind subject teachers</Button>
          <Button variant="ghost" size="sm">Publish anyway</Button>
        </div>
      </div>
    </div>
  );
}

function PlaceholderScreen({ title }) {
  return (
    <div className="dashboard-shell">
      <PageHead title={title} sub="Not recreated in this kit" />
      <Card>
        <div className="ds-empty-state">
          <p className="ds-empty-state-title">No source for this screen</p>
          <p className="ds-empty-state-desc">
            The supplied KlassApp bundle contains styling for this area but no screen markup, so it is left blank
            on purpose rather than invented. Dashboard, Students, Fee payments and Exams are the recreated views.
          </p>
        </div>
      </Card>
    </div>
  );
}

Object.assign(window, { ExamsScreen, PlaceholderScreen });
