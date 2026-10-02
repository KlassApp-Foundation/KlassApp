const { useState } = React;
const { Icon } = window.KlassAppDesignSystem_df5836;

function App() {
  const [screen, setScreen] = useState('home');
  let view;
  if (screen === 'home') view = <DashboardHome onNav={setScreen} />;
  else if (screen === 'students') view = <StudentsScreen />;
  else if (screen === 'fees') view = <FeesScreen />;
  else if (screen === 'exams') view = <ExamsScreen />;
  else view = <PlaceholderScreen title={screen[0].toUpperCase() + screen.slice(1)} />;

  return (
    <div style={{ display: 'flex', height: '100vh', overflow: 'hidden', background: 'var(--d-canvas)' }}>
      <Sidebar active={screen} onSelect={setScreen} />
      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0 }}>
        <header style={{ height: 58, flexShrink: 0, background: 'var(--d-white)', borderBottom: '1px solid var(--d-border)', display: 'flex', alignItems: 'center', gap: 14, padding: '0 20px' }}>
          <div style={{ position: 'relative', width: 320, maxWidth: '40%' }}>
            <span style={{ position: 'absolute', left: 12, top: 10, color: 'var(--d-muted)' }}><Icon name="search" size={17} /></span>
            <input className="ds-form-input" style={{ paddingLeft: 36, height: 38, minHeight: 38 }} placeholder="Search students, classes, payments" />
          </div>
          <div style={{ marginLeft: 'auto', display: 'flex', alignItems: 'center', gap: 14, color: 'var(--d-text-secondary)' }}>
            <span style={{ fontSize: 13 }}>Term 2 2026</span>
            <span style={{ position: 'relative' }}>
              <Icon name="bell" size={20} />
              <span style={{ position: 'absolute', top: -2, right: -2, width: 8, height: 8, borderRadius: 999, background: 'var(--d-red)' }}></span>
            </span>
          </div>
        </header>
        <main className="dashboard-content-area" style={{ flex: 1, overflowY: 'auto', padding: 20 }}>{view}</main>
      </div>
    </div>
  );
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);
