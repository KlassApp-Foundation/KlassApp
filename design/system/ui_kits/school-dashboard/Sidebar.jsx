const { useState } = React;
const { Icon } = window.KlassAppDesignSystem_df5836;

const NAV = [
  { group: null, items: [{ id: 'home', label: 'Dashboard', icon: 'home' }] },
  { group: 'People', items: [
    { id: 'students', label: 'Students', icon: 'users' },
    { id: 'teachers', label: 'Teachers', icon: 'users' },
  ] },
  { group: 'Academics', items: [
    { id: 'classes', label: 'Classes', icon: 'classes' },
    { id: 'exams', label: 'Exams & marks', icon: 'exam' },
  ] },
  { group: 'Finance', items: [
    { id: 'fees', label: 'Fee payments', icon: 'dollar' },
    { id: 'reports', label: 'Statements', icon: 'document' },
  ] },
  { group: 'Communication', items: [
    { id: 'notices', label: 'Notices', icon: 'bell' },
    { id: 'whatsapp', label: 'WhatsApp log', icon: 'whatsapp' },
  ] },
];

const itemStyle = (active) => ({
  display: 'flex', alignItems: 'center', gap: 12, padding: '10px 16px', minHeight: 44,
  fontSize: 14, fontWeight: active ? 600 : 500, cursor: 'pointer',
  color: active ? '#166534' : 'var(--d-text)',
  background: active ? 'rgba(34, 197, 94, 0.24)' : 'transparent',
  borderRadius: 8, margin: '0 8px',
});

function SidebarGroup({ label, items, active, onSelect }) {
  const [open, setOpen] = useState(true);
  return (
    <div className="sidebar-group">
      {label ? (
        <div className={'sidebar-group-header' + (open ? ' sidebar-group-header--open' : '')} onClick={() => setOpen(!open)}>
          <span className="sidebar-group-label">{label}</span>
          <span className={'sidebar-group-chevron' + (open ? ' rotate-180' : '')}><Icon name="chevronDown" size={14} /></span>
        </div>
      ) : null}
      {open ? (
        <ul>
          {items.map((it) => (
            <li key={it.id}>
              <div className={'dashboard-menu-item' + (active === it.id ? ' active' : '')} style={itemStyle(active === it.id)} onClick={() => onSelect(it.id)}>
                <Icon name={it.icon} size={19} />
                <span>{it.label}</span>
              </div>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}

function Sidebar({ active, onSelect }) {
  return (
    <aside style={{ width: 252, flexShrink: 0, background: '#FFFFFC', borderRight: '1px solid var(--d-border)', display: 'flex', flexDirection: 'column', height: '100%' }}>
      <div style={{ padding: '20px 16px 14px', borderBottom: '1px solid var(--d-border)' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
          <img src="../../assets/brand/klassapp-icon.svg" height="28" alt="" />
          <span style={{ fontFamily: 'var(--d-font-display)', fontWeight: 700, fontSize: 19, letterSpacing: '-0.02em', color: 'var(--d-dark)' }}>KlassApp</span>
        </div>
        <div style={{ fontSize: 12, color: 'var(--d-muted)', marginTop: 6 }}>St. Mary&rsquo;s SS, Kampala</div>
      </div>
      <nav style={{ paddingTop: 8, overflowY: 'auto', flex: 1 }}>
        {NAV.map((g, i) => <SidebarGroup key={i} label={g.group} items={g.items} active={active} onSelect={onSelect} />)}
      </nav>
      <div style={{ borderTop: '1px solid var(--d-border)', padding: 14, display: 'flex', alignItems: 'center', gap: 10 }}>
        <div style={{ width: 34, height: 34, borderRadius: 999, background: 'rgba(30,111,217,0.10)', color: 'var(--d-blue)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700, fontSize: 13 }}>JN</div>
        <div style={{ lineHeight: 1.3, flex: 1, minWidth: 0 }}>
          <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--d-text)' }}>Josephine N.</div>
          <div style={{ fontSize: 11, color: 'var(--d-muted)' }}>Head Teacher</div>
        </div>
        <span style={{ color: 'var(--d-muted)' }}><Icon name="logout" size={17} /></span>
      </div>
    </aside>
  );
}

Object.assign(window, { Sidebar });
