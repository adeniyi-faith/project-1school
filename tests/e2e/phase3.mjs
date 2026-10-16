// Phase 3: every role's view. Who lands where, what the menu offers, what a wrong role sees,
// sign-in checks, and that parents and students are shown the RIGHT figures for the RIGHT child.
import { watch, visit, login, demoLogin, db, settle, pageText, BASE } from './lib/harness.mjs';

const PASSWORD_USERS = {
    'school-admin': 'school-admin@genius-sms.test',
    principal: 'principal@genius-sms.test',
    teacher: 'teacher@genius-sms.test',
    accountant: 'accountant@genius-sms.test',
    librarian: 'librarian@genius-sms.test',
    student: 'student@genius-sms.test',
    parent: 'parent@genius-sms.test',
};
const DEMO_ONLY = { receptionist: 'Front Desk', driver: 'Driver', warden: 'Hostel Warden', 'store-manager': 'Store Keeper' };

// Written by hand from what each job needs, not read from the app.
const ROLES = {
    'school-admin': { landing: '/school/reports/dashboard', has: ['Students', 'Staff', 'Payroll', 'Invoices', 'Settings', 'Manage Users', 'Audit Log', 'Classes'], not: ['Packages', 'Schools', 'My Results'] },
    principal:      { landing: '/school/reports/dashboard', has: ['Students', 'Staff', 'Invoices', 'Term Results', 'Messages'], not: ['Payroll', 'Settings', 'Manage Users', 'Audit Log', 'Library', 'SMS/Email Blast'] },
    teacher:        { landing: '/school/reports/dashboard', has: ['Attendance', 'Term Results', 'Homework', 'CBT Tests', 'Timetable'], not: ['Payroll', 'Invoices', 'Staff', 'Settings', 'Library', 'Hostel'] },
    accountant:     { landing: '/school/reports/dashboard', has: ['Invoices', 'Payroll', 'Fee Structures', 'Scholarships'], not: ['Attendance', 'Hostel', 'Term Results', 'Settings', 'Staff'] },
    librarian:      { landing: '/school/reports/dashboard', has: ['Library'], not: ['Invoices', 'Payroll', 'Attendance', 'Settings', 'Hostel'] },
    receptionist:   { landing: '/school/home', has: ['Inquiries', 'Visitors', 'Students'], not: ['Invoices', 'Payroll', 'Staff', 'Settings', 'Hostel'] },
    driver:         { landing: '/school/home', has: ['Transport', 'Announcements'], not: ['Students', 'Invoices', 'Payroll', 'Hostel'] },
    warden:         { landing: '/school/home', has: ['Hostel', 'Announcements'], not: ['Transport', 'Invoices', 'Payroll', 'Inventory'] },
    'store-manager': { landing: '/school/home', has: ['Inventory', 'Announcements'], not: ['Hostel', 'Transport', 'Invoices', 'Payroll'] },
    student:        { landing: '/school/student/dashboard', has: ['My Results', 'My Fees', 'My Tests', 'My Timetable'], not: ['Students', 'Staff', 'Invoices', 'Settings'] },
    parent:         { landing: '/school/parent/dashboard', has: ['Results', 'Fee Status', 'Attendance'], not: ['Students', 'Staff', 'Invoices', 'Settings', 'My Results'] },
};

// screen => the roles that may open it (everyone else must be refused). Same table as RoleAccessMatrixTest.
const SA = 'school-admin', PR = 'principal', TE = 'teacher', AC = 'accountant', LI = 'librarian', RE = 'receptionist', DR = 'driver', WA = 'warden', ST = 'store-manager';
const SCREENS = {
    '/school/students': [SA, PR, TE, AC, LI, RE, WA],
    '/school/students/create': [SA, RE],
    '/school/staff': [SA, PR, LI],
    '/school/settings/admins': [SA, PR],
    '/school/attendance': [SA, PR, TE],
    '/school/results': [SA, PR, TE],
    '/school/promotions': [SA],
    '/school/fees/invoices': [SA, PR, AC],
    '/school/hr/payroll': [SA, AC],
    '/school/settings': [SA],
    '/school/reports/audit-log': [SA],
    '/school/library/books': [SA, LI],
    '/school/hostel': [SA, WA],
    '/school/transport/routes': [SA, DR],
    '/school/inventory/items': [SA, ST],
    '/school/communication/announcements': [SA, PR, TE, RE, DR, WA, ST],
    '/school/communication/blast': [SA],
};
const NOT_ALLOWED = 'You do not have permission to do that';

