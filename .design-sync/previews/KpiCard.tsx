import * as React from 'react';
import { KpiCard } from '@klassapp/ds';

const grid: React.CSSProperties = {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))',
    gap: 16,
};

/** The admin dashboard row, matching the real `<x-ds-kpi-card>` call sites. */
export const DashboardGrid = () => (
    <div style={grid}>
        <KpiCard icon="users" value="1,284" label="Total Students" color="blue" />
        <KpiCard icon="users" value="63" label="Teachers" color="green" />
        <KpiCard icon="whatsapp" value="1,102" label="WhatsApp Linked" color="green" />
        <KpiCard icon="dollar" value="UGX 18.4M" label="Fees Collected" color="amber" />
    </div>
);

/** Every colour in the Blade `$colorMap`. */
export const Colors = () => (
    <div style={grid}>
        <KpiCard icon="users" value="42" label="blue" color="blue" />
        <KpiCard icon="check" value="42" label="green" color="green" />
        <KpiCard icon="bell" value="42" label="amber" color="amber" />
        <KpiCard icon="dollar" value="42" label="red" color="red" />
        <KpiCard icon="book" value="42" label="purple" color="purple" />
    </div>
);

/** The distinct icon glyphs the template can render. */
export const Icons = () => (
    <div style={grid}>
        <KpiCard icon="users" value="—" label="users" />
        <KpiCard icon="classes" value="—" label="classes / door" />
        <KpiCard icon="exam" value="—" label="exam / calendar" />
        <KpiCard icon="whatsapp" value="—" label="whatsapp / message" />
        <KpiCard icon="book" value="—" label="book / library" />
        <KpiCard icon="bell" value="—" label="bell / notice" />
        <KpiCard icon="dollar" value="—" label="dollar / money" />
        <KpiCard icon="check" value="—" label="check / tasks" />
        <KpiCard icon="unknown-key" value="—" label="fallback" />
    </div>
);

/** With `link` the whole tile becomes an anchor. */
export const AsLink = () => (
    <div style={grid}>
        <KpiCard
            icon="users"
            value="1,284"
            label="Total Students"
            color="blue"
            link="/reception/students"
        />
        <KpiCard
            icon="exam"
            value="12"
            label="Upcoming Exams"
            color="purple"
            link="/admin/exams"
        />
    </div>
);

/** Defaults: no icon key, em-dash value, blue tint. */
export const EmptyState = () => (
    <div style={grid}>
        <KpiCard label="Awaiting sync" />
    </div>
);
