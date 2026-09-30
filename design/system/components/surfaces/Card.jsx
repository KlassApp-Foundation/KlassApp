import React from 'react';

const PADDINGS = {
  default: 'ds-card-padding-default',
  sm: 'ds-card-padding-sm',
  none: 'ds-card-padding-none',
  lg: 'ds-card-padding-lg',
};

const SHADOWS = {
  sm: 'ds-card-shadow-sm',
  md: 'ds-card-shadow-md',
  lg: 'ds-card-shadow-lg',
  none: '',
};

function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}

export function Card({ padding = 'default', shadow = 'sm', hover = false, title, className = '', children, style }) {
  const classes = cx('ds-card', PADDINGS[padding] ?? PADDINGS.default, SHADOWS[shadow] ?? SHADOWS.sm, hover ? 'ds-card-hover' : '', className);
  return (
    <div className={classes} style={style}>
      {title ? <h3 className="ds-card-title">{title}</h3> : null}
      {children}
    </div>
  );
}
