import * as React from 'react';

/**
 * FormGroup — label + control + error/help, the only form primitive in the system.
 */
export interface FormGroupProps {
  /** Label text. Omit for an unlabelled control. */
  label?: string;
  /** `name` attribute; also the `id` the label points at. Falls back to a slug of `label`. */
  name?: string;
  /** Control to render. `select` and `textarea` swap the element. */
  type?: "number" | "text" | "email" | "select" | "textarea" | "date";
  /** Initial value (uncontrolled default; Blade reads `old($name, $value)`). */
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
  onChange?: (e: React.ChangeEvent<any>) => void;
  /** Extra content appended inside the wrapper (the Blade `$slot`). */
  children?: React.ReactNode;
}

export declare const FormGroup: React.ComponentType<FormGroupProps>;
