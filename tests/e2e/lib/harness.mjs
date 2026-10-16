// Shared helpers for the browser checks: start the app, sign in, watch for problems, report.
import { chromium } from 'playwright-core';
import { spawn, spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, writeFileSync, openSync } from 'node:fs';
import { DatabaseSync } from 'node:sqlite';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');
export const WORK = path.join(ROOT, 'tests/e2e/.work');
export const DB_FILE = path.join(WORK, 'e2e.sqlite');
export const PORT = process.env.E2E_PORT || '8765';
export const BASE = process.env.E2E_BASE || `http://127.0.0.1:${PORT}`;

const appEnv = () => ({
    ...process.env,
    APP_ENV: 'local', APP_DEBUG: process.env.E2E_DEBUG ? 'true' : 'false', DB_CONNECTION: 'sqlite', DB_DATABASE: DB_FILE,
    SESSION_DRIVER: 'file', CACHE_STORE: 'file', QUEUE_CONNECTION: 'sync', LOGIN_DRIVER: 'database',
    DEMO_ENABLED: 'true', APP_URL: BASE, MAIL_MAILER: 'log', LOG_CHANNEL: 'single', SHOW_DEMO_ACCOUNTS: 'false',
    ...(process.env.E2E_WORKERS ? { PHP_CLI_SERVER_WORKERS: process.env.E2E_WORKERS } : {}),
});

/** Make a fresh throw-away database (never the real one) filled with the demo schools. */
export function setupDatabase({ fresh = false } = {}) {
    mkdirSync(WORK, { recursive: true });
    if (existsSync(DB_FILE) && !fresh) return;
    writeFileSync(DB_FILE, '');
    const run = (...args) => {
        const r = spawnSync('php', ['artisan', ...args], { cwd: ROOT, env: appEnv(), encoding: 'utf8' });
        if (r.status !== 0) throw new Error(`artisan ${args.join(' ')} failed:\n${r.stdout}\n${r.stderr}`);
    };
    run('migrate:fresh', '--seed', '--force');
    run('db:seed', '--class=RolePermissionSeeder', '--force');
    run('demo:fill');
}

export function startServer() {
    // php -S directly (what "artisan serve" does); the router file adds a helper that older PHP lacks
    const log = openSync(path.join(WORK, 'server.log'), 'w');
    return spawn('php', ['-S', `127.0.0.1:${PORT}`, '-t', path.join(ROOT, 'public'), path.join(ROOT, 'tests/e2e/lib/router.php')],
        { cwd: path.join(ROOT, 'public'), env: { ...appEnv(), LARAVEL_SAIL: '' }, stdio: ['ignore', log, log] });
}

export async function waitForServer(timeoutMs = 30000) {
    const end = Date.now() + timeoutMs;
    while (Date.now() < end) {
        try { const r = await fetch(BASE + '/login'); if (r.status < 500) return; } catch { /* not up yet */ }
        await new Promise(r => setTimeout(r, 300));
    }
    throw new Error('The app did not start');
}

/** Read straight from the test database, so expected answers do not depend on the app's own screens. */
export function db() {
    const d = new DatabaseSync(DB_FILE, { readOnly: true });
    return {
        all: (sql, ...p) => d.prepare(sql).all(...p),
        one: (sql, ...p) => d.prepare(sql).get(...p),
        value: (sql, ...p) => { const r = d.prepare(sql).get(...p); return r ? Object.values(r)[0] : undefined; },
        close: () => d.close(),
    };
}

export function findChromium() {
    if (process.env.CHROMIUM_PATH) return process.env.CHROMIUM_PATH;
    const base = process.env.PLAYWRIGHT_BROWSERS_PATH || '/opt/pw-browsers';
    const direct = path.join(base, 'chromium', 'chrome-linux', 'chrome');
    if (existsSync(direct)) return direct;
    if (existsSync(path.join(base, 'chromium'))) return path.join(base, 'chromium');
    const dirs = existsSync(base) ? readdirSync(base).filter(d => d.startsWith('chromium-')) : [];
    for (const d of dirs) { const p = path.join(base, d, 'chrome-linux', 'chrome'); if (existsSync(p)) return p; }
    return undefined; // let Playwright look in its own default place
}

