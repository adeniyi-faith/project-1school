// Phase 5: phone layouts (320 and 360 wide), screens with big lists, then (through run.mjs) everything again together.
// A phone page must not scroll sideways: neither the page nor its inner main area may be wider than the screen.
import { watch, visit, login, demoLogin, overflow, pageText } from './lib/harness.mjs';

const PHONES = [{ width: 320, height: 640 }, { width: 360, height: 740 }];
const ROLES = [
    ['school-admin@genius-sms.test', 'school administrator'],
    ['teacher@genius-sms.test', 'teacher'],
    ['accountant@genius-sms.test', 'accountant'],
    ['librarian@genius-sms.test', 'librarian'],
    ['student@genius-sms.test', 'student'],
    ['parent@genius-sms.test', 'parent'],
];

export default async function ({ browser, findings: f }) {
    for (const [email, label] of ROLES) await phoneRole(browser, f, email, label);
    await bigLists(browser, f);
}

async function phoneRole(browser, f, email, label) {
    // Collect this person's menu on a laptop screen, then open each page on small phones
    const desk = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const dp = await desk.newPage();
    if (!(await login(dp, f, email))) { await desk.close(); return; }
    const links = await dp.evaluate(() => [...new Set([...document.querySelectorAll('nav[aria-label="Main"] a')].map(a => new URL(a.href).pathname))]);
    const state = await desk.storageState();
    await desk.close();
    f.ok(`${label}: has menu pages to check`, links.length >= 2, `${links.length} found`);

    for (const size of PHONES) {
        const ctx = await browser.newContext({ viewport: size, storageState: state, hasTouch: true, isMobile: true });
        const page = await ctx.newPage();
        watch(page, f);
        for (const url of links) {
            if (!(await visit(page, f, url, { slowMs: 4000 }))) continue;
            const o = await overflow(page);
            f.ok(`${label} ${size.width}px ${url}: page does not scroll sideways`, o.page <= 1, `${o.page}px too wide`);
            f.ok(`${label} ${size.width}px ${url}: main area does not scroll sideways`, o.main <= 1, `${o.main}px too wide`);
        }
        await ctx.close();
    }
}

async function bigLists(browser, f) {
    // The demo school has 374 pupils: lists must stay quick and must page, not dump everything
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    watch(page, f);
    if (!(await demoLogin(page, f, 'School Admin'))) { await ctx.close(); return; }
    const pages = ['/school/reports/dashboard', '/school/students', '/school/students?search=a', '/school/students?page=5', '/school/attendance', '/school/fees/invoices', '/school/fees/invoices?page=3', '/school/fees/structures', '/school/results', '/school/reports/finance', '/school/reports/academic', '/school/reports/audit-log', '/school/staff', '/school/library/books', '/school/communication/sent', '/school/admissions/inquiries'];
    for (const p of pages) {
        const r = await visit(page, f, p, { slowMs: 3000 });
        if (!r) continue;
        // a huge list is split into pages: the row count on one page stays small
        const rows = await page.evaluate(() => document.querySelectorAll('tbody tr').length);
        f.ok(`${p}: no more than 100 table rows on one page (${rows})`, rows <= 100, `${rows} rows`);
    }
    const t = await pageText(page);
    await ctx.close();
}
