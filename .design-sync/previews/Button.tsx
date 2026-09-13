import * as React from 'react';
import { Button } from '@klassapp/ds';

/** All six variants, in the order the Blade `match()` declares them. */
export const Variants = () => (
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'center' }}>
        <Button variant="primary">Save Changes</Button>
        <Button variant="success">Record Payment</Button>
        <Button variant="danger">Delete Subject</Button>
        <Button variant="warning">Flag for Review</Button>
        <Button variant="outline">Cancel</Button>
        <Button variant="ghost">Back</Button>
    </div>
);

/**
 * The three sizes. `md` is the default and — faithfully to the Blade source —
 * `.ds-btn-md` has no rule in `dashboard-refresh.css`, so it renders at the
 * `.ds-btn` base size.
 */
export const Sizes = () => (
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'center' }}>
        <Button variant="primary" size="sm">
            Small
        </Button>
        <Button variant="primary" size="md">
            Medium (default)
        </Button>
        <Button variant="primary" size="lg">
            Large
        </Button>
    </div>
);

/** With `href` the component renders an `<a>` — the pattern used for back links. */
export const AsLink = () => (
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'center' }}>
        <Button href="/admin/standards" variant="ghost" size="sm">
            ← Back to Classes
        </Button>
        <Button href="/admin/students/create" variant="primary" size="sm">
            Add Student
        </Button>
    </div>
);

/** Disabled renders `disabled` on a button, `aria-disabled` + `tabindex=-1` on a link. */
export const Disabled = () => (
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 12, alignItems: 'center' }}>
        <Button variant="primary" disabled>
            Submitting…
        </Button>
        <Button variant="outline" disabled>
            Unavailable
        </Button>
        <Button href="/admin/fees" variant="ghost" disabled>
            Locked Term
        </Button>
    </div>
);
