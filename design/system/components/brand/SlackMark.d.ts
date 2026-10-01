import * as React from 'react';

/**
 * SlackMark — the official Slack logo mark (2019 hash). Never recolour it.
 */
export interface SlackMarkProps {
  /** When true (default) the mark is `aria-hidden` with no `<title>`. */
  decorative?: boolean;
  className?: string;
  /** Sizing is by CSS/inline style — the SVG carries no intrinsic width. */
  style?: React.CSSProperties;
}

export declare const SlackMark: React.ComponentType<SlackMarkProps>;
