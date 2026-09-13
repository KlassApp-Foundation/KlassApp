import * as React from 'react';
import { Badge, Button, Card } from '@klassapp/ds';

/** The canonical titled card — the shape used across the parent dashboard panels. */
export const WithTitle = () => (
    <Card title="Fee Balance">
        <p style={{ margin: '0 0 4px', fontSize: 28, fontWeight: 700, color: 'var(--d-text)' }}>
            UGX 450,000
        </p>
        <p style={{ margin: 0, color: 'var(--d-text-secondary)', fontSize: 14 }}>
            Term 2 2026 · due 30 September
        </p>
    </Card>
);

/** Untitled card carrying its own composition. */
export const Plain = () => (
    <Card>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 16 }}>
            <div>
                <p style={{ margin: '0 0 4px', fontWeight: 600, color: 'var(--d-text)' }}>
                    Nakato Sarah
                </p>
                <p style={{ margin: 0, fontSize: 14, color: 'var(--d-text-secondary)' }}>
                    Senior 2 · Admission 2024/0418
                </p>
            </div>
            <Badge variant="active">Active</Badge>
        </div>
    </Card>
);

/** The four padding steps. `none` is what the parent panels use. */
export const Paddings = () => (
    <div style={{ display: 'grid', gap: 12 }}>
        <Card padding="none" title="padding=none">
            <p style={{ margin: 0, padding: 12, color: 'var(--d-text-secondary)' }}>Flush content</p>
        </Card>
        <Card padding="sm" title="padding=sm">
            <p style={{ margin: 0, color: 'var(--d-text-secondary)' }}>Tight</p>
        </Card>
        <Card padding="default" title="padding=default">
            <p style={{ margin: 0, color: 'var(--d-text-secondary)' }}>Standard</p>
        </Card>
        <Card padding="lg" title="padding=lg">
            <p style={{ margin: 0, color: 'var(--d-text-secondary)' }}>Roomy</p>
        </Card>
    </div>
);

/** Shadow scale; `none` emits no shadow class at all. */
export const Shadows = () => (
    <div style={{ display: 'grid', gap: 12 }}>
        <Card shadow="none" title="shadow=none">
            <p style={{ margin: 0, color: 'var(--d-text-secondary)' }}>Flat</p>
        </Card>
        <Card shadow="sm" title="shadow=sm (default)">
            <p style={{ margin: 0, color: 'var(--d-text-secondary)' }}>Resting</p>
        </Card>
        <Card shadow="md" title="shadow=md">
            <p style={{ margin: 0, color: 'var(--d-text-secondary)' }}>Raised</p>
        </Card>
        <Card shadow="lg" title="shadow=lg">
            <p style={{ margin: 0, color: 'var(--d-text-secondary)' }}>Floating</p>
        </Card>
    </div>
);

/** `hover` adds the lift-on-hover affordance — used for clickable cards. */
export const HoverLift = () => (
    <Card hover title="Attendance">
        <p style={{ margin: '0 0 12px', color: 'var(--d-text-secondary)', fontSize: 14 }}>
            94% present this term
        </p>
        <Button variant="ghost" size="sm" href="/parent/attendance">
            View register
        </Button>
    </Card>
);
