#!/usr/bin/env node
/**
 * Read-only crawl of Demo Junior on staging.
 *
 * Eight roles. GET only. Never follows a link whose URL or label contains
 * delete, remove, destroy, approve, reject, send, reset, or logout.
 * Password comes from ROLE_CRAWL_PASSWORD and is never printed.
 *
 *   ROLE_CRAWL_PASSWORD=… node e2e/role-crawl.mjs
 */
import { chromium } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const BASE = 'https://test.klassapp.xyz';
const DOMAIN = 'junior.demo.klassapp.test';
const BLOCK = /delete|remove|destroy|approve|reject|send|reset|logout/i;
const ROLES = [
    ['admin', `admin@${DOMAIN}`],
    ['head teacher', `headteacher@${DOMAIN}`],
    ['class teacher', `teacher1@${DOMAIN}`],
    ['subject teacher', `teacher2@${DOMAIN}`],
    ['bursar', `bursar@${DOMAIN}`],
    ['librarian', `library@${DOMAIN}`],
    ['receptionist', `reception@${DOMAIN}`],
    ['parent', `parent1@${DOMAIN}`],
];

const password = process.env.ROLE_CRAWL_PASSWORD || '';
if (!password) {
    console.error('role-crawl: set ROLE_CRAWL_PASSWORD');
    process.exit(1);
}

function sameHost(href) {
    try {
        const url = new URL(href, BASE);
        return url.origin === new URL(BASE).origin;
    } catch {
        return false;
    }
}

async function signIn(page, email) {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    const form = page.locator('[data-testid="preview-login-form"]');
    await form.waitFor({ state: 'visible', timeout: 30_000 });
    await form.locator('#email').fill(email);
    await form.locator('#password').fill(password);
    await Promise.all([
        page.waitForLoadState('domcontentloaded').catch(() => {}),
        form.locator('[data-testid="ap-primary-submit"]').click(),
    ]);
    await page.waitForTimeout(1500);
    return !/\/login/.test(page.url());
}

async function expandSidebar(page) {
    for (let i = 0; i < 12; i++) {
        const closed = page.locator('#admin-sidebar .sidebar-v3-chevron[aria-expanded="false"]');
        if (await closed.count() === 0) break;
        await closed.first().click({ timeout: 2000 }).catch(() => {});
        await page.waitForTimeout(150);
    }
}

async function sidebarLinks(page) {
    await expandSidebar(page);
    return page.evaluate(() => {
        const root = document.querySelector('#admin-sidebar') || document.body;
        return [...root.querySelectorAll('a[href]')].map((a) => ({
            href: a.href,
            label: (a.getAttribute('aria-label') || a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80),
        }));
    });
}

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const report = [];

for (const [role, email] of ROLES) {
    const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await context.newPage();
    const consoleErrors = [];
    const assetFailures = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') consoleErrors.push(msg.text().slice(0, 240));
    });
    page.on('pageerror', (err) => consoleErrors.push(String(err.message || err).slice(0, 240)));
    page.on('response', (res) => {
        if (res.status() < 400 || res.request().resourceType() === 'document') return;
        const url = res.url().split('?')[0];
        assetFailures.push(`${res.status()} ${url.slice(0, 180)}`);
    });

    const row = {
        role,
        email,
        login: false,
        links: 0,
        skipped: [],
        http4xx: [],
        http5xx: [],
        dead: [],
        consoleErrors: [],
        assetFailures: [],
    };
    try {
        row.login = await signIn(page, email);
        if (!row.login) {
            report.push(row);
            console.log(`${role}: login failed`);
            await context.close();
            continue;
        }
        const links = await sidebarLinks(page);
        const seen = new Set();
        const queue = [];
        for (const link of links) {
            const key = `${link.href} ${link.label}`;
            if (seen.has(link.href)) continue;
            seen.add(link.href);
            if (!sameHost(link.href) || BLOCK.test(`${link.href} ${link.label}`)) {
                row.skipped.push(link.label || link.href);
                continue;
            }
            queue.push(link);
        }
        row.links = queue.length;
        for (const link of queue) {
            let status = 0;
            try {
                const response = await page.goto(link.href, { waitUntil: 'domcontentloaded', timeout: 25_000 });
                status = response ? response.status() : 0;
            } catch (err) {
                row.dead.push({ label: link.label, href: link.href, error: String(err.message || err).slice(0, 160) });
                continue;
            }
            const item = { label: link.label, href: new URL(link.href).pathname, status };
            if (status === 404 || status === 410) row.dead.push(item);
            else if (status >= 500) row.http5xx.push(item);
            else if (status >= 400) row.http4xx.push(item);
        }
        row.consoleErrors = [...new Set(consoleErrors)].slice(0, 30);
        row.assetFailures = [...new Set(assetFailures)].slice(0, 20);
    } catch (err) {
        row.dead.push({ error: String(err.message || err).slice(0, 200) });
    }
    report.push(row);
    console.log(`${role}: links=${row.links} 4xx=${row.http4xx.length} 5xx=${row.http5xx.length} dead=${row.dead.length} console=${row.consoleErrors.length} assets=${row.assetFailures.length}`);
    await context.close();
}

await browser.close();
const outDir = path.join(path.dirname(fileURLToPath(import.meta.url)), 'artifacts', 'role-crawl');
fs.mkdirSync(outDir, { recursive: true });
const out = path.join(outDir, 'report.json');
fs.writeFileSync(out, JSON.stringify(report, null, 2));
console.log(`role-crawl: wrote ${out}`);
