import * as React from 'react';
import { cx } from './cx';

export type BadgeVariant =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'paid'
    | 'unpaid'
    | 'active'
    | 'inactive'
    | 'warning'
    | 'info';

export type BadgeSize = 'sm' | 'md';

export interface BadgeProps {
    /** Status colour. Unknown values fall back to `info` (the Blade whitelist behaviour). */
    variant?: BadgeVariant;
    /** Emits `.ds-badge-{size}`. */
    size?: BadgeSize;
    /** Extra classes appended after the `ds-badge-*` set. Blade equivalent: `class`. */
    className?: string;
    children?: React.ReactNode;
}

const KNOWN_VARIANTS: BadgeVariant[] = [
    'pending',
    'approved',
    'rejected',
    'paid',
    'unpaid',
    'active',
    'inactive',
    'warning',
    'info',
];

/**
 * KlassApp status pill.
 *
 * Blade equivalent: `<x-badge variant="…" size="…">`
 * (`resources/views/components/badge.blade.php`).
 */
export function Badge({
    variant = 'info',
    size = 'sm',
    className = '',
    children,
}: BadgeProps) {
    const safeVariant = KNOWN_VARIANTS.includes(variant) ? variant : 'info';
    const classes = cx('ds-badge', `ds-badge-${safeVariant}`, `ds-badge-${size}`, className);

    return <span className={classes}>{children}</span>;
}
