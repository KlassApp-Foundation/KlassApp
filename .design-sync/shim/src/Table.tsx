import * as React from 'react';
import { cx } from './cx';

export type TableDensity = 'comfortable' | 'compact';

export interface TableProps {
    /** Column header labels. When empty, no `<thead>` is rendered. */
    headers?: string[];
    /**
     * Declared in the Blade `@props` but **never referenced** in the template —
     * it emits no class and has no effect. Kept for API parity with
     * `<x-table>`; do not rely on it for striping.
     */
    striped?: boolean;
    /**
     * Declared in the Blade `@props` but **never referenced** in the template —
     * it emits no class and has no effect. Row hover comes from
     * `.ds-table-ledger` itself.
     */
    hover?: boolean;
    /** Emits `.dt-compact` or `.dt-comfortable`. */
    density?: TableDensity;
    /** Prepends a checkbox header cell (`.dt-cell-check` + `.dt-checkbox`). */
    selectable?: boolean;
    /** Appends a `.dt-sort-arrow` glyph to every header cell. */
    sortable?: boolean;
    /** Stacked-card layout at ≤767px via `.ds-table-card-mobile`. */
    cardMobile?: boolean;
    /** Extra classes appended to the `<table>`. Blade equivalent: `class`. */
    className?: string;
    /** `<tr>` rows — rendered into `<tbody>`. */
    children?: React.ReactNode;
}

/**
 * KlassApp ledger table.
 *
 * Blade equivalent: `<x-table :headers="[…]" density="…">`
 * (`resources/views/components/table.blade.php`).
 *
 * Emits `.ds-table-ledger`, **not** the `.ds-table` documented in
 * `DESIGN_SYSTEM.md` — the doc is out of date relative to the component.
 */
export function Table({
    headers = [],
    density = 'comfortable',
    selectable = false,
    sortable = false,
    cardMobile = true,
    className = '',
    children,
}: TableProps) {
    const classes = cx(
        'ds-table-ledger',
        density === 'compact' ? 'dt-compact' : 'dt-comfortable',
        cardMobile ? 'ds-table-card-mobile' : '',
        className,
    );

    return (
        <div className="ds-table-wrap">
            <table className={classes}>
                {headers.length > 0 ? (
                    <thead>
                        <tr>
                            {selectable ? (
                                <th className="dt-cell-check" style={{ cursor: 'default' }}>
                                    <input type="checkbox" className="dt-checkbox" id="select-all" />
                                </th>
                            ) : null}
                            {headers.map((header) => (
                                <th key={header}>
                                    {header}
                                    {sortable ? <span className="dt-sort-arrow">{'▴'}</span> : null}
                                </th>
                            ))}
                        </tr>
                    </thead>
                ) : null}
                <tbody>{children}</tbody>
            </table>
        </div>
    );
}
