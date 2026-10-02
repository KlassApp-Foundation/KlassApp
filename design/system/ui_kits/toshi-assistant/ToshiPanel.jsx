const { useState } = React;
const { Icon } = window.KlassAppDesignSystem_df5836;

const SUGGESTIONS = [
  { icon: '👥', label: 'Add a new student' },
  { icon: '💰', label: 'Record a fee payment' },
  { icon: '📣', label: 'Send a notice to Senior 2' },
  { icon: '📊', label: 'How are fees this week?' },
];

function Bubble({ from, children }) {
  const mine = from === 'user';
  return (
    <div style={{ display: 'flex', justifyContent: mine ? 'flex-end' : 'flex-start' }}>
      <div style={{
        maxWidth: '82%', padding: '10px 14px', borderRadius: 14,
        background: mine ? 'var(--toshi-user-bubble)' : 'var(--toshi-bot-bubble)',
        border: mine ? 'none' : '1px solid var(--toshi-border)',
        fontSize: 13.5, lineHeight: 1.5, color: 'var(--toshi-title-text)',
      }}>{children}</div>
    </div>
  );
}

function ConfirmCard({ params, state, onYes, onNo }) {
  return (
    <div className={'toshi-confirm-card' + (state === 'cancelled' ? ' is-cancelled' : '')}>
      <div className="toshi-confirm-card-header">
        <div className="toshi-confirm-card-title">
          <span className="toshi-confirm-card-icon"><Icon name="tick" size={15} /></span>
          Record this payment?
        </div>
        {state === 'cancelled' ? <span className="toshi-confirm-cancelled-badge">Cancelled</span> : null}
      </div>
      <div className="toshi-confirm-card-body">
        <div className="toshi-confirm-param-list">
          {params.map(([k, v]) => (
            <div className="toshi-confirm-param-row" key={k}>
              <span className="toshi-confirm-param-label">{k}</span>
              <span className="toshi-confirm-param-value">{v}</span>
            </div>
          ))}
        </div>
      </div>
      <div className="toshi-confirm-card-footer">
        {state === 'open' ? (
          <React.Fragment>
            <button className="toshi-confirm-btn toshi-confirm-btn-yes" onClick={onYes}>Yes, record it</button>
            <button className="toshi-confirm-btn toshi-confirm-btn-no" onClick={onNo}>No, change something</button>
          </React.Fragment>
        ) : (
          <span className="toshi-confirm-cancelled-msg">
            {state === 'done' ? 'Recorded · parent notified on WhatsApp' : 'Nothing was saved.'}
          </span>
        )}
      </div>
    </div>
  );
}

function PlanCard({ step }) {
  const steps = ['Find the student', 'Record UGX 450,000', 'Send WhatsApp receipt'];
  return (
    <div className="toshi-plan-card">
      <div className="toshi-plan-card-header">
        <span className="toshi-plan-card-icon">📋</span>
        <span className="toshi-plan-card-title">Plan</span>
        <span className="toshi-plan-card-count">{step}/3</span>
      </div>
      <div className="toshi-plan-card-steps">
        {steps.map((s, i) => (
          <div key={s} className={'toshi-plan-step ' + (i < step ? 'step-completed' : i === step ? 'step-active' : 'step-pending')}>
            <span className="toshi-plan-step-icon">{i < step ? '✓' : i === step ? '▸' : '·'}</span>
            <span className="toshi-plan-step-num">{i + 1}</span>
            <span className="toshi-plan-step-label">{s}</span>
          </div>
        ))}
      </div>
    </div>
  );
}

function ToshiPanel({ onClose }) {
  const [messages, setMessages] = useState([
    { from: 'bot', text: 'Morning Josephine. Fees are at UGX 18.4M this term — 86% of the target. What do you need?' },
  ]);
  const [confirm, setConfirm] = useState(null);
  const [draft, setDraft] = useState('');
  const [used, setUsed] = useState([]);

  function ask(text) {
    setMessages((m) => [...m, { from: 'user', text }]);
    setDraft('');
    setTimeout(() => {
      if (/pay|fee|money/i.test(text)) {
        setMessages((m) => [...m, { from: 'bot', text: 'Got it — here is what I will do.' }, { from: 'plan', step: 1 }]);
        setConfirm('open');
      } else {
        setMessages((m) => [...m, { from: 'bot', text: 'I can do that. Which class should it go to — Senior 2, or the whole school?' }]);
      }
    }, 420);
  }

  return (
    <div className="toshi-panel">
      <div className="toshi-header">
        <div className="toshi-header-logo" style={{ display: 'flex', alignItems: 'center', gap: 9 }}>
          <img src="../../assets/brand/klassapp-icon.svg" height="22" alt="" />
          <span style={{ fontFamily: 'var(--d-font-display)', fontSize: 15, fontWeight: 700, color: 'var(--toshi-title-text)' }}>Toshi</span>
          <span style={{ fontSize: 11, color: 'var(--d-muted)' }}>KlassApp assistant</span>
        </div>
        <div className="toshi-header-actions">
          <button className="toshi-header-btn" title="Expand"><Icon name="plus" size={16} /></button>
          <button className="toshi-header-btn" onClick={onClose} title="Close"><Icon name="close" size={16} /></button>
        </div>
      </div>

      <div className="toshi-messages-area">
        {messages.map((m, i) => m.from === 'plan'
          ? <PlanCard key={i} step={m.step} />
          : <Bubble key={i} from={m.from}>{m.text}</Bubble>)}
        {confirm ? (
          <ConfirmCard
            state={confirm}
            params={[['Student', 'Nakato Sarah · Senior 2'], ['Amount', 'UGX 450,000'], ['Method', 'SchoolPay'], ['Receipt', 'WhatsApp to +256 772 114 220']]}
            onYes={() => { setConfirm('done'); setMessages((m) => [...m, { from: 'bot', text: 'Done. Receipt sent to Okello Anne on WhatsApp.' }]); }}
            onNo={() => setConfirm('cancelled')}
          />
        ) : null}
      </div>

      <div className="toshi-suggestions-wrapper">
        <div className="toshi-suggestions-scroll">
          {SUGGESTIONS.map((s) => (
            <button key={s.label} className={'toshi-chip-suggestion' + (used.includes(s.label) ? ' toshi-chip-used' : '')}
              onClick={() => { setUsed((u) => [...u, s.label]); ask(s.label); }}>
              <span className="toshi-chip-icon">{s.icon}</span>{s.label}
            </button>
          ))}
        </div>
      </div>

      <div className="toshi-composer">
        <div className="toshi-composer-inner">
          <span className="toshi-attach-btn"><Icon name="upload" size={17} /></span>
          <textarea className="toshi-composer-input" rows={1} placeholder="Ask Toshi to do something…" value={draft}
            onChange={(e) => setDraft(e.target.value)}
            onKeyDown={(e) => { if (e.key === 'Enter' && !e.shiftKey && draft.trim()) { e.preventDefault(); ask(draft.trim()); } }} />
          <button className="toshi-btn-done" onClick={() => draft.trim() && ask(draft.trim())}>Send</button>
        </div>
      </div>
    </div>
  );
}

Object.assign(window, { ToshiPanel });
