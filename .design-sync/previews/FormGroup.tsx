import * as React from 'react';
import { FormGroup } from '@klassapp/ds';

const stack: React.CSSProperties = { display: 'grid', gap: 16, maxWidth: 420 };

/** The canonical text field, as used in the subject-create form. */
export const TextInput = () => (
    <div style={stack}>
        <FormGroup label="Subject Name" name="name" type="text" required placeholder="e.g. Mathematics" />
        <FormGroup label="Subject Code" name="code" type="text" required placeholder="e.g. MTC" />
    </div>
);

/** Select with options — the `:options` array pattern from the Blade views. */
export const SelectInput = () => (
    <div style={stack}>
        <FormGroup
            label="Class"
            name="class_id"
            type="select"
            required
            placeholder="Select…"
            options={{
                '1': 'Primary 5',
                '2': 'Primary 6',
                '3': 'Primary 7',
                '4': 'Senior 1',
                '5': 'Senior 2',
            }}
        />
        <FormGroup
            label="Type"
            name="type"
            type="select"
            options={{ tuition: 'Tuition', boarding: 'Boarding', transport: 'Transport' }}
        />
    </div>
);

/** Textarea variant. */
export const Textarea = () => (
    <div style={stack}>
        <FormGroup
            label="Description"
            name="desc"
            type="textarea"
            placeholder="Optional notes for this fee category…"
            help="Shown to parents on the WhatsApp fee statement."
        />
    </div>
);

/** Validation error state — adds `.ds-form-input-error` and the message. */
export const WithError = () => (
    <div style={stack}>
        <FormGroup
            label="Admission Number"
            name="admission_no"
            type="text"
            value="2024/0418"
            error="That admission number is already taken."
            required
        />
    </div>
);

/** Help text and a non-required field, for contrast with the error state. */
export const WithHelpText = () => (
    <div style={stack}>
        <FormGroup
            label="Parent WhatsApp Number"
            name="whatsapp"
            type="text"
            placeholder="+256 7XX XXX XXX"
            help="Used for fee reminders and attendance alerts. Include the country code."
        />
        <FormGroup label="Date of Birth" name="dob" type="date" />
    </div>
);
