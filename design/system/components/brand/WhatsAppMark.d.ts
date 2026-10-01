import * as React from 'react';

/**
 * WhatsAppMark — the official WhatsApp logo mark. Never recolour the #25D366 green.
 */
export interface WhatsAppMarkProps {
  /** When true (default) the mark is `aria-hidden` with no `<title>`. */
  decorative?: boolean;
  className?: string;
  /** Sizing is by CSS/inline style — the SVG carries no intrinsic width. */
  style?: React.CSSProperties;
}

export declare const WhatsAppMark: React.ComponentType<WhatsAppMarkProps>;
