import * as React from 'react';
import { SlackMark } from '@klassapp/ds';

/** Sizing is external — the SVG has no intrinsic width. */
export const Sizes = () => (
    <div style={{ display: 'flex', gap: 20, alignItems: 'center' }}>
        <SlackMark style={{ width: 20, height: 20 }} />
        <SlackMark style={{ width: 32, height: 32 }} />
        <SlackMark style={{ width: 48, height: 48 }} />
        <SlackMark style={{ width: 64, height: 64 }} />
    </div>
);

/** In a tool chip — how the mark appears on the Toshi connector surfaces. */
export const InToolChip = () => (
    <div
        style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: 10,
            padding: '10px 16px',
            borderRadius: 999,
            background: 'var(--d-white)',
            border: '1px solid var(--d-border)',
        }}
    >
        <SlackMark style={{ width: 22, height: 22 }} />
        <span style={{ color: 'var(--d-text)', fontWeight: 600 }}>Slack</span>
    </div>
);

/** `decorative={false}` exposes a `<title>` for screen readers. */
export const Accessible = () => <SlackMark decorative={false} style={{ width: 48, height: 48 }} />;
