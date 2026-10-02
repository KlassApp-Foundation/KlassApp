const { KpiCard, Card, Table, Badge, Button, WhatsAppMark, GoogleDriveMark, Icon } = window.KlassAppDesignSystem_df5836;

function PageHead({ title, sub, action }) {
  return (
    <div className="ds-page-head">
      <div>
        <h1 className="ds-page-head-title">{title}</h1>
        <p className="ds-page-head-sub">{sub}</p>
      </div>
      {action}
    </div>
  );
}

function DashboardHome({ onNav }) {
  return (
    <div className="dashboard-shell dashboard-shell--admin">
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 16, flexWrap: 'wrap' }}>
        <div>
          <h1 className="dashboard-title">Good morning, Josephine</h1>
          <p className="dashboard-subtitle">Term 2 2026 · Week 6 · 1,284 students enrolled</p>
        </div>
        <span className="dashboard-live-badge"><span className="dashboard-live-dot"></span>Live</span>
      </div>

      <div className="dashboard-kpi-grid">
        <KpiCard icon="users" value="1,284" label="Total Students" color="blue" link="#students" />
        <KpiCard icon="dollar" value="UGX 18.4M" label="Fees Collected" color="amber" link="#fees" />
        <KpiCard icon="whatsapp" value="1,102" label="WhatsApp Linked" color="green" />
        <KpiCard icon="exam" value="12" label="Upcoming Exams" color="purple" />
      </div>

      <div className="dashboard-topfold" style={{ display: 'grid', gridTemplateColumns: '1.6fr 1fr', gap: 20 }}>
        <div>
          <h2 className="ds-section-title">Fees collected this week</h2>
          <p className="ds-section-subtitle">UGX 4.2M of a UGX 6.0M target</p>
          <div style={{ display: 'flex', alignItems: 'flex-end', gap: 10, height: 128 }}>
            {[['Mon', 52], ['Tue', 68], ['Wed', 41], ['Thu', 84], ['Fri', 96], ['Sat', 30]].map(([d, v]) => (
              <div key={d} style={{ flex: 1, textAlign: 'center' }}>
                <div style={{ height: v + '%', background: 'rgba(34,197,94,0.18)', borderTop: '3px solid var(--d-green)', borderRadius: '6px 6px 0 0' }}></div>
                <div style={{ fontSize: 11, color: 'var(--d-muted)', marginTop: 6 }}>{d}</div>
              </div>
            ))}
          </div>
        </div>
        <div>
          <h2 className="ds-section-title">Connected tools</h2>
          <p className="ds-section-subtitle">KlassApp runs inside what the school already uses</p>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, fontSize: 13, color: 'var(--d-text)' }}>
              <WhatsAppMark style={{ width: 22, height: 22 }} /> 1,102 parents reachable
              <span style={{ marginLeft: 'auto' }}><Badge variant="active">Live</Badge></span>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, fontSize: 13, color: 'var(--d-text)' }}>
              <GoogleDriveMark style={{ width: 22 }} /> Reports filed to Drive
              <span style={{ marginLeft: 'auto' }}><Badge variant="active">Live</Badge></span>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 10, fontSize: 13, color: 'var(--d-text)' }}>
              <Icon name="bell" size={20} style={{ color: 'var(--d-muted)' }} /> Notice board
              <span style={{ marginLeft: 'auto' }}><Badge variant="pending">3 drafts</Badge></span>
            </div>
          </div>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginTop: 20 }}>
        <Card title="Recent payments" padding="none">
          <Table headers={['Student', 'Amount', 'Method', 'Status']} density="compact">
            <tr><td data-label="Student">Nakato Sarah</td><td className="dt-cell-num" data-label="Amount">UGX 450,000</td><td data-label="Method">SchoolPay</td><td className="dt-cell-badge" data-label="Status"><Badge variant="paid">Paid</Badge></td></tr>
            <tr><td data-label="Student">Mukasa David</td><td className="dt-cell-num" data-label="Amount">UGX 300,000</td><td data-label="Method">Mobile Money</td><td className="dt-cell-badge" data-label="Status"><Badge variant="warning">Part paid</Badge></td></tr>
            <tr><td data-label="Student">Namuli Grace</td><td className="dt-cell-num" data-label="Amount">UGX 120,000</td><td data-label="Method">Cash</td><td className="dt-cell-badge" data-label="Status"><Badge variant="paid">Paid</Badge></td></tr>
          </Table>
          <div style={{ padding: '12px 16px' }}>
            <Button variant="ghost" size="sm" onClick={() => onNav('fees')}>View all payments →</Button>
          </div>
        </Card>
        <Card title="Needs your attention">
          <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
            {[['Senior 4 marks not submitted', 'Mr. Opio · due Friday', 'amber'],
              ['38 parents without WhatsApp', 'Reception can collect numbers', 'blue'],
              ['Term 2 report cards ready', 'Publish to send 1,102 messages', 'green']].map(([t, s, c]) => (
              <div key={t} style={{ display: 'flex', gap: 10, alignItems: 'flex-start' }}>
                <span className={'ds-dot ds-dot-' + c} style={{ marginTop: 6 }}></span>
                <div>
                  <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--d-text)' }}>{t}</div>
                  <div style={{ fontSize: 12, color: 'var(--d-muted)' }}>{s}</div>
                </div>
              </div>
            ))}
          </div>
          <div style={{ marginTop: 16, display: 'flex', gap: 8 }}>
            <Button variant="primary" size="sm">Publish reports</Button>
            <Button variant="outline" size="sm">Remind teachers</Button>
          </div>
        </Card>
      </div>
    </div>
  );
}

Object.assign(window, { DashboardHome, PageHead });
