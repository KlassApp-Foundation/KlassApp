import React from 'react';

function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}

function slug(input) {
  return String(input).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

export function FormGroup({ label, name = '', type = 'text', value, error, required = false, placeholder, help, options = {}, className = '', children, onChange }) {
  const labelClass = cx('ds-form-label', required ? 'ds-form-label-required' : '');
  const inputClass = cx(
    'ds-form-input',
    error ? 'ds-form-input-error' : '',
    type === 'select' ? 'ds-form-select' : '',
    type === 'textarea' ? 'ds-form-textarea' : ''
  );
  const inputId = name || slug(label ?? '');
  return (
    <div className={cx('ds-form-group', className)}>
      {label ? <label htmlFor={inputId} className={labelClass}>{label}</label> : null}
      {type === 'select' ? (
        <select name={name} id={inputId} className={inputClass} required={required} defaultValue={value} onChange={onChange}>
          {placeholder ? <option value="">{placeholder}</option> : null}
          {Object.entries(options).map(([key, optionLabel]) => <option key={key} value={key}>{optionLabel}</option>)}
        </select>
      ) : type === 'textarea' ? (
        <textarea name={name} id={inputId} className={inputClass} placeholder={placeholder} required={required} defaultValue={value} onChange={onChange} />
      ) : (
        <input type={type} name={name} id={inputId} className={inputClass} placeholder={placeholder} required={required} defaultValue={value} onChange={onChange} />
      )}
      {error ? <p className="ds-form-error">{error}</p> : null}
      {help ? <p className="ds-form-help">{help}</p> : null}
      {children}
    </div>
  );
}
