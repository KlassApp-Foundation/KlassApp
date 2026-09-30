import * as React from 'react';

/**
 * Badge — the KlassApp status pill; the only way statuses are shown in tables and lists.
 */
export interface BadgeProps {
  /** Status colour. Unknown values fall back to `info` (the Blade whitelist behaviour). */
  variant?: "pending" | "approved" | "rejected" | "paid" | "unpaid" | "active" | "inactive" | "warning" | "info";
  /** Emits `.ds-badge-{size}`. */
  size?: "sm" | "md";
  /** Extra classes appended after the `ds-badge-*` set. Blade equivalent: `class`. */
  className?: string;
  children?: React.ReactNode;
}

export declare const Badge: React.ComponentType<BadgeProps>;
