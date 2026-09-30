const { useState } = React;
const { Card, Table, Badge, Button, FormGroup, KpiCard } = window.KlassAppDesignSystem_df5836;

const INITIAL = [
  ['12 Sep', 'Nakato Sarah', 'Senior 2', 'UGX 450,000', 'SchoolPay', 'paid'],
  ['11 Sep', 'Mukasa David', 'Senior 2', 'UGX 300,000', 'Mobile Money', 'warning'],
  ['09 Sep', 'Namuli Grace', 'Senior 4', 'UGX 120,000', 'Cash', 'paid'],
  ['08 Sep', 'Ssempala Isaac', 'Senior 4', 'UGX 0', '—', 'unpaid'],
];

function FeesScreen() {
  const [rows, setRows] = useState(INITIAL);
  const [open, setOpen] = useState(false);
  const [saved, setSaved] = useState(false);
  const [form, setForm] = useState({ student: '', amount: '', method: 'schoolpay' });
  const [error, setError] = useState('');

  function record() {
    if (!form.student.trim() || !form.amount.trim()) {
      setError('Enter both a student and an amount.');
      return;
    }
    setError('');
    const method = { schoolpay: 'SchoolPay', momo: 'Mobile Money', cash: 'Cash' }[form.method];
    setRows([['Today', form.student, 'Senior 2', 'UGX ' + Number(form.amount).toLocaleString(), method, 'paid'], ...rows]);
    setForm({ student: '', amount: '', method: 'schoolpay' });
    setOpen(false);
    setSaved(true);
    setTimeout(() => setSaved(false), 2600);
  }

  return (
    <div className="dashboard-shell dashboard-shell--accountant">
      <PageHead
        title="Fee payments"
        sub="Term 2 2026 · all classes"
        action={<div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
          {saved ? <span className="ds-save-indicator ds-save-indicator--saved"><span className="ds-save-indicator__dot"></span>Payment recorded</span> : null}
          <Button variant="outline" size="sm">Export statement</Button>
          <Button variant="success" size="sm" onClick={() => setOpen(!open)}>Record payment</Button>
        </div>}
      />

      <div className="dashboard-kpi-grid" style={{ marginTop: 0, marginBottom: 20 }}>
        <KpiCard icon="dollar" value="UGX 18.4M" label="Collected this term" color="green" />
        <KpiCard icon="money" value="UGX 6.1M" label="Outstanding" color="amber" />
        <KpiCard icon="users" value="182" label="Students in arrears" color="red" />
        <KpiCard icon="check" value="86%" label="Collection rate" color="blue" />
      </div>

      {open ? (
        <Card title="Record a payment" style={{ marginBottom: 16 }}>
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '0 16px' }}>
            <FormGroup label="Student" name="student" required placeholder="e.g. Nakato Sarah" value={form.student} onChange={(e) => setForm({ ...form, student: e.target.value })} />
            <FormGroup label="Amount (UGX)" name="amount" type="number" required placeholder="450000" value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} help="Figures only, no separators." />
            <FormGroup label="Method" name="method" type="select" options={{ schoolpay: 'SchoolPay', momo: 'Mobile Money', cash: 'Cash' }} value={form.method} onChange={(e) => setForm({ ...form, method: e.target.value })} />
          </div>
          {error ? <p className="ds-form-error" style={{ marginTop: 0 }}>{error}</p> : null}
          <div style={{ display: 'flex', gap: 8, marginTop: 4 }}>
            <Button variant="success" onClick={record}>Save payment</Button>
            <Button variant="outline" onClick={() => { setOpen(false); setError(''); }}>Cancel</Button>
          </div>
          <p className="ds-form-help" style={{ marginTop: 10 }}>The parent gets a WhatsApp receipt as soon as you save.</p>
        </Card>
      ) : null}

      <Card padding="none">
        <Table headers={['Date', 'Student', 'Class', 'Amount', 'Method', 'Status']} sortable>
          {rows.map((r, i) => (
            <tr key={i}>
              <td data-label="Date">{r[0]}</td>
              <td data-label="Student"><a className="dt-name-link" href="#">{r[1]}</a></td>
              <td data-label="Class">{r[2]}</td>
              <td className="dt-cell-num" data-label="Amount">{r[3]}</td>
              <td data-label="Method">{r[4]}</td>
              <td className="dt-cell-badge" data-label="Status">
                <Badge variant={r[5]}>{r[5] === 'paid' ? 'Paid' : r[5] === 'warning' ? 'Part paid' : 'Unpaid'}</Badge>
              </td>
            </tr>
          ))}
        </Table>
      </Card>
    </div>
  );
}

Object.assign(window, { FeesScreen });
