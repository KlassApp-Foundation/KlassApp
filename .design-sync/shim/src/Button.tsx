import * as React from 'react';
import { cx } from './cx';

export type ButtonVariant =
    | 'primary'
    | 'success'
    | 'danger'
    | 'warning'
    | 'outline'
    | 'ghost';

export type ButtonSize = 'sm' | 'md' | 'lg';

export interface ButtonProps {
    /** Visual style. Emits `.ds-btn-{variant}`. Unknown values fall back to `primary`. */
    variant?: ButtonVariant;
    /**
     * Size. Emits `.ds-btn-{size}`.
     *
     * Note: `md` is the default but `.ds-btn-md` has **no rule** in
     * `dashboard-refresh.css` — default-size buttons render at the `.ds-btn`
     * base size. Faithful to the Blade source; not a porting bug.
     */
    size?: ButtonSize;
    /** When set, renders an `<a href>` instead of a `<button>`. */
    href?: string;
    /** Only applies when `href` is unset. */
    type?: 'button' | 'submit';
    /** On an `<a>`, renders `aria-disabled` + `tabindex="-1"` rather than `disabled`. */
    disabled?: boolean;
    /** Extra classes appended after the `ds-btn-*` set. Blade equivalent: `class`. */
    className?: string;
    children?: React.ReactNode;
}

const VARIANTS: Record<ButtonVariant, string> = {
    primary: 'ds-btn-primary',
    success: 'ds-btn-success',
    danger: 'ds-btn-danger',
    warning: 'ds-btn-warning',
    outline: 'ds-btn-outline',
    ghost: 'ds-btn-ghost',
};

const SIZES: Record<ButtonSize, string> = {
    sm: 'ds-btn-sm',
    md: 'ds-btn-md',
    lg: 'ds-btn-lg',
};

/**
 * KlassApp action button.
 *
 * Blade equivalent: `<x-button variant="…" size="…" href="…">`
 * (`resources/views/components/button.blade.php`).
 */
export function Button({
    variant = 'primary',
    size = 'md',
    href,
    type = 'button',
    disabled = false,
    className = '',
    children,
}: ButtonProps) {
    const classes = cx(
        'ds-btn',
        VARIANTS[variant] ?? VARIANTS.primary,
        SIZES[size] ?? SIZES.md,
        className,
    );

    if (href) {
        return (
            <a
                href={href}
                className={classes}
                {...(disabled ? { 'aria-disabled': true as const, tabIndex: -1 } : {})}
            >
                {children}
            </a>
        );
    }

    return (
        <button type={type} className={classes} disabled={disabled}>
            {children}
        </button>
    );
}
