import React from 'react';

function cx(...parts) {
  return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}

export function Table({ headers = [], density = 'comfortable', selectable = false, sortable = false, cardMobile = true, className = '', children }) {
  const classes = cx('ds-table-ledger', density === 'compact' ? 'dt-compact' : 'dt-comfortable', cardMobile ? 'ds-table-card-mobile' : '', className);
  return (
    <div className="ds-table-wrap">
      <table className={classes}>
        {headers.length > 0 ? (
          <thead>
            <tr>
              {selectable ? <th className="dt-cell-check" style={{ cursor: 'default' }}><input type="checkbox" className="dt-checkbox" id="select-all" /></th> : null}
              {headers.map((header) => (
                <th key={header}>{header}{sortable ? <span className="dt-sort-arrow">▴</span> : null}</th>
              ))}
            </tr>
          </thead>
        ) : null}
        <tbody>{children}</tbody>
      </table>
    </div>
  );
}
