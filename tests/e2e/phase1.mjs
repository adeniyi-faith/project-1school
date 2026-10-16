// Phase 1: money and people. Admissions, students, staff, fees.
// Each flow clicks through the screens like a person would, then checks the answer against
// numbers worked out by hand (written here) or read straight from the database rows.
import { watch, visit, login, overflow, db, choose, type, field, dialog, settle, pageText, money, BASE } from './lib/harness.mjs';

const ADMIN = 'school-admin@genius-sms.test'; // school 1
const TEACHER = 'teacher@genius-sms.test';
const ACCOUNTANT = 'accountant@genius-sms.test';
const PARENT = 'parent@genius-sms.test';
const naira = n => '₦' + money(n);

export default async function ({ browser, findings: f }) {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    watch(page, f);

    // ---- Sign-in: a wrong password is refused with a message, the right one gets in ----
    await page.goto(BASE + '/login');
    await page.fill('input[type=email], input[name=email]', ADMIN);
    await page.fill('input[type=password]', 'wrong-password');
    await page.click('button[type=submit]');
    await settle(page);
    f.ok('wrong password stays on sign-in', new URL(page.url()).pathname === '/login', page.url());
    f.ok('wrong password shows a message', /do not match|incorrect|failed|invalid/i.test(await pageText(page)), 'no message shown');
    await login(page, f, ADMIN);

    // ---- Every money and people screen opens cleanly ----
    for (const p of [
        '/school/students', '/school/students/create', '/school/staff', '/school/staff/create', '/school/departments', '/school/designations',
        '/school/admissions/inquiries', '/school/admissions/visitors', '/school/fees/categories', '/school/fees/structures', '/school/fees/invoices',
        '/school/fees/payments', '/school/fees/payment-plans', '/school/fees/scholarships', '/school/fees/online-payments', '/school/fees/outstanding',
        '/school/promotions', '/school/id-cards', '/school/certificates',
    ]) await visit(page, f, p);

    await admissionsChain(page, f);
    await admitStudentByForm(page, f);
    await registerStaff(page, f);
    await feesFlow(page, f);
    await permissions(browser, f);
    await parentSeesSameMoney(browser, f);
}

