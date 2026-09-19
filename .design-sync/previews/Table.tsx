import * as React from 'react';
import { Badge, Table } from '@klassapp/ds';

/**
 * The fee-payments ledger — headers lifted from
 * `admin/fees/payments.blade.php`.
 */
export const FeePayments = () => (
    <Table headers={['#', 'Student', 'Amount', 'Method', 'Reference', 'Paid On']}>
        <tr>
            <td>1</td>
            <td>Nakato Sarah</td>
            <td>UGX 450,000</td>
            <td>SchoolPay</td>
            <td>SP-2026-04182</td>
            <td>12 Sep 2026</td>
        </tr>
        <tr>
            <td>2</td>
            <td>Mukasa David</td>
            <td>UGX 300,000</td>
            <td>Mobile Money</td>
            <td>MM-771204933</td>
            <td>11 Sep 2026</td>
        </tr>
        <tr>
            <td>3</td>
            <td>Namuli Grace</td>
            <td>UGX 120,000</td>
            <td>Cash</td>
            <td>RCT-00913</td>
            <td>09 Sep 2026</td>
        </tr>
    </Table>
);

/** Student roster with status badges composed into cells. */
export const StudentRoster = () => (
    <Table headers={['#', 'Student Name', 'Class', 'Parent / Guardian', 'Status']}>
        <tr>
            <td>1</td>
            <td>Okello Brian</td>
            <td>Senior 2</td>
            <td>Okello James</td>
            <td>
                <Badge variant="active">Active</Badge>
            </td>
        </tr>
        <tr>
            <td>2</td>
            <td>Achieng Mercy</td>
            <td>Primary 5</td>
            <td>Achieng Ruth</td>
            <td>
                <Badge variant="pending">Pending</Badge>
            </td>
        </tr>
        <tr>
            <td>3</td>
            <td>Ssempala Isaac</td>
            <td>Senior 4</td>
            <td>Ssempala Peter</td>
            <td>
                <Badge variant="inactive">Archived</Badge>
            </td>
        </tr>
    </Table>
);

/** `density="compact"` swaps `.dt-comfortable` for `.dt-compact`. */
export const CompactDensity = () => (
    <Table headers={['Date', 'Student', 'Amount', 'Channel']} density="compact">
        <tr>
            <td>12 Sep</td>
            <td>Nakato Sarah</td>
            <td>UGX 450,000</td>
            <td>SchoolPay</td>
        </tr>
        <tr>
            <td>11 Sep</td>
            <td>Mukasa David</td>
            <td>UGX 300,000</td>
            <td>Mobile Money</td>
        </tr>
        <tr>
            <td>09 Sep</td>
            <td>Namuli Grace</td>
            <td>UGX 120,000</td>
            <td>Cash</td>
        </tr>
    </Table>
);

/** `selectable` prepends the checkbox column; `sortable` adds header arrows. */
export const SelectableSortable = () => (
    <Table headers={['Student', 'Class', 'Balance']} selectable sortable>
        <tr>
            <td className="dt-cell-check">
                <input type="checkbox" className="dt-checkbox" readOnly />
            </td>
            <td>Nakato Sarah</td>
            <td>Senior 2</td>
            <td>UGX 0</td>
        </tr>
        <tr>
            <td className="dt-cell-check">
                <input type="checkbox" className="dt-checkbox" defaultChecked readOnly />
            </td>
            <td>Mukasa David</td>
            <td>Senior 2</td>
            <td>UGX 150,000</td>
        </tr>
    </Table>
);

/** With no `headers`, no `<thead>` is rendered at all. */
export const HeaderlessTable = () => (
    <Table>
        <tr>
            <td>Term 1</td>
            <td>UGX 450,000</td>
        </tr>
        <tr>
            <td>Term 2</td>
            <td>UGX 450,000</td>
        </tr>
    </Table>
);
