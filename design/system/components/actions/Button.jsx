import React from 'react';

const VARIANTS = {
  primary: 'ds-btn-primary',
  success: 'ds-btn-primary', // RETIRED: white on #22C55E fails AA; the only passing green is primary
  danger: 'ds-btn-danger',
  warning: 'ds-btn-warning',
  outline: 'ds-btn-outline',
  ghost: 'ds-btn-ghost',
};

const SIZES = { sm: 'ds-btn-sm', md: 'ds-btn-md', lg: 'ds-btn-lg' };

function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}

export function Button({ variant = 'primary', size = 'md', href, type = 'button', disabled = false, loading = false, v2 = false, className = '', children, onClick }) {
  const classes = cx('ds-btn', VARIANTS[variant] ?? VARIANTS.primary, SIZES[size] ?? SIZES.md, v2 && 'v2', loading && 'is-loading', className);
  const content = loading ? [<span key="s" className="ds-btn-spinner" aria-hidden="true"></span>, <span key="l">{children}</span>] : children;
  if (href) {
    return (
      <a href={href} className={classes} onClick={onClick} aria-busy={loading || undefined} {...(disabled ? { 'aria-disabled': true, tabIndex: -1 } : {})}>{content}</a>
    );
  }
  return <button type={type} className={classes} disabled={disabled} aria-busy={loading || undefined} onClick={onClick}>{content}</button>;
}