// ---------------------------------------------------------------------------------------------
async function admissionsChain(page, f) {
    const d = db();
    await visit(page, f, '/school/admissions/inquiries');
    await page.getByRole('button', { name: 'New Inquiry' }).click();
    const dlg = dialog(page);
    await type(dlg, 'Student Name', "Chidera O'Neil-Ọlá");
    await type(dlg, 'Class Interested', 'Class 2');
    await type(dlg, 'Guardian Name', 'Mrs Ngozi Okafor');
    await type(dlg, 'Guardian Phone', '08034445555');
    await dlg.getByRole('button', { name: 'Create' }).click();
    await settle(page);
    const inq = d.one("select * from admission_inquiries where guardian_phone='08034445555'");
    if (!f.ok('inquiry saved', inq, 'no inquiry row')) return;
    f.check('inquiry name saved exactly', inq.student_name, "Chidera O'Neil-Ọlá");

    // Enrolling before the place is offered must not be possible from the screen
    await visit(page, f, `/school/admissions/inquiries/${inq.id}`);
    f.ok('no Enrol button before an offer', (await page.getByRole('button', { name: /^Enrol/ }).count()) === 0, 'Enrol is visible too early');

    // exam booked and marked: 68 out of 80
    await page.getByRole('button', { name: 'Exam' }).first().click();
    await type(dialog(page), 'Where', 'Main hall');
    await dialog(page).getByRole('button', { name: 'Save' }).click();
    await settle(page);
    f.check('exam booked', d.value(`select count(*) from admission_assessments where inquiry_id=?`, inq.id), 1);

    await page.getByRole('button', { name: /Record result/ }).first().click();
    await choose(page, dialog(page), 'Result', 'Passed');
    await type(dialog(page), 'Score', '68');
    await type(dialog(page), 'Out of', '80');
    await dialog(page).getByRole('button', { name: 'Save' }).click();
    await settle(page);
    const a = d.one(`select * from admission_assessments where inquiry_id=?`, inq.id);
    f.check('exam outcome saved', a?.outcome, 'passed');
    f.check('exam score saved', Number(a?.score), 68);
    f.check('exam total saved', Number(a?.max_score), 80);

    // a score above the total is refused
    await page.getByRole('button', { name: /Edit/ }).first().click();
    await type(dialog(page), 'Score', '95');
    await dialog(page).getByRole('button', { name: 'Save' }).click();
    await settle(page);
    f.check('score above total refused', Number(d.one(`select score from admission_assessments where inquiry_id=?`, inq.id).score), 68);
    await page.keyboard.press('Escape');
    await visit(page, f, `/school/admissions/inquiries/${inq.id}`);

    // offer a place
    await page.getByRole('button', { name: 'Offer a place' }).click();
    await dialog(page).getByRole('button', { name: /Offer/ }).last().click();
    await settle(page);
    f.check('place offered', d.value('select status from admission_inquiries where id=?', inq.id), 'accepted');

    // enrol
    await page.getByRole('button', { name: /^Enrol/ }).first().click();
    const en = dialog(page);
    await choose(page, en, 'Gender', 'Female');
    await choose(page, en, 'Class', 'Class 2');
    await type(en, 'Date of birth', '2014-03-09');
    await type(en, "child's", 'Mother');
    await en.getByRole('button', { name: 'Enrol' }).last().click();
    await settle(page, 1200);
    const st = d.one(`select * from students where first_name like 'Chidera%' order by id desc limit 1`);
    if (f.ok('student created by enrolment', st, 'no student row')) {
        f.check('enrolled student status', st.status, 'active');
        f.check('enrolled student class', d.value('select name from classes where id=?', st.class_id), 'Class 2');
        f.ok('student has an admission number', /^ADM-\d{4}-\d{4}$/.test(st.admission_no ?? ''), st.admission_no);
        f.check('parent phone carried over', d.value('select phone from guardians where id=?', st.guardian_id), '08034445555');
        f.check('parent name carried over', d.value('select name from guardians where id=?', st.guardian_id), 'Mrs Ngozi Okafor');
        f.check('inquiry marked admitted', d.value('select status from admission_inquiries where id=?', inq.id), 'admitted');
        f.ok('lands on the new student page', page.url().includes(`/school/students/${st.id}`), page.url());
        f.ok('student page shows the admission number', (await pageText(page)).includes(st.admission_no), '');
    }
    await visit(page, f, `/school/admissions/inquiries/${inq.id}`);
    f.ok('no Enrol button after enrolment', (await page.getByRole('button', { name: /^Enrol/ }).count()) === 0, 'could enrol twice');
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function admitStudentByForm(page, f) {
    const d = db();
    const before = d.value('select count(*) from students where school_id=1');
    await visit(page, f, '/school/students/create');
    // Step 1 with required boxes empty must not move on silently
    await page.getByRole('button', { name: 'Next' }).click();
    await settle(page, 300);
    f.ok('empty first step shows errors', /required|enter|first name/i.test(await pageText(page)), 'no error text');
    await type(page, 'First Name', 'Ọlámidé');
    await type(page, 'Last Name', "D'Souza <b>x</b>");
    await choose(page, page, 'Gender', 'Male');
    await page.getByRole('button', { name: 'Next' }).click();
    await settle(page, 300);
    await choose(page, page, 'Class', 'Class 3');
    await page.getByRole('button', { name: 'Next' }).click();
    await settle(page, 300);
    await type(page, 'Guardian Name', 'Mr Femi D\'Souza');
    await choose(page, page, 'Relation', 'Father');
    await page.getByRole('button', { name: /Admit|Save|Submit|Create/ }).last().click();
    await settle(page, 1200);
    const after = d.value('select count(*) from students where school_id=1');
    f.check('one student added', after - before, 1);
    const s = d.one(`select * from students where first_name='Ọlámidé'`);
    if (f.ok('student saved with accents', s, 'not found')) {
        f.check('surname saved exactly (apostrophe, markup kept as text)', s.last_name, "D'Souza <b>x</b>");
        f.check('class saved', d.value('select name from classes where id=?', s.class_id), 'Class 3');
        f.check('gender saved', s.gender, 'male');
    }
    await visit(page, f, '/school/students?search=' + encodeURIComponent('Ọlámidé'));
    const t = await pageText(page);
    f.ok('search finds the new student', t.includes('Ọlámidé'), '');
    f.ok('markup in a name is shown as text, not run', t.includes('<b>x</b>') || !(await page.locator('main b', { hasText: 'x' }).count()), 'name was treated as HTML');
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function registerStaff(page, f) {
    const d = db();
    const before = d.value('select count(*) from staff where school_id=1');
    await visit(page, f, '/school/staff/create');
    await type(page, 'First Name', 'Adaeze');
    await type(page, 'Last Name', 'Nwosu-Eze');
    await choose(page, page, 'Gender', 'Female');
    await page.getByRole('button', { name: 'Next' }).click();
    await settle(page, 300);
    // salary box: label contains Salary
    const salary = field(page, 'Salary (₦)');
    await salary.fill('185000.50');
    await page.getByRole('button', { name: 'Next' }).click();
    await settle(page, 300);
    await page.getByRole('button', { name: /Register|Save|Create|Submit/ }).last().click();
    await settle(page, 1200);
    f.check('one staff member added', d.value('select count(*) from staff where school_id=1') - before, 1);
    const s = d.one(`select * from staff where first_name='Adaeze' and last_name='Nwosu-Eze' order by id desc limit 1`);
    if (s) {
        f.check('staff salary saved to the kobo', Number(s.salary), 185000.5);
        f.ok('staff number looks right', /^EMP-\d{4}-\d{4}$/.test(s.emp_id ?? ''), s.emp_id);
        await visit(page, f, `/school/staff/${s.id}`);
        await page.getByRole('tab', { name: 'Employment' }).click().catch(() => page.getByText('Employment', { exact: true }).first().click());
        await settle(page, 300);
        const t = await pageText(page);
        f.ok('staff page shows salary in naira', t.includes('₦185,000.5') || t.includes('₦185,000'), t.match(/salary[^A-Za-z]{0,30}/i)?.[0] ?? 'salary not shown');
        f.ok('staff page does not show BDT', !t.includes('BDT'), 'wrong currency');
    }
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function feesFlow(page, f) {
    const d = db();
    // ---- Bill a whole class from the screen: right number of invoices, right amounts, no double billing ----
    const fee = d.one(`select fs.*, (select count(*) from students s where s.class_id=fs.class_id and s.status='active' and s.deleted_at is null) as pupils,
        (select name from classes c where c.id=fs.class_id) as class_name
        from fee_structures fs where fs.school_id=1 and fs.is_active=1 and fs.deleted_at is null order by fs.id limit 1`);
    const amountOf = Number(fee.amount);
    const maxBefore = Number(d.value('select coalesce(max(id),0) from invoices'));
    const beforeCount = d.value('select count(*) from invoices where fee_structure_id=?', fee.id);
    const beforeSum = Number(d.value('select coalesce(round(sum(amount),2),0) from invoices where fee_structure_id=?', fee.id));
    await visit(page, f, '/school/fees/invoices');
    await page.getByRole('button', { name: /Create invoices/ }).first().click();
    const dl = dialog(page);
    await choose(page, dl, 'Term', 'First Term');
    await dl.locator(`xpath=.//p[normalize-space()='${fee.class_name}']/following-sibling::label[1]`).click();
    await dl.getByRole('button', { name: 'Create invoices' }).last().click();
    await settle(page, 1500);
    const made = () => d.value('select count(*) from invoices where fee_structure_id=?', fee.id);
    const strictBefore = beforeCount;
    f.check(`class "${fee.class_name}" billed: one invoice per active pupil (${fee.pupils})`, made() - strictBefore, Number(fee.pupils));
    f.check('every new invoice has the exact fee amount', Number(d.value('select count(*) from invoices where id>? and abs(amount-?)>0.001', maxBefore, amountOf)), 0);
    f.check('total billed = pupils x fee', Number(d.value('select round(sum(amount),2) from invoices where fee_structure_id=?', fee.id)) - beforeSum, Math.round(fee.pupils * amountOf * 100) / 100);
    // run it again: nothing new
    await page.getByRole('button', { name: /Create invoices/ }).first().click();
    await choose(page, dialog(page), 'Term', 'First Term');
    await dialog(page).locator(`xpath=.//p[normalize-space()='${fee.class_name}']/following-sibling::label[1]`).click();
    await dialog(page).getByRole('button', { name: 'Create invoices' }).last().click();
    await settle(page, 1500);
    f.check('billing twice does not double charge', made() - beforeCount, Number(fee.pupils));
    f.ok('screen says nothing new was made', /No new invoices/i.test(await pageText(page)), '');

    const inv = d.one(`select i.* from invoices i where i.id>? order by i.id limit 1`, maxBefore);
    if (!f.ok('a fresh invoice exists to test payments on', inv, 'none found')) return;
    const amount = Number(inv.amount);
    await visit(page, f, `/school/fees/invoices/${inv.id}`);
    f.ok('invoice page shows the full fee as still owed', (await pageText(page)).includes(naira(amount)), naira(amount));

    // Part payment of 1,000.55 -> owed must read amount - 1,000.55 (to the kobo)
    const pay = async (value) => {
        await page.getByRole('button', { name: 'Record payment' }).first().click();
        const dl = dialog(page);
        await dl.locator('#pay-amount').fill(String(value));
        await dl.getByRole('button', { name: 'Record payment' }).last().click();
        await settle(page, 900);
    };
    await pay(1000.55);
    let owed = Math.round((amount - 1000.55) * 100) / 100;
    f.check('balance in database after part payment', Number(d.value('select balance from invoices where id=?', inv.id)), owed);
    f.check('status after part payment', d.value('select status from invoices where id=?', inv.id), 'partial');
    f.ok('screen shows the kobo balance', (await pageText(page)).includes(naira(owed)), `expected ${naira(owed)}`);

    // Paying more than is owed is refused with a message and writes nothing
    const before = d.value('select count(*) from ledger_entries where invoice_id=?', inv.id);
    await pay(owed + 0.01);
    f.check('overpayment writes nothing', d.value('select count(*) from ledger_entries where invoice_id=?', inv.id), before);
    const blockedByBrowser = await page.locator('#pay-amount').evaluate(el => el.validity.rangeOverflow).catch(() => false);
    f.ok('overpayment is stopped with a message (by the box or the server)', blockedByBrowser || /more than/i.test(await pageText(page)), 'nothing told the person');
    await page.keyboard.press('Escape');

    // Pay the exact rest: invoice is settled and the pay button goes away
    await visit(page, f, `/school/fees/invoices/${inv.id}`);
    await pay(owed);
    f.check('status when settled', d.value('select status from invoices where id=?', inv.id), 'paid');
    f.check('balance when settled', Number(d.value('select balance from invoices where id=?', inv.id)), 0);
    f.ok('no pay button on a settled invoice', (await page.getByRole('button', { name: 'Record payment' }).count()) === 0, '');

    // The ledger adds up: charge - payments = 0
    f.check('ledger lines add up to zero', Number(d.value('select round(sum(amount),2) from ledger_entries where invoice_id=?', inv.id)), 0);

    // Reverse the first payment: owed goes back to 1,000.55
    await page.getByRole('button', { name: /Reverse/ }).first().click();
    await dialog(page).locator('textarea').fill('Cheque bounced');
    await dialog(page).getByRole('button', { name: /Reverse/ }).last().click();
    await settle(page, 900);
    f.check('balance after reversing a payment', Number(d.value('select balance from invoices where id=?', inv.id)), 1000.55);
    f.check('status after reversing a payment', d.value('select status from invoices where id=?', inv.id), 'partial');

    // The fee line itself cannot be reversed from the screen
    const reverseButtons = await page.getByRole('button', { name: /Reverse/ }).count();
    const chargeRow = page.locator('tr', { hasText: 'charge' }).first();
    f.ok('fee line has no Reverse button', (await chargeRow.getByRole('button', { name: /Reverse/ }).count()) === 0, `${reverseButtons} reverse buttons`);

    // Invoices list totals equal what the database says is owed
    await visit(page, f, '/school/fees/invoices');
    const owedAll = Number(d.value(`select round(sum(balance),2) from invoices where school_id=1 and status in ('unpaid','partial')`));
    const t = await pageText(page);
    f.ok('invoices page total owed matches the ledger', t.includes(naira(owedAll)) || t.includes('₦' + Math.round(owedAll).toLocaleString('en-NG')) , `expected ${naira(owedAll)}`);
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function permissions(browser, f) {
    const d = db();
    const inv = d.one(`select id from invoices where school_id=1 and status in ('unpaid','partial') limit 1`);
    for (const [who, canSeeFees] of [[TEACHER, false], [ACCOUNTANT, true]]) {
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
        const page = await ctx.newPage();
        watch(page, f);
        await login(page, f, who);
        const r = await visit(page, f, `/school/fees/invoices/${inv.id}`, { allowStatus: canSeeFees ? [200] : [403] });
        if (!canSeeFees) {
            f.ok(`${who}: plain not-allowed page`, /do not have permission/i.test(r?.text ?? ''), (r?.text ?? '').slice(0, 80));
            const s = await visit(page, f, '/school/staff/create', { allowStatus: [403] });
            f.ok(`${who}: cannot open staff registration`, /do not have permission/i.test(s?.text ?? ''), '');
        } else {
            f.ok(`${who}: can record payments`, (await page.getByRole('button', { name: 'Record payment' }).count()) > 0, 'no pay button');
            f.ok(`${who}: cannot cancel invoices`, (await page.getByRole('button', { name: 'Cancel invoice' }).count()) === 0, 'cancel button shown');
        }
        await ctx.close();
    }
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function parentSeesSameMoney(browser, f) {
    const d = db();
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    watch(page, f);
    await login(page, f, PARENT);
    const r = await visit(page, f, '/school/parent/fees');
    const g = d.one(`select g.id from guardians g join users u on u.id=g.user_id where u.email=?`, PARENT);
    if (g && r) {
        const kids = d.all('select id from students where guardian_id=?', g.id).map(k => k.id);
        if (kids.length) {
            const q = kids.map(() => '?').join(',');
            const owed = Number(d.value(`select round(coalesce(sum(balance),0),2) from invoices where student_id in (${q}) and status<>'void'`, ...kids));
            f.ok('parent fees page total owed matches the ledger', r.text.includes(naira(owed)) || r.text.includes('₦' + Math.round(owed).toLocaleString('en-NG')), `expected ${naira(owed)}`);
        }
    }
    await ctx.close();
    d.close();
}