export async function launch() {
    return chromium.launch({ executablePath: findChromium(), args: ['--no-sandbox'] });
}

const IGNORED_CONSOLE = [/favicon/i, /Failed to load resource.*(401|403|404|419|422)/i, /Download the React DevTools/i];

/** Collects everything that looks wrong while a page is used. */
export class Findings {
    constructor() { this.items = []; this.checks = 0; this.passed = 0; this.timings = []; }
    add(kind, where, detail) { this.items.push({ kind, where, detail: String(detail).slice(0, 400) }); }
    /** An expected-answer check: compare, record, never throw. */
    check(name, actual, expected) {
        this.checks++;
        if (process.env.E2E_VERBOSE) console.log('  check:', name);
        const same = typeof expected === 'number' && typeof actual === 'number' ? Math.abs(actual - expected) < 0.005 : String(actual) === String(expected);
        if (same) this.passed++; else this.add('WRONG ANSWER', name, `expected ${expected} but got ${actual}`);
        return same;
    }
    ok(name, condition, detail = '') {
        this.checks++;
        if (process.env.E2E_VERBOSE) console.log('  check:', name);
        if (condition) this.passed++; else this.add('CHECK FAILED', name, detail);
        return !!condition;
    }
}

export function watch(page, findings, label = () => page.url()) {
    page.on('console', m => {
        if (m.type() !== 'error') return;
        const t = m.text();
        if (IGNORED_CONSOLE.some(r => r.test(t))) return;
        findings.add('console error', label(), t);
    });
    page.on('pageerror', e => findings.add('page crash', label(), e.message));
    page.on('requestfailed', r => {
        const f = r.failure()?.errorText ?? '';
        if (/ABORTED/i.test(f)) return;
        findings.add('request failed', r.url().replace(BASE, ''), f);
    });
    page.on('response', r => {
        const s = r.status();
        const u = r.url();
        if (s >= 500) {
            // With E2E_DEBUG=1 the page body names the real cause
            r.text().then(t => findings.add('server error ' + s, u.replace(BASE, ''), r.request().method() + ' body=' + (r.request().postData() ?? '').slice(0, 300) + ' → ' + (t.match(/"message":"([^"]{0,200})/)?.[1] ?? t.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 200)))).catch(() => findings.add('server error ' + s, u.replace(BASE, ''), r.request().method()));
        }
    });
}

const SLOW_MS = Number(process.env.E2E_SLOW_MS || 2500);

/** Open a page and check it is a real page, loaded quickly, not wider than the screen. */
export async function visit(page, findings, url, { slowMs = SLOW_MS, expect, allowStatus = [200], note } = {}) {
    const t0 = Date.now();
    let resp;
    try { resp = await page.goto(BASE + url, { waitUntil: 'load', timeout: 30000 }); }
    catch (e) { findings.add('page did not load', url, e.message); return null; }
    await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
    const ms = Date.now() - t0;
    findings.timings.push({ url, ms });
    const status = resp?.status() ?? 0;
    if (!allowStatus.includes(status)) findings.add('unexpected status ' + status, url, note ?? '');
    const text = await page.evaluate(() => document.body?.innerText ?? '');
    if (/Server Error|Whoops|SQLSTATE|Undefined (variable|array key|property)|Call to undefined|Class "[^"]+" not found|Attempt to read property/i.test(text)) findings.add('error page', url, text.slice(0, 200).replace(/\s+/g, ' '));
    if (status === 200 && /Page Not Found|404/.test(text.slice(0, 120))) findings.add('error page', url, 'shows not found');
    if (ms > slowMs) findings.add('slow page', url, `${ms} ms`);
    if (expect) {
        const hit = expect instanceof RegExp ? expect.test(text) : text.includes(expect);
        if (!hit) findings.add('missing content', url, `did not show ${expect}`);
    }
    return { status, text, ms };
}

