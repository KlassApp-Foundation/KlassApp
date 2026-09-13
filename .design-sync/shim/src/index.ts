/**
 * KlassApp design system — React surface.
 *
 * Every export here is a thin port of an anonymous Blade component in
 * `resources/views/components/`. The port emits the same `ds-*` class
 * contract; all styling comes from `public/css/dashboard-refresh.css`,
 * which ships alongside as `_ds_bundle.css`.
 *
 * Generated designs map back to Blade 1:1 — see each component's JSDoc for
 * its `<x-…>` equivalent.
 */

export { Button } from './Button';
export type { ButtonProps, ButtonVariant, ButtonSize } from './Button';

export { Card } from './Card';
export type { CardProps, CardPadding, CardShadow } from './Card';

export { Badge } from './Badge';
export type { BadgeProps, BadgeVariant, BadgeSize } from './Badge';

export { Table } from './Table';
export type { TableProps, TableDensity } from './Table';

export { FormGroup } from './FormGroup';
export type { FormGroupProps, FormGroupType } from './FormGroup';

export { KpiCard } from './KpiCard';
export type { KpiCardProps, KpiCardColor, KpiCardIcon } from './KpiCard';

export { WhatsAppMark } from './WhatsAppMark';
export type { WhatsAppMarkProps } from './WhatsAppMark';

export { SlackMark } from './SlackMark';
export type { SlackMarkProps } from './SlackMark';

export { GoogleDriveMark } from './GoogleDriveMark';
export type { GoogleDriveMarkProps } from './GoogleDriveMark';