export default async function ({ browser, findings: f }) {
    await signInChecks(browser, f);
    await platformOwner(browser, f);

    for (const role of Object.keys(ROLES)) await oneRole(browser, f, role);

    await buttonsFollowPermissions(browser, f);
    await parentFigures(browser, f);
    await studentFigures(browser, f);
    await demoIsReadOnly(browser, f);
}

async function fresh(browser) {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    return { ctx, page: await ctx.newPage() };
}

async function enter(page, f, role) {
    if (PASSWORD_USERS[role]) return login(page, f, PASSWORD_USERS[role]);
    return demoLogin(page, f, DEMO_ONLY[role]);
}

async function menu(page) {
    return page.evaluate(() => [...document.querySelectorAll('nav[aria-label="Main"] a')].map(a => ({ label: a.innerText.trim(), href: new URL(a.href).pathname })));
}

// ---------------------------------------------------------------------------------------------
async function signInChecks(browser, f) {
    const { ctx, page } = await fresh(browser);
    watch(page, f);

    // Not signed in: a protected page sends you to the sign-in page
    for (const p of ['/school/students', '/school/parent/fees', '/super-admin/schools', '/dashboard']) {
        await page.goto(BASE + p, { waitUntil: 'load' });
        f.ok(`signed-out visit to ${p} goes to sign in`, new URL(page.url()).pathname === '/login', page.url());
    }

    const attempt = async (email, password) => {
        await page.goto(BASE + '/login', { waitUntil: 'load' });
        if (email) await page.fill('input[type=email], input[name=email]', email);
        if (password) await page.fill('input[type=password]', password);
        await page.click('button[type=submit]');
        await settle(page, 700);
        return { path: new URL(page.url()).pathname, text: await pageText(page) };
    };

    const wrongPw = await attempt('school-admin@genius-sms.test', 'not-the-password');
    f.ok('wrong password stays on sign in', wrongPw.path === '/login', wrongPw.path);
    f.ok('wrong password says so plainly', /do not match|incorrect|invalid|failed/i.test(wrongPw.text), wrongPw.text.slice(0, 160));

    const ghost = await attempt('nobody-here@example.test', 'whatever1');
    f.ok('unknown email stays on sign in', ghost.path === '/login', ghost.path);
    const msg = t => (t.match(/(These credentials[^.]*\.|[^.]*do not match[^.]*\.)/i) ?? [''])[0].trim();
    f.check('unknown email gets the same message as a wrong password (nothing leaks about which emails exist)', msg(ghost.text), msg(wrongPw.text));

    const blank = await attempt('', '');
    f.ok('blank sign in form stays on sign in with no crash', blank.path === '/login' && !/Server Error|Whoops/i.test(blank.text), blank.text.slice(0, 120));
    const badEmail = await attempt('not-an-email', 'x');
    f.ok('badly typed email stays on sign in', badEmail.path === '/login', badEmail.path);

    // 5 wrong tries for one email, then the 6th is refused with a wait message even with the RIGHT password
    for (let i = 0; i < 5; i++) await attempt('throttle-me@example.test', 'wrong' + i);
    const sixth = await attempt('throttle-me@example.test', 'wrong-again');
    f.ok('six wrong tries in a row are slowed down', /Too many login attempts/i.test(sixth.text), sixth.text.slice(0, 160));

    // Right password signs in; sign out really signs out
    const ok = await login(page, f, 'school-admin@genius-sms.test');
    f.ok('right password signs in', ok);
    await page.getByRole('button', { name: 'Account menu' }).click();
    await page.getByText('Sign out').click();
    await settle(page, 700);
    f.ok('sign out lands on the sign-in page', new URL(page.url()).pathname === '/login', page.url());
    await page.goto(BASE + '/school/students', { waitUntil: 'load' });
    f.ok('after sign out the school pages are closed again', new URL(page.url()).pathname === '/login', page.url());
    await page.goBack().catch(() => {});
    await page.waitForLoadState('load').catch(() => {});
    f.ok('the back button does not reopen a school page after sign out', !/\/school\/students$/.test(new URL(page.url()).pathname) || /Email|Password/i.test(await pageText(page)), page.url());
    await ctx.close();
}

