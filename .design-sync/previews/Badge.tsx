import * as React from 'react';
import { Badge } from '@klassapp/ds';

const row: React.CSSProperties = {
    display: 'flex',
    flexWrap: 'wrap',
    gap: 8,
    alignItems: 'center',
};

/** Every variant in the Blade whitelist, grouped by the colour they share. */
export const AllVariants = () => (
    <div style={{ display: 'grid', gap: 12 }}>
        <div style={row}>
            <Badge variant="pending">Pending</Badge>
            <Badge variant="info">SchoolPay</Badge>
        </div>
        <div style={row}>
            <Badge variant="approved">Approved</Badge>
            <Badge variant="paid">Paid</Badge>
            <Badge variant="active">Active</Badge>
        </div>
        <div style={row}>
            <Badge variant="rejected">Rejected</Badge>
            <Badge variant="unpaid">Unpaid</Badge>
        </div>
        <div style={row}>
            <Badge variant="warning">Unmatched</Badge>
            <Badge variant="inactive">Archived</Badge>
        </div>
    </div>
);

/** The two sizes. */
export const Sizes = () => (
    <div style={row}>
        <Badge variant="paid" size="sm">
            Paid (sm)
        </Badge>
        <Badge variant="paid" size="md">
            Paid (md)
        </Badge>
    </div>
);

/**
 * How badges actually appear in `admin/fees/unmatched.blade.php` — tagging the
 * payment channel on a reconciliation row.
 */
export const PaymentChannels = () => (
    <div style={{ display: 'grid', gap: 10 }}>
        <div style={{ ...row, justifyContent: 'space-between', maxWidth: 360 }}>
            <span style={{ color: 'var(--d-text)' }}>UGX 450,000 · Mukasa David</span>
            <Badge variant="info">SchoolPay</Badge>
        </div>
        <div style={{ ...row, justifyContent: 'space-between', maxWidth: 360 }}>
            <span style={{ color: 'var(--d-text)' }}>UGX 120,000 · Namuli Grace</span>
            <Badge variant="warning">Mobile Money</Badge>
        </div>
        <div style={{ ...row, justifyContent: 'space-between', maxWidth: 360 }}>
            <span style={{ color: 'var(--d-text)' }}>UGX 300,000 · Okello Brian</span>
            <Badge variant="unpaid">Unmatched</Badge>
        </div>
    </div>
);

/** Unknown variants fall back to `info` — the Blade whitelist behaviour. */
export const UnknownVariantFallsBack = () => (
    <div style={row}>
        <Badge variant={'not-a-variant' as never}>Falls back to info</Badge>
        <Badge variant="info">info</Badge>
    </div>
);
