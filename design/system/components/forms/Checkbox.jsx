import React from 'react';

export function Checkbox({ label, name, value, type = 'checkbox', checked, defaultChecked, disabled = false, className = '', onChange }) {
  const cls = ['ds-check', disabled && 'is-disabled', className].filter(Boolean).join(' ');
  return (
    <label className={cls}>
      <input type={type} name={name} value={value} checked={checked} defaultChecked={defaultChecked} disabled={disabled} onChange={onChange} />
      <span>{label}</span>
    </label>
  );
}
