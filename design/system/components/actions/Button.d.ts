import * as React from 'react';

/**
 * Button — the KlassApp action button. Blade equivalent: `<x-button>`.
 * @replaces button
 */
export interface ButtonProps {
  /** Visual style. Emits `.ds-btn-{variant}`. Unknown values fall back to `primary`. **`success` is retired** — it renders as `primary` (white on #22C55E was 2.28:1; the only AA green is primary itself). Use `--d-success` for badges/alerts, not buttons. */
  variant?: "warning" | "primary" | "success" | "danger" | "outline" | "ghost";
  /** Size. Emits `.ds-btn-{size}`. `.ds-btn-md` restates the base (8px 18px / 0.85rem). All three sizes render **44px tall** — the touch-target rule sets `min-height: 44px` on every `.ds-btn`, so `sm` differs from `md` only in padding-x and font size. */
  size?: "sm" | "md" | "lg";
  /** When set, renders an `<a href>` instead of a `<button>`. */
  href?: string;
  /** Only applies when `href` is unset. */
  type?: "button" | "submit";
  /** On an `<a>`, renders `aria-disabled` + `tabindex="-1"` rather than `disabled`. */
  disabled?: boolean;
  /** PROPOSED. Prepends a 14px spinner, sets `aria-busy`, blocks pointer events. The label stays, so width never changes. */
  loading?: boolean;
  /** PROPOSED. Opts into `controls-v2.css` fixes: AA-passing success/warning fills and single-dim disabled. */
  v2?: boolean;
  /** Extra classes appended after the `ds-btn-*` set. Blade equivalent: `class`. */
  className?: string;
  onClick?: (e: React.MouseEvent) => void;
  children?: React.ReactNode;
}

export declare const Button: React.ComponentType<ButtonProps>;
