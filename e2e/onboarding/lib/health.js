// Page-health collectors: console errors, page errors, failed network requests,
// >=500 responses. Plus a horizontal-scroll check helper.

function attach(page) {
    const state = {
        consoleErrors: [],
        pageErrors: [],
        failedRequests: [],
        httpErrors: [],
        badResponses: [],
    };
    page.on('console', (m) => {
        if (m.type() === 'error') state.consoleErrors.push(m.text().slice(0, 300));
    });
    page.on('pageerror', (e) => state.pageErrors.push(String(e).slice(0, 300)));
    page.on('requestfailed', (r) => {
        const err = r.failure() ? r.failure().errorText : 'unknown';
        // ERR_ABORTED is commonly caused by our own navigations; keep but mark.
        state.failedRequests.push({ url: r.url().slice(0, 200), error: err });
    });
    page.on('response', (r) => {
        if (r.status() >= 500) state.httpErrors.push({ url: r.url().slice(0, 200), status: r.status() });
        if (r.status() >= 400 && r.status() < 500) state.badResponses.push({ url: r.url().slice(0, 200), status: r.status() });
    });
    return state;
}

async function hasHorizontalScroll(page) {
    return page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 2);
}

async function checkNoHorizontalScroll(page, label, findings) {
    const bad = await hasHorizontalScroll(page).catch(() => false);
    if (bad) findings.push(`Horizontal scroll on ${label} (${page.url()})`);
    return !bad;
}

function summarize(state) {
    return {
        consoleErrors: state.consoleErrors,
        pageErrors: state.pageErrors,
        serverErrors: state.httpErrors,
        failedRequests: state.failedRequests.filter((r) => !r.error.includes('ERR_ABORTED')),
        abortedRequests: state.failedRequests.filter((r) => r.error.includes('ERR_ABORTED')).length,
        clientErrors: state.badResponses,
    };
}

function toFindings(state, label) {
    const out = [];
    if (state.consoleErrors.length) out.push(`${label}: console errors x${state.consoleErrors.length}`);
    if (state.pageErrors.length) out.push(`${label}: page errors x${state.pageErrors.length}`);
    if (state.httpErrors.length) out.push(`${label}: server (5xx) responses x${state.httpErrors.length}`);
    const realFails = state.failedRequests.filter((r) => !r.error.includes('ERR_ABORTED'));
    if (realFails.length) out.push(`${label}: failed requests x${realFails.length}`);
    return out;
}

module.exports = { attach, summarize, toFindings, hasHorizontalScroll, checkNoHorizontalScroll };