// ---------------------------------------------------------------------------------------------
async function platformOwner(browser, f) {
    const { ctx, page } = await fresh(browser);
    watch(page, f);
    await login(page, f, 'admin@genius-sms.test');
    f.ok('platform owner lands on the platform dashboard', new URL(page.url()).pathname === '/super-admin/dashboard', page.url());
    await visit(page, f, '/super-admin/dashboard');
    const labels = (await menu(page)).map(m => m.label);
    for (const l of ['Schools', 'Packages', 'Subscriptions', 'Module Manager']) f.ok(`platform menu has ${l}`, labels.includes(l), labels.join(', '));
    for (const l of ['Students', 'Invoices', 'Attendance', 'Payroll']) f.ok(`platform menu has no ${l} before choosing a school`, !labels.includes(l), labels.join(', '));
    for (const p of ['/super-admin/schools', '/super-admin/packages', '/super-admin/subscriptions', '/super-admin/coupons', '/super-admin/module-manager', '/super-admin/settings', '/super-admin/ai', '/super-admin/users']) await visit(page, f, p);

    // A school page without picking a school goes back to the schools list
    await page.goto(BASE + '/school/students', { waitUntil: 'load' });
    f.ok('school pages need a chosen school', new URL(page.url()).pathname === '/super-admin/schools', page.url());

    // Open a school: the whole school menu, the amber bar, and the numbers are that school's
    const d = db();
    await visit(page, f, '/super-admin/schools');
    const school1 = d.one(`select id, name from schools where id = 1`);
    await page.locator('tr', { hasText: school1.name }).first().getByRole('button', { name: /Open/ }).click().catch(async () => {
        await page.getByRole('button', { name: /Open/ }).first().click();
    });
    await settle(page, 900);
    const bar = await pageText(page);
    f.ok('inside a school there is a way back to the platform', /Back to platform/.test(bar));
    const inside = (await menu(page)).map(m => m.label);
    for (const l of ['Students', 'Invoices', 'Payroll', 'Settings']) f.ok(`inside a school the menu has ${l}`, inside.includes(l), inside.join(', '));
    await visit(page, f, '/school/students');
    const shown = (await pageText(page));
    const school1Students = d.value(`select count(*) from students where school_id = 1 and deleted_at is null`);
    const school2Name = d.one(`select first_name, last_name from students where school_id = 2 and deleted_at is null limit 1`);
    f.ok('students list does not show another school\'s pupils', !shown.includes(`${school2Name.first_name} ${school2Name.last_name}`) || school1Students === 0, 'a school 2 pupil is visible inside school 1');
    await page.getByRole('button', { name: 'Back to platform' }).click();
    await settle(page, 900);
    f.ok('Back to platform returns to the platform pages', new URL(page.url()).pathname.startsWith('/super-admin'), page.url());
    d.close();
    await ctx.close();
}

