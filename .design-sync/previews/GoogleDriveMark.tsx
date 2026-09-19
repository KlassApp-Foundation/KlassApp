import * as React from 'react';
import { GoogleDriveMark, SlackMark, WhatsAppMark } from '@klassapp/ds';

/** Sizing is external — the SVG has no intrinsic width. */
export const Sizes = () => (
    <div style={{ display: 'flex', gap: 20, alignItems: 'center' }}>
        <GoogleDriveMark style={{ width: 20, height: 20 }} />
        <GoogleDriveMark style={{ width: 32, height: 32 }} />
        <GoogleDriveMark style={{ width: 48, height: 48 }} />
        <GoogleDriveMark style={{ width: 64, height: 64 }} />
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
        <GoogleDriveMark style={{ width: 22, height: 22 }} />
        <span style={{ color: 'var(--d-text)', fontWeight: 600 }}>Google Drive</span>
    </div>
);

/** All three connected tools together — the Toshi hub's tool row. */
export const ConnectedTools = () => (
    <div style={{ display: 'flex', gap: 24, alignItems: 'center' }}>
        <WhatsAppMark style={{ width: 36, height: 36 }} />
        <GoogleDriveMark style={{ width: 36, height: 36 }} />
        <SlackMark style={{ width: 36, height: 36 }} />
    </div>
);
