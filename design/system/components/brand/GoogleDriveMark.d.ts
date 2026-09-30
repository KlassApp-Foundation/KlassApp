import * as React from 'react';

/**
 * GoogleDriveMark — the official Google Drive logo mark. Never recolour the triangle.
 */
export interface GoogleDriveMarkProps {
  /** When true (default) the mark is `aria-hidden` with no `<title>`. */
  decorative?: boolean;
  className?: string;
  /** Sizing is by CSS/inline style — the SVG carries no intrinsic width. */
  style?: React.CSSProperties;
}

export declare const GoogleDriveMark: React.ComponentType<GoogleDriveMarkProps>;
