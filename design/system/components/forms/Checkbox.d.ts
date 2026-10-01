import * as React from 'react';

/**
 * Checkbox — PROPOSED checkbox/radio row. Extends `.dt-checkbox` (18px, accent-color --d-blue);
 * the whole label row is the 44px hit target. Emits `.ds-check`.
 */
export interface CheckboxProps {
  label: React.ReactNode;
  name?: string;
  value?: string;
  /** `radio` renders a radio with identical metrics. */
  type?: "checkbox" | "radio";
  checked?: boolean;
  defaultChecked?: boolean;
  disabled?: boolean;
  className?: string;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
}

export declare const Checkbox: React.ComponentType<CheckboxProps>;
