import React from 'react';

const KNOWN_VARIANTS = ['pending', 'approved', 'rejected', 'paid', 'unpaid', 'active', 'inactive', 'warning', 'info'];

function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}

export function Badge({ variant = 'info', size = 'sm', className = '', children }) {
  const safeVariant = KNOWN_VARIANTS.includes(variant) ? variant : 'info';
  return <span className={cx('ds-badge', `ds-badge-${safeVariant}`, `ds-badge-${size}`, className)}>{children}</span>;
}
