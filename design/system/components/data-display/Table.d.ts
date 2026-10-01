import * as React from 'react';

/**
 * Table — the KlassApp ledger table: sticky blue-ruled header, green hover rail.
 * @replaces table
 */
export interface TableProps {
  /** Column header labels. When empty, no `<thead>` is rendered. */
  headers?: string[];
  /** Declared upstream but **never referenced** — emits no class and has no effect. Kept for API parity. */
  striped?: boolean;
  /** Declared upstream but **never referenced** — emits no class. Row hover comes from `.ds-table-ledger` itself. */
  hover?: boolean;
  /** Emits `.dt-compact` (12px/16px cells) or `.dt-comfortable` (18px/20px). */
  density?: "comfortable" | "compact";
  /** Prepends a checkbox header cell (`.dt-cell-check` + `.dt-checkbox`). */
  selectable?: boolean;
  /** Appends a `.dt-sort-arrow` glyph to every header cell. */
  sortable?: boolean;
  /** Stacked-card layout at ≤767px via `.ds-table-card-mobile`. Default true. */
  cardMobile?: boolean;
  /** Extra classes appended to the `<table>`. */
  className?: string;
  /** `<tr>` rows — rendered into `<tbody>`. */
  children?: React.ReactNode;
}

export declare const Table: React.ComponentType<TableProps>;
