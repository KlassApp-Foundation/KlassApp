import * as React from 'react';

/**
 * Icon — Heroicons v1 outline glyph, stroked in `currentColor`.
 * Intentional addition: the source ships icon paths inline inside KpiCard only;
 * this wrapper exposes the same family for app chrome (sidebar, toolbars).
 */
export interface IconProps {
  /** Glyph key. KpiCard keys (users, classes/door, exam/calendar, whatsapp/message, book/library, bell/notice, dollar/money, check/tasks) plus chrome glyphs: home, document, upload, logout, search, plus, menu, close, chevronDown, chevronLeft, chevronRight, tick. */
  name?: string;
  /** Rendered square size in px. 20 inline, 24 in nav, 18 in dense toolbars. */
  size?: number;
  /** Stroke width; 2 everywhere in the app. */
  strokeWidth?: number;
  className?: string;
  style?: React.CSSProperties;
  /** Pass only when the icon is the sole label — adds a `<title>` and unsets `aria-hidden`. */
  title?: string;
}

export declare const Icon: React.ComponentType<IconProps>;
