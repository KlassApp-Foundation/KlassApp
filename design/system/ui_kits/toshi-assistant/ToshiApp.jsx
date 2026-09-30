const { useState } = React;
const { Card, Button, Badge, KpiCard } = window.KlassAppDesignSystem_df5836;

function ToshiApp() {
  const [open, setOpen] = useState(true);
  return (
    <div style={{ display: 'flex', height: '100vh', overflow: 'hidden', background: 'var(--d-canvas)' }}>
      <main style={{ flex: 1, overflowY: 'auto', padding: 24, minWidth: 0 }}>
        <div className="dashboard-shell dashboard-shell--admin">
          <h1 className="dashboard-title">Good morning, Josephine</h1>
          <p className="dashboard-subtitle">Toshi sits beside the dashboard on desktop and goes full-screen on a phone.</p>
          <div className="dashboard-kpi-grid">
            <KpiCard icon="users" value="1,284" label="Total Students" color="blue" />
            <KpiCard icon="dollar" value="UGX 18.4M" label="Fees Collected" color="amber" />
            <KpiCard icon="whatsapp" value="1,102" label="WhatsApp Linked" color="green" />
          </div>
          <Card title="Why an assistant" style={{ marginTop: 20 }}>
            <p style={{ margin: 0, fontSize: 14, lineHeight: 1.55, color: 'var(--d-text-secondary)' }}>
              Toshi does the multi-step office work — adding a student, recording a payment, sending a notice — and asks
              for confirmation before anything is written. It runs on its own warm clay palette so it never reads as
              part of the ledger.
            </p>
            <div style={{ marginTop: 14, display: 'flex', gap: 8 }}>
              <Button variant="primary" size="sm" onClick={() => setOpen(true)}>Open Toshi</Button>
              <Button variant="outline" size="sm" onClick={() => setOpen(false)}>Collapse</Button>
            </div>
          </Card>
        </div>
      </main>

      <div data-toshi-root style={{ position: 'static', display: 'flex', flexDirection: 'column', width: open ? 400 : 0, flexShrink: 0, padding: open ? 16 : 0, boxSizing: 'content-box' }}>
        {open ? <ToshiPanel onClose={() => setOpen(false)} /> : (
          <div className="toshi-pill" onClick={() => setOpen(true)} style={{ position: 'fixed', bottom: 24, right: 24 }}>
            <span className="toshi-pill-avatar"><img src="../../assets/brand/klassapp-icon.svg" height="18" alt="" /></span>
            <span className="toshi-pill-text">Ask Toshi to do it for you</span>
            <span className="toshi-pill-badge">Open</span>
          </div>
        )}
      </div>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<ToshiApp />);