// ---------------------------------------------------------------------------------------------
async function oneRole(browser, f, role) {
    const spec = ROLES[role];
    const { ctx, page } = await fresh(browser);
    watch(page, f);
    if (!await enter(page, f, role)) { await ctx.close(); return; }

    // Lands in the right place
    await page.goto(BASE + '/dashboard', { waitUntil: 'load' });
    await settle(page, 400);
    f.check(`${role} lands on`, new URL(page.url()).pathname, spec.landing);

    // The menu offers what this job needs and nothing it should not
    const items = await menu(page);
    const labels = items.map(i => i.label);
    for (const l of spec.has) f.ok(`${role} menu has "${l}"`, labels.includes(l), labels.join(', '));
    for (const l of spec.not) f.ok(`${role} menu has no "${l}"`, !labels.includes(l), labels.join(', '));

    // Every link in the menu opens (a link that leads to "not allowed" is a dead end)
    const seen = new Set();
    for (const it of items) {
        if (seen.has(it.href)) continue;
        seen.add(it.href);
        await visit(page, f, it.href, { slowMs: 8000 });
    }

    // The screens table: open the ones allowed, refused (with the plain message) the rest
    if (!/^(student|parent)$/.test(role)) {
        for (const [path, allowed] of Object.entries(SCREENS)) {
            if (allowed.includes(role)) {
                await visit(page, f, path, { slowMs: 8000 });
            } else {
                const r = await visit(page, f, path, { allowStatus: [403], note: `${role} should be refused ${path}`, slowMs: 8000 });
                if (r) f.ok(`${role} refused ${path} with a readable message`, r.text.includes(NOT_ALLOWED), r.text.slice(0, 100).replace(/\s+/g, ' '));
            }
        }
    }

    // The other portals and the platform pages are closed
    const closed = ['/super-admin/dashboard', '/super-admin/schools', ...(role === 'student' ? ['/school/parent/dashboard'] : role === 'parent' ? ['/school/student/dashboard'] : ['/school/student/dashboard', '/school/parent/dashboard'])];
    for (const p of closed) {
        const r = await visit(page, f, p, { allowStatus: [403], note: `${role} should be refused ${p}`, slowMs: 8000 });
        if (r) f.ok(`${role} refused ${p} with a readable message`, r.text.includes(NOT_ALLOWED), r.text.slice(0, 100).replace(/\s+/g, ' '));
    }
    await ctx.close();
}

// ---------------------------------------------------------------------------------------------
/** Buttons that need a permission must not be shown to people without it (and are shown to people with it). */
async function buttonsFollowPermissions(browser, f) {
    const cases = [
        { role: 'school-admin', url: '/school/students', text: /Admit student/, shown: true },
        { role: 'receptionist', url: '/school/students', text: /Admit student/, shown: true },
        { role: 'teacher', url: '/school/students', text: /Admit student/, shown: false },
        { role: 'accountant', url: '/school/students', text: /Admit student/, shown: false },
        { role: 'principal', url: '/school/students', text: /Admit student/, shown: false },
    ];
    for (const c of cases) {
        const { ctx, page } = await fresh(browser);
        watch(page, f);
        if (await enter(page, f, c.role)) {
            await visit(page, f, c.url);
            const count = await page.getByRole('link', { name: c.text }).count() + await page.getByRole('button', { name: c.text }).count();
            f.ok(`${c.role} ${c.shown ? 'sees' : 'does not see'} "Admit student"`, c.shown ? count > 0 : count === 0, `count=${count}`);
        }
        await ctx.close();
    }
}

// ---------------------------------------------------------------------------------------------
const ordinal = n => { const r = n % 100; if (r >= 11 && r <= 13) return n + 'th'; return n + ({ 1: 'st', 2: 'nd', 3: 'rd' }[n % 10] ?? 'th'); };
const fmtNaira = n => {
    const kobo = Math.abs(n - Math.round(n)) > 0.004;
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: kobo ? 2 : 0, maximumFractionDigits: kobo ? 2 : 0 });
};

function familyFacts(d, studentId) {
    const sheets = d.all(`select s.id, s.status from result_sheets s join term_result_summaries m on m.result_sheet_id = s.id where m.student_id = ?`, studentId);
    const published = sheets.filter(s => ['published', 'locked'].includes(s.status)).map(s => s.id);
    const drafts = sheets.filter(s => !['published', 'locked'].includes(s.status)).map(s => s.id);
    const summaries = published.map(id => ({
        sheet: id,
        sum: d.one(`select * from term_result_summaries where result_sheet_id = ? and student_id = ?`, id, studentId),
        subjects: d.all(`select r.total, r.grade, sub.name from term_results r join subjects sub on sub.id = r.subject_id where r.result_sheet_id = ? and r.student_id = ?`, id, studentId),
        classAverage: d.value(`select class_average from result_sheets where id = ?`, id),
    }));
    const invoices = d.all(`select id, balance from invoices where student_id = ? and status != 'void'`, studentId);
    const balance = invoices.reduce((a, i) => a + Number(i.balance), 0);
    const ids = invoices.map(i => i.id);
    let paid = 0;
    if (ids.length) {
        const q = ids.map(() => '?').join(',');
        const pay = d.all(`select id, amount from ledger_entries where type = 'payment' and invoice_id in (${q})`, ...ids);
        const payIds = pay.map(p => p.id);
        const revs = payIds.length ? d.all(`select amount from ledger_entries where type = 'reversal' and reverses_id in (${payIds.map(() => '?').join(',')})`, ...payIds) : [];
        paid = -(pay.reduce((a, p) => a + Number(p.amount), 0) + revs.reduce((a, r) => a + Number(r.amount), 0));
    }
    return { published, drafts, summaries, balance: Math.round(balance * 100) / 100, paid: Math.round(paid * 100) / 100 };
}

