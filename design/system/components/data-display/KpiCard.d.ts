import * as React from 'react';

/**
 * KpiCard — the dashboard metric tile that opens every role dashboard.
 */
export interface KpiCardProps {
  /** Icon key: users · classes/door · exam/calendar · whatsapp/message · book/library · bell/notice · dollar/money · check/tasks. Anything else renders the info-circle fallback. */
  icon?: string;
  /** The headline figure. Defaults to an em dash — which is what an un-synced school shows. */
  value?: string | number;
  /** Caption under the value. */
  label?: string;
  /** Tints the icon chip only. Unknown values fall back to `blue`. */
  color?: "blue" | "green" | "amber" | "red" | "purple";
  /** When set, the whole card becomes an `<a href>` and gains the hover lift. */
  link?: string;
}

export declare const KpiCard: React.ComponentType<KpiCardProps>;
