const { useState } = React;
const { Card, Table, Badge, Button, FormGroup, Icon } = window.KlassAppDesignSystem_df5836;

const STUDENTS = [
  ['1', 'Nakato Sarah', 'Senior 2', 'Okello Anne', '+256 772 114 220', 'active'],
  ['2', 'Mukasa David', 'Senior 2', 'Mukasa John', '+256 701 883 004', 'active'],
  ['3', 'Namuli Grace', 'Senior 4', 'Namuli Betty', '+256 758 220 991', 'active'],
  ['4', 'Ssempala Isaac', 'Senior 4', 'Ssempala Paul', '—', 'inactive'],
  ['5', 'Achieng Mercy', 'Primary 5', 'Achieng Rose', '+256 772 660 118', 'active'],
];

function StudentsScreen() {
  const [query, setQuery] = useState('');
  const [cls, setCls] = useState('all');
  const rows = STUDENTS.filter((r) =>
    r[1].toLowerCase().includes(query.toLowerCase()) && (cls === 'all' || r[2] === cls));
  return (
    <div className="dashboard-shell dashboard-shell--reception">
      <PageHead
        title="Students"
        sub={rows.length + ' of ' + STUDENTS.length + ' shown · Term 2 2026'}
        action={<div style={{ display: 'flex', gap: 8 }}>
          <Button variant="outline" size="sm">Import list</Button>
          <Button variant="primary" size="sm">Add student</Button>
        </div>}
      />
      <Card padding="sm" style={{ marginBottom: 16 }}>
        <div style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
          <div style={{ position: 'relative', flex: 1, minWidth: 220 }}>
            <span style={{ position: 'absolute', left: 12, top: 11, color: 'var(--d-muted)' }}><Icon name="search" size={18} /></span>
            <input className="ds-form-input" style={{ paddingLeft: 38 }} placeholder="Search by student name" value={query} onChange={(e) => setQuery(e.target.value)} />
          </div>
          <select className="ds-form-input ds-form-select" style={{ width: 180 }} value={cls} onChange={(e) => setCls(e.target.value)}>
            <option value="all">All classes</option>
            <option value="Primary 5">Primary 5</option>
            <option value="Senior 2">Senior 2</option>
            <option value="Senior 4">Senior 4</option>
          </select>
        </div>
      </Card>
      <Card padding="none">
        {rows.length ? (
          <Table headers={['#', 'Student name', 'Class', 'Parent', 'WhatsApp', 'Status']} selectable sortable>
            {rows.map((r) => (
              <tr key={r[0]}>
                <td data-label="#"><input type="checkbox" className="dt-checkbox" /></td>
                <td data-label="#">{r[0]}</td>
                <td data-label="Student name"><a className="dt-name-link" href="#">{r[1]}</a></td>
                <td data-label="Class">{r[2]}</td>
                <td data-label="Parent">{r[3]}</td>
                <td data-label="WhatsApp">{r[4]}</td>
                <td className="dt-cell-badge" data-label="Status"><Badge variant={r[5]}>{r[5] === 'active' ? 'Active' : 'Left school'}</Badge></td>
              </tr>
            ))}
          </Table>
        ) : (
          <div className="ds-empty-state">
            <p className="ds-empty-state-title">No students match “{query}”</p>
            <p className="ds-empty-state-desc">Check the spelling, or clear the class filter to search the whole school.</p>
          </div>
        )}
        <div className="dt-pagination" style={{ padding: '12px 16px' }}>
          <span className="dt-pagination-info">Showing 1–{rows.length} of 1,284 students</span>
          <span className="dt-pagination-pages">
            <button className="dt-page-btn active">1</button>
            <button className="dt-page-btn">2</button>
            <button className="dt-page-btn">3</button>
            <a className="dt-page-nav" href="#">Next →</a>
          </span>
        </div>
      </Card>
    </div>
  );
}

Object.assign(window, { StudentsScreen });