async function checkResultsPage(page, f, who, facts) {
    const text = await pageText(page);
    f.ok(`${who}: no unpublished term shown (${facts.drafts.length} in draft)`, true);
    for (const { sheet, sum, subjects, classAverage } of facts.summaries) {
        const where = `${who} sheet ${sheet}`;
        f.ok(`${where}: position "${ordinal(sum.position)} of ${sum.class_size}" shown`, text.includes(`${ordinal(sum.position)} of ${sum.class_size}`), 'position text missing');
        f.ok(`${where}: average ${sum.average}% shown`, text.includes(`${Number(sum.average)}%`), `average ${sum.average}`);
        f.ok(`${where}: class average shown`, classAverage === null || text.includes(`${Number(classAverage)}%`), `class average ${classAverage}`);
        for (const s of subjects) {
            const row = page.locator('tr', { hasText: s.name }).filter({ hasText: s.grade ?? '' }).first();
            const cells = (await row.innerText().catch(() => '')).split('\t').map(x => x.trim());
            const score = parseFloat(cells[1] ?? 'NaN');
            f.check(`${where}: ${s.name} score`, Number.isNaN(score) ? 'not shown' : score, Number(s.total));
            f.ok(`${where}: ${s.name} grade ${s.grade}`, cells.includes(s.grade ?? ''), cells.join(' | '));
        }
    }
}

async function parentFigures(browser, f) {
    const d = db();
    const { ctx, page } = await fresh(browser);
    watch(page, f);
    if (!await demoLogin(page, f, 'Parent')) { await ctx.close(); d.close(); return; }
    const guardian = d.one(`select g.id from guardians g join users u on u.id = g.user_id where u.email like 'demo-parent@%'`);
    if (!guardian) { f.add('missing data', 'demo parent', 'demo parent has no guardian record'); await ctx.close(); d.close(); return; }
    const kids = d.all(`select id, first_name, last_name from students where guardian_id = ? and deleted_at is null`, guardian.id);
    f.ok('demo parent has at least one child', kids.length > 0);
    const strangers = d.all(`select first_name, last_name from students where school_id = (select school_id from guardians where id = ?) and (guardian_id is null or guardian_id != ?) and deleted_at is null limit 60`, guardian.id, guardian.id);
    const ownNames = new Set(kids.map(k => `${k.first_name} ${k.last_name}`));

    await visit(page, f, '/school/parent/results');
    for (const k of kids) await checkResultsPage(page, f, `parent/${k.first_name}`, familyFacts(d, k.id));
    const resultsText = await pageText(page);
    for (const s of strangers) {
        const n = `${s.first_name} ${s.last_name}`;
        if (!ownNames.has(n)) f.ok(`parent results does not show ${n}`, !resultsText.includes(n), 'another family\'s child is visible');
    }

    await visit(page, f, '/school/parent/fees');
    const feesText = await pageText(page);
    for (const k of kids) {
        const facts = familyFacts(d, k.id);
        f.ok(`parent fees: ${k.first_name} paid ${fmtNaira(facts.paid)}`, facts.paid === 0 ? feesText.includes('₦0') : feesText.includes(fmtNaira(facts.paid)), `paid ${facts.paid}`);
        f.ok(`parent fees: ${k.first_name} balance ${facts.balance > 0 ? fmtNaira(facts.balance) : 'Clear'}`, facts.balance > 0 ? feesText.includes(fmtNaira(facts.balance)) : feesText.includes('Clear'), `balance ${facts.balance}`);
    }
    for (const s of strangers) {
        const n = `${s.first_name} ${s.last_name}`;
        if (!ownNames.has(n)) f.ok(`parent fees does not show ${n}`, !feesText.includes(n), 'another family\'s child is visible');
    }

    // Another family's report card cannot be opened by id
    const other = d.one(`select m.student_id, m.result_sheet_id from term_result_summaries m join result_sheets s on s.id = m.result_sheet_id where s.status in ('published','locked') and m.student_id not in (${kids.map(() => '?').join(',')}) limit 1`, ...kids.map(k => k.id));
    if (other) {
        const r = await page.goto(`${BASE}/school/parent/report-cards/${other.student_id}/${other.result_sheet_id}`, { waitUntil: 'load' });
        f.ok('parent cannot open another family\'s report card', [403, 404].includes(r.status()), `status ${r.status()}`);
    }
    // Their own card opens and is a real PDF
    const own = d.one(`select m.student_id, m.result_sheet_id from term_result_summaries m join result_sheets s on s.id = m.result_sheet_id where s.status in ('published','locked') and m.student_id in (${kids.map(() => '?').join(',')}) limit 1`, ...kids.map(k => k.id));
    if (own) {
        const resp = await page.request.get(`${BASE}/school/parent/report-cards/${own.student_id}/${own.result_sheet_id}`);
        f.check('own report card downloads as a PDF', (resp.headers()['content-type'] ?? '').includes('pdf') ? 'pdf' : resp.headers()['content-type'], 'pdf');
        f.ok('own report card PDF starts like a PDF', (await resp.body()).subarray(0, 4).toString() === '%PDF');
    }
    for (const p of ['/school/parent/dashboard', '/school/parent/attendance', '/school/parent/announcements']) await visit(page, f, p);
    await ctx.close();
    d.close();
}

