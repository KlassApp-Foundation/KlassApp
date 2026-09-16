import * as React from 'react';

export type KpiCardColor = 'blue' | 'green' | 'amber' | 'red' | 'purple';

export type KpiCardIcon =
    | 'users'
    | 'classes'
    | 'door'
    | 'exam'
    | 'calendar'
    | 'whatsapp'
    | 'message'
    | 'book'
    | 'library'
    | 'bell'
    | 'notice'
    | 'dollar'
    | 'money'
    | 'check'
    | 'tasks';

export interface KpiCardProps {
    /** Icon key. Anything unrecognised renders the info-circle fallback. */
    icon?: KpiCardIcon | string;
    /** The headline figure. Defaults to an em dash. */
    value?: string | number;
    /** Caption under the value. */
    label?: string;
    /** Tints the icon chip. Unknown values fall back to `blue`. */
    color?: KpiCardColor;
    /** When set, the whole card becomes an `<a href>`. */
    link?: string;
}

const COLORS: Record<KpiCardColor, { bg: string; text: string }> = {
    blue: { bg: 'rgba(30,111,217,0.10)', text: 'var(--d-blue)' },
    green: { bg: 'rgba(22,163,74,0.10)', text: '#16A34A' },
    amber: { bg: 'rgba(217,119,6,0.10)', text: 'var(--d-amber)' },
    red: { bg: 'rgba(220,38,38,0.10)', text: 'var(--d-red)' },
    purple: { bg: 'rgba(139,92,246,0.10)', text: '#8B5CF6' },
};

const ICON_PATHS: Record<string, string> = {
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
    classes: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    exam: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    whatsapp:
        'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    book: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    bell: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    dollar: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    check: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    fallback: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

/** The Blade template routes several keys to the same glyph. */
const ICON_ALIASES: Record<string, string> = {
    door: 'classes',
    calendar: 'exam',
    message: 'whatsapp',
    library: 'book',
    notice: 'bell',
    money: 'dollar',
    tasks: 'check',
};

/**
 * KlassApp dashboard KPI tile.
 *
 * Blade equivalent: `<x-ds-kpi-card icon="users" value="42" label="Students" color="blue" />`
 * (`resources/views/components/ds-kpi-card.blade.php`).
 */
export function KpiCard({
    icon = '',
    value = '—',
    label = '',
    color = 'blue',
    link = '',
}: KpiCardProps) {
    const tint = COLORS[color] ?? COLORS.blue;
    const pathKey = ICON_ALIASES[icon] ?? icon;
    const path = ICON_PATHS[pathKey] ?? ICON_PATHS.fallback;

    const body = (
        <>
            <div
                className="ds-kpi-icon-wrap"
                style={{ background: tint.bg, color: tint.text }}
            >
                <svg
                    className="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2}
                    viewBox="0 0 24 24"
                >
                    <path d={path} />
                </svg>
            </div>
            <p className="ds-kpi-value">{value}</p>
            <p className="ds-kpi-label">{label}</p>
        </>
    );

    if (link) {
        return (
            <a href={link} className="ds-kpi-card group">
                {body}
            </a>
        );
    }

    return <div className="ds-kpi-card group">{body}</div>;
}