/** Sideways scroll check: the page itself and the main area (phones). */
export async function overflow(page) {
    return page.evaluate(() => {
        const de = document.documentElement;
        const main = document.querySelector('main');
        return {
            page: de.scrollWidth - de.clientWidth,
            main: main ? main.scrollWidth - main.clientWidth : 0,
            width: window.innerWidth,
        };
    });
}

export async function login(page, findings, email, password = 'password') {
    await page.goto(BASE + '/login', { waitUntil: 'load' });
    await page.fill('input[type=email], input[name=email]', email);
    await page.fill('input[type=password]', password);
    await Promise.all([
        page.waitForURL(u => !u.pathname.startsWith('/login'), { timeout: 15000 }).catch(() => {}),
        page.click('button[type=submit]'),
    ]);
    await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
    const ok = !new URL(page.url()).pathname.startsWith('/login');
    if (!ok) findings.add('sign-in failed', email, 'still on the sign-in page');
    return ok;
}

export async function logout(page) {
    await page.context().clearCookies();
}

export function money(n) {
    return Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/** Text of the whole page with all spacing collapsed, for simple "does it say this" checks. */
export async function pageText(page) {
    return (await page.evaluate(() => document.body.innerText)).replace(/\s+/g, ' ');
}

export function report(findings, title) {
    const bySig = new Map();
    for (const f of findings.items) {
        const k = `${f.kind}|${f.where}|${f.detail}`;
        bySig.set(k, (bySig.get(k) || 0) + 1);
    }
    console.log(`\n=== ${title}: ${findings.passed}/${findings.checks} answer checks passed, ${bySig.size} problem(s) ===`);
    for (const [k, n] of bySig) {
        const [kind, where, detail] = k.split('|');
        console.log(`  [${kind}] ${where}${detail ? ' — ' + detail : ''}${n > 1 ? ` (x${n})` : ''}`);
    }
    const slow = [...findings.timings].sort((a, b) => b.ms - a.ms).slice(0, 5);
    if (slow.length) console.log('  slowest pages: ' + slow.map(s => `${s.url} ${s.ms}ms`).join(', '));
    mkdirSync(path.join(WORK, 'out'), { recursive: true });
    writeFileSync(path.join(WORK, 'out', `${title.replace(/\W+/g, '-')}.json`), JSON.stringify(findings, null, 2));
    return bySig.size;
}

/** The input or box that follows a label with this text (forms here do not tie labels to inputs). */
export function field(scope, label) {
    return scope.locator('label', { hasText: label }).first().locator('xpath=following::*[self::input or self::textarea][1]');
}
export async function type(scope, label, value) { await field(scope, label).fill(String(value)); }

/** Pick an option in the drop-down that follows a label. */
export async function choose(page, scope, label, option) {
    try {
        await scope.locator('label', { hasText: label }).first().locator('xpath=following::button[1]').click({ timeout: 8000 });
        await page.getByRole('option', { name: option, exact: false }).first().click({ timeout: 8000 });
    } catch (e) {
        mkdirSync(path.join(WORK, 'out'), { recursive: true });
        await page.screenshot({ path: path.join(WORK, 'out', 'choose-failed.png') }).catch(() => {});
        throw new Error(`could not pick "${option}" for "${label}": ${e.message.split('\n')[0]}`);
    }
}

export const dialog = page => page.locator('[role=dialog]').last();

/** Wait until the page has settled after a click that sends the form. */
export async function settle(page, ms = 600) {
    await page.waitForLoadState('networkidle', { timeout: 10000 }).catch(() => {});
    await page.waitForTimeout(ms);
}

/** Enter the public demo school as one kind of person (these accounts have no password; they are read-only). */
export async function demoLogin(page, findings, label) {
    await page.goto(BASE + '/demo', { waitUntil: 'load' });
    await page.getByRole('button', { name: new RegExp('^' + label, 'i') }).first().click();
    await page.waitForURL(u => !u.pathname.startsWith('/demo'), { timeout: 15000 }).catch(() => {});
    await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
    const ok = !new URL(page.url()).pathname.startsWith('/demo');
    if (!ok) findings.add('demo sign-in failed', label, 'still on the demo page');
    return ok;
}