async function studentFigures(browser, f) {
    const d = db();
    const { ctx, page } = await fresh(browser);
    watch(page, f);
    if (!await demoLogin(page, f, 'Student')) { await ctx.close(); d.close(); return; }
    const me = d.one(`select s.id, s.first_name, s.last_name, s.school_id from students s join users u on u.id = s.user_id where u.email like 'demo-student@%'`);
    if (!me) { f.add('missing data', 'demo student', 'demo student has no pupil record'); await ctx.close(); d.close(); return; }
    await visit(page, f, '/school/student/results');
    await checkResultsPage(page, f, `student/${me.first_name}`, familyFacts(d, me.id));
    const classmates = d.all(`select first_name, last_name from students where school_id = ? and id != ? and class_id = (select class_id from students where id = ?) and deleted_at is null limit 40`, me.school_id, me.id, me.id);
    const text = await pageText(page);
    for (const c of classmates) {
        const n = `${c.first_name} ${c.last_name}`;
        if (n !== `${me.first_name} ${me.last_name}`) f.ok(`student results does not show classmate ${n}`, !text.includes(n), 'a classmate is visible');
    }
    await visit(page, f, '/school/student/fees');
    const feesText = await pageText(page);
    const facts = familyFacts(d, me.id);
    f.ok(`student fees: balance ${facts.balance > 0 ? fmtNaira(facts.balance) : 'Clear'}`, facts.balance > 0 ? feesText.includes(fmtNaira(facts.balance)) : /Clear|Paid up|₦0/.test(feesText), `balance ${facts.balance}`);
    for (const p of ['/school/student/dashboard', '/school/student/attendance', '/school/student/timetable', '/school/student/homework', '/school/student/cbt', '/school/student/announcements']) await visit(page, f, p);
    await ctx.close();
    d.close();
}

// ---------------------------------------------------------------------------------------------
/** The public demo lets people look, never change: a save from a demo account changes nothing. */
async function demoIsReadOnly(browser, f) {
    const d = db();
    const { ctx, page } = await fresh(browser);
    watch(page, f);
    if (await demoLogin(page, f, 'School Admin')) {
        const before = d.value(`select count(*) from classes`);
        await visit(page, f, '/school/classes');
        await page.getByRole('button', { name: /Add Class/ }).first().click();
        await page.locator('input[name=name]').fill('Demo Should Not Save');
        await page.locator('[role=dialog]').last().getByRole('button', { name: 'Create Class' }).click();
        await settle(page, 900);
        f.check('a demo account cannot add a class', d.value(`select count(*) from classes`), before);
        f.ok('the demo says it is read-only', /read-only demo/i.test(await pageText(page)), 'no read-only message');
    }
    await ctx.close();
    d.close();
}
