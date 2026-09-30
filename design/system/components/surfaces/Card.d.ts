import * as React from 'react';

/**
 * Card — the KlassApp surface container: white, 14px radius, hairline ring.
 */
export interface CardProps {
  /** Emits `.ds-card-padding-{padding}` — 20px / 14px / 28px / 0. */
  padding?: "sm" | "lg" | "default" | "none";
  /** Emits `.ds-card-shadow-{shadow}`; `none` emits no shadow class at all. */
  shadow?: "sm" | "md" | "lg" | "none";
  /** Adds `.ds-card-hover` — 1px lift plus a stronger ring on hover. */
  hover?: boolean;
  /** Optional heading rendered as `<h3 class="ds-card-title">` above the content. */
  title?: string;
  /** Extra classes appended after the `ds-card-*` set. Blade equivalent: `class`. */
  className?: string;
  style?: React.CSSProperties;
  children?: React.ReactNode;
}

export declare const Card: React.ComponentType<CardProps>;
