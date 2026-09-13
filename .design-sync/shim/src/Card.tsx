import * as React from 'react';
import { cx } from './cx';

export type CardPadding = 'default' | 'sm' | 'none' | 'lg';
export type CardShadow = 'sm' | 'md' | 'lg' | 'none';

export interface CardProps {
    /** Emits `.ds-card-padding-{padding}`. */
    padding?: CardPadding;
    /** Emits `.ds-card-shadow-{shadow}`; `none` emits no shadow class at all. */
    shadow?: CardShadow;
    /** Adds `.ds-card-hover` (lift-on-hover). */
    hover?: boolean;
    /** Optional heading rendered as `<h3 class="ds-card-title">` above the content. */
    title?: string;
    /** Extra classes appended after the `ds-card-*` set. Blade equivalent: `class`. */
    className?: string;
    children?: React.ReactNode;
}

const PADDINGS: Record<CardPadding, string> = {
    default: 'ds-card-padding-default',
    sm: 'ds-card-padding-sm',
    none: 'ds-card-padding-none',
    lg: 'ds-card-padding-lg',
};

const SHADOWS: Record<CardShadow, string> = {
    sm: 'ds-card-shadow-sm',
    md: 'ds-card-shadow-md',
    lg: 'ds-card-shadow-lg',
    none: '',
};

/**
 * KlassApp surface container.
 *
 * Blade equivalent: `<x-card title="…" padding="…" shadow="…" :hover="true">`
 * (`resources/views/components/card.blade.php`).
 */
export function Card({
    padding = 'default',
    shadow = 'sm',
    hover = false,
    title,
    className = '',
    children,
}: CardProps) {
    const classes = cx(
        'ds-card',
        PADDINGS[padding] ?? PADDINGS.default,
        SHADOWS[shadow] ?? SHADOWS.sm,
        hover ? 'ds-card-hover' : '',
        className,
    );

    return (
        <div className={classes}>
            {title ? <h3 className="ds-card-title">{title}</h3> : null}
            {children}
        </div>
    );
}
