import * as React from 'react';
import { cx } from './cx';

export type FormGroupType = 'text' | 'email' | 'select' | 'textarea' | 'number' | 'date';

export interface FormGroupProps {
    /** Label text. Omit for an unlabelled control. */
    label?: string;
    /** `name` attribute; also the `id` the label points at. */
    name?: string;
    /** Control to render. `select` and `textarea` swap the element. */
    type?: FormGroupType;
    /** Initial value (Blade reads `old($name, $value)`; here it is the uncontrolled default). */
    value?: string | number;
    /** Validation message — adds `.ds-form-input-error` and renders `.ds-form-error`. */
    error?: string;
    /** Adds `.ds-form-label-required` (the red asterisk) and the `required` attribute. */
    required?: boolean;
    placeholder?: string;
    /** Hint text rendered below the control as `.ds-form-help`. */
    help?: string;
    /** `select` options as `{ value: label }`. */
    options?: Record<string, string>;
    /** Extra classes on the `.ds-form-group` wrapper. Blade equivalent: `class`. */
    className?: string;
    /** Extra content appended inside the wrapper (the Blade `$slot`). */
    children?: React.ReactNode;
}

/** Mirrors Laravel's `Str::slug()` closely enough for an element id. */
function slug(input: string): string {
    return input
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

/**
 * KlassApp labelled form control.
 *
 * Blade equivalent: `<x-form-group label="…" name="…" type="…" :error="…">`
 * (`resources/views/components/form-group.blade.php`).
 */
export function FormGroup({
    label,
    name = '',
    type = 'text',
    value,
    error,
    required = false,
    placeholder,
    help,
    options = {},
    className = '',
    children,
}: FormGroupProps) {
    const labelClass = cx('ds-form-label', required ? 'ds-form-label-required' : '');
    const inputClass = cx(
        'ds-form-input',
        error ? 'ds-form-input-error' : '',
        type === 'select' ? 'ds-form-select' : '',
        type === 'textarea' ? 'ds-form-textarea' : '',
    );
    const inputId = name || slug(label ?? '');

    return (
        <div className={cx('ds-form-group', className)}>
            {label ? (
                <label htmlFor={inputId} className={labelClass}>
                    {label}
                </label>
            ) : null}

            {type === 'select' ? (
                <select
                    name={name}
                    id={inputId}
                    className={inputClass}
                    required={required}
                    defaultValue={value}
                >
                    {placeholder ? <option value="">{placeholder}</option> : null}
                    {Object.entries(options).map(([key, optionLabel]) => (
                        <option key={key} value={key}>
                            {optionLabel}
                        </option>
                    ))}
                </select>
            ) : type === 'textarea' ? (
                <textarea
                    name={name}
                    id={inputId}
                    className={inputClass}
                    placeholder={placeholder}
                    required={required}
                    defaultValue={value}
                />
            ) : (
                <input
                    type={type}
                    name={name}
                    id={inputId}
                    className={inputClass}
                    placeholder={placeholder}
                    required={required}
                    defaultValue={value}
                />
            )}

            {error ? <p className="ds-form-error">{error}</p> : null}
            {help ? <p className="ds-form-help">{help}</p> : null}

            {children}
        </div>
    );
}
