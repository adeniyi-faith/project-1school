// Phase 2: daily school work. Classes, subjects, timetable, attendance, exams, scores, report sheets, CBT.
import { watch, visit, login, demoLogin, overflow, db, choose, type, field, dialog, settle, pageText, BASE } from './lib/harness.mjs';

const ADMIN = 'school-admin@genius-sms.test';   // school 1: small, used for the flows that change data

const SCREENS = [
    '/school/classes', '/school/sections', '/school/subjects', '/school/shifts', '/school/holidays',
    '/school/academics/terms', '/school/academics/assessment', '/school/timetable', '/school/timetable/teacher',
    '/school/attendance', '/school/attendance/staff', '/school/exams', '/school/results', '/school/results/broadsheets', '/school/results/print',
    '/school/results/comment-bank', '/school/academics/report-card-designs',
    '/school/cbt/banks', '/school/cbt/tests', '/school/cbt/papers', '/school/lesson-notes', '/school/lesson-notes/schemes', '/school/lesson-notes/formats',
    '/school/homework', '/school/homework/syllabi', '/school/homework/online-classes',
    '/school/reports/dashboard', '/school/reports/attendance', '/school/promotions',
];

export default async function ({ browser, findings: f }) {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    watch(page, f);
    await login(page, f, ADMIN);
    for (const p of SCREENS) await visit(page, f, p);
    await classesFlow(page, f);
    await attendanceFlow(page, f);
    await scoresFlow(page, f);
    await ctx.close();

    await bigSchool(browser, f);
    await studentSide(browser, f);
}

// ---------------------------------------------------------------------------------------------
async function classesFlow(page, f) {
    const d = db();
    await visit(page, f, '/school/classes');
    const before = d.value('select count(*) from classes where school_id=1 and deleted_at is null');
    await page.getByRole('button', { name: /Add Class/ }).first().click();
    await page.locator('input[name=name]').fill('E2E Nine'); // only the name: the order box is left empty on purpose
    await dialog(page).getByRole('button', { name: 'Create Class' }).click();
    await settle(page);
    f.check('class added', d.value('select count(*) from classes where school_id=1 and deleted_at is null') - before, 1);

    // The same name again, in capitals: refused with a message beside the box, nothing added
    await page.getByRole('button', { name: /Add Class/ }).first().click();
    await page.locator('input[name=name]').fill('e2e nine');
    await dialog(page).getByRole('button', { name: 'Create Class' }).click();
    await settle(page);
    f.check('duplicate class name adds nothing', d.value('select count(*) from classes where school_id=1 and deleted_at is null') - before, 1);
    f.ok('duplicate class name explains itself', /already exists/i.test(await pageText(page)), 'no message');
    await page.keyboard.press('Escape');

    // A class with pupils on roll cannot be deleted from the screen
    const busy = d.one(`select c.id, c.name from classes c where c.school_id=1 and c.deleted_at is null and exists (select 1 from students s where s.class_id=c.id and s.status='active') order by c.id limit 1`);
    await visit(page, f, '/school/classes');
    page.once('dialog', dlg => dlg.accept());
    const card = page.locator(`xpath=//*[normalize-space(text())='${busy.name}']/ancestor::*[.//button][1]`).first();
    const trash = card.locator('button:has(svg.lucide-trash-2, svg.lucide-trash)').first();
    if (await trash.count()) {
        await trash.click();
        await settle(page, 900);
        f.ok('delete of a busy class says why not', /still has .* on roll/i.test(await pageText(page)), 'no message');
    } else f.add('missing content', '/school/classes', 'could not find the delete button');
    f.check('class with pupils is still there after a delete attempt', d.value('select count(*) from classes where id=? and deleted_at is null', busy.id), 1);
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function attendanceFlow(page, f) {
    const d = db();
    const cls = d.one(`select c.id, c.name, (select count(*) from students s where s.class_id=c.id and s.status='active' and s.deleted_at is null) n from classes c where c.school_id=1 and c.deleted_at is null and (select count(*) from students s where s.class_id=c.id and s.status='active' and s.deleted_at is null)>=3 order by c.numeric_name, c.id limit 1`);
    const day = new Date(Date.now() - 200 * 86400000).toISOString().slice(0, 10); // a day nothing is marked for yet
    await visit(page, f, `/school/attendance?class_id=${cls.id}&date=${day}`);
    const rows = page.locator('li', { has: page.getByRole('radiogroup') });
    const n = await rows.count();
    f.check(`attendance page lists every active pupil of ${cls.name}`, n, Number(cls.n));
    if (n < 3) return;
    await rows.nth(0).getByRole('radio', { name: 'Absent' }).click();
    await rows.nth(1).getByRole('radio', { name: 'Late' }).click();
    await rows.nth(2).getByRole('radio', { name: /Half/ }).click();
    await page.getByRole('button', { name: /Save attendance/ }).click();
    await settle(page, 1000);
    const count = status => Number(d.value(`select count(*) from attendances where school_id=1 and date(date)=? and attendable_type like '%Student' and status=? and attendable_id in (select id from students where class_id=?)`, day, status, cls.id));
    f.check('rows saved for the day', count('present') + count('absent') + count('late') + count('half_day'), Number(cls.n));
    f.check('one absent', count('absent'), 1);
    f.check('one late', count('late'), 1);
    f.check('one half day', count('half_day'), 1);
    f.check('the rest present', count('present'), Number(cls.n) - 3);

    // Opening the day again shows the same marks, and saving again does not add rows
    await visit(page, f, `/school/attendance?class_id=${cls.id}&date=${day}`);
    const first = page.locator('li', { has: page.getByRole('radiogroup') }).nth(0);
    f.ok('first pupil shows as Absent after reopening', (await first.getByRole('radio', { name: 'Absent' }).getAttribute('aria-checked')) === 'true', 'mark was not remembered');
    await page.getByRole('button', { name: /Save attendance/ }).click();
    await settle(page, 800);
    f.check('saving twice does not double the rows', count('present') + count('absent') + count('late') + count('half_day'), Number(cls.n));
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function scoresFlow(page, f) {
    const d = db();
    await visit(page, f, '/school/results');
    // a class with at least two pupils, its sheet for the current term (the page makes the sheets)
    const sheet = d.one(`select rs.id, rs.class_id, rs.status, c.name from result_sheets rs join classes c on c.id=rs.class_id
        where rs.school_id=1 and rs.status='draft' and (select count(*) from students s where s.class_id=rs.class_id and s.status='active' and s.deleted_at is null)>=2 order by rs.id limit 1`);
    if (!f.ok('a draft result sheet exists to enter scores into', sheet, 'none')) return;
    const pupils = d.all(`select id, first_name, last_name from students where class_id=? and status='active' and deleted_at is null order by id limit 2`, sheet.class_id);
    const nameOf = p => `${p.first_name} ${p.last_name ?? ''}`.trim();
    await visit(page, f, `/school/results/${sheet.id}`);
    const box = (part, p) => page.getByLabel(`${part} for ${nameOf(p)}`, { exact: true });
    if (!(await box('CA1', pupils[0]).count())) { f.add('missing content', `/school/results/${sheet.id}`, 'no score boxes (CA1 for ...) on the sheet'); return; }
    const subjectId = (await page.url().match(/subject_id=(\d+)/)?.[1]) ?? d.value(`select cs.subject_id from class_subjects cs join subjects s on s.id=cs.subject_id where cs.class_id=? and s.deleted_at is null order by s.name limit 1`, sheet.class_id);

    // Too-high score is refused with a message and saves nothing
    await box('CA1', pupils[0]).fill('25');
    await page.getByRole('button', { name: /Save/ }).first().click();
    await settle(page, 800);
    f.check('over-maximum score is not saved', Number(d.value('select count(*) from subject_scores where result_sheet_id=?', sheet.id)), 0);
    f.ok('over-maximum score explains itself', /too high|out of/i.test(await pageText(page)), 'no message');

    // Hand-worked: pupil 1 = 15 + 12.5 + 40 = 67.5 (B3) ; pupil 2 = 18 + 19 + 55 = 92 (A1) ; so pupil 2 is 1st, pupil 1 is 2nd
    const entries = [[pupils[0], 15, 12.5, 40], [pupils[1], 18, 19, 55]];
    for (const [p, a, b, c] of entries) {
        await box('CA1', p).fill(String(a));
        await box('CA2', p).fill(String(b));
        await box('Exam', p).fill(String(c));
    }
    await page.getByRole('button', { name: /Save/ }).first().click();
    await settle(page, 1200);
    const res = id => d.one('select * from term_results where result_sheet_id=? and student_id=? and subject_id=?', sheet.id, id, Number(subjectId));
    const r1 = res(pupils[0].id), r2 = res(pupils[1].id);
    if (f.ok('scores produce stored results', r1 && r2, 'no term_results rows')) {
        f.check('pupil 1 total', Number(r1.total), 67.5);
        f.check('pupil 1 grade', r1.grade, 'B3');
        f.check('pupil 1 subject position', r1.subject_position, 2);
        f.check('pupil 2 total', Number(r2.total), 92);
        f.check('pupil 2 grade', r2.grade, 'A1');
        f.check('pupil 2 subject position', r2.subject_position, 1);
        f.check('subject average', Number(r1.subject_average), 79.75); // (67.5 + 92) / 2
    }
    // The totals are shown back on the Results tab
    await visit(page, f, `/school/results/${sheet.id}?tab=results&subject_id=${subjectId}`);
    const t = await pageText(page);
    f.ok('results tab shows 67.5 and 92', t.includes('67.5') && /\b92\b/.test(t), 'figures not on screen');
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function bigSchool(browser, f) {
    const d = db();
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    watch(page, f);
    await demoLogin(page, f, 'School Admin');
    for (const p of SCREENS) await visit(page, f, p);

    const sheet = d.one(`select rs.id, rs.class_id, rs.term_id from result_sheets rs where rs.school_id=2 and rs.status in ('published','locked') order by rs.id limit 1`);
    if (sheet) {
        await visit(page, f, `/school/results/${sheet.id}`);
        await visit(page, f, `/school/results/${sheet.id}?tab=results`);
        await visit(page, f, `/school/results/${sheet.id}/report-cards`);
        // Files: report cards (term, whole class) and broadsheets must be real, non-empty files, produced in reasonable time
        for (const [name, url, type] of [
            ['report cards PDF', `/school/results/${sheet.id}/report-cards/term`, 'pdf'],
            ['broadsheet PDF', `/school/results/${sheet.id}/broadsheet`, 'pdf'],
            ['broadsheet Excel', `/school/results/${sheet.id}/broadsheet?format=xlsx`, 'spreadsheetml'],
        ]) {
            const t0 = Date.now();
            const r = await ctx.request.get(BASE + url, { timeout: 60000 });
            const body = await r.body();
            const ms = Date.now() - t0;
            f.ok(`${name} opens`, r.status() === 200, `status ${r.status()}`);
            f.ok(`${name} is the right kind of file`, (r.headers()['content-type'] ?? '').includes(type), r.headers()['content-type']);
            f.ok(`${name} is not empty`, body.length > 3000, `${body.length} bytes`);
            if (type === 'pdf') f.ok(`${name} starts like a PDF`, body.slice(0, 4).toString() === '%PDF', body.slice(0, 8).toString());
            if (ms > 20000) f.add('slow page', url, `${ms} ms to build the ${name}`);
            f.timings.push({ url: name, ms });
        }
        // Figures on the broadsheet equal the stored results: the class average shown on the sheet page
        const avg = d.value('select class_average from result_sheets where id=?', sheet.id);
        if (avg != null) {
            await visit(page, f, `/school/results/${sheet.id}?tab=results`);
            const txt = await pageText(page);
            f.ok('sheet page shows the stored class average', txt.includes(String(Number(avg))) || txt.includes(Number(avg).toFixed(1)) || txt.includes(Number(avg).toFixed(2)), `class average ${avg} not found`);
        }
    }
    // The Cbt screens with real demo tests
    const test = d.one(`select id from cbt_tests where school_id=2 order by id desc limit 1`);
    if (test) {
        await visit(page, f, `/school/cbt/tests/${test.id}`);
        const att = d.one(`select id from cbt_attempts where cbt_test_id=? limit 1`, test.id);
        if (att) await visit(page, f, `/school/cbt/tests/${test.id}/attempts/${att.id}`);
    }
    const bank = d.one('select id from cbt_banks where school_id=2 order by id limit 1');
    if (bank) await visit(page, f, `/school/cbt/banks/${bank.id}`);
    const paper = d.one('select id from exam_papers where school_id=2 limit 1');
    if (paper) {
        for (const kind of ['pdf', 'scheme']) {
            const r = await ctx.request.get(BASE + `/school/cbt/papers/${paper.id}/${kind}`, { timeout: 60000 });
            if (r.status() !== 200 && r.status() !== 404) f.add('unexpected status ' + r.status(), `/school/cbt/papers/${paper.id}/${kind}`, '');
        }
    }
    // A big list stays quick and shows a page of it, not all of it
    await visit(page, f, '/school/students', { slowMs: 2000 });
    const rows = await page.locator('tbody tr').count();
    f.ok('students list shows a page, not everyone', rows > 0 && rows <= 100, `${rows} rows`);
    await ctx.close();
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function studentSide(browser, f) {
    for (const [who, pages] of [
        ['Teacher', ['/school/timetable/teacher', '/school/attendance', '/school/results', '/school/cbt/tests', '/school/lesson-notes', '/school/homework']],
        ['Student', ['/school/student/timetable', '/school/student/attendance', '/school/student/results', '/school/student/cbt', '/school/student/homework']],
    ]) {
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
        const page = await ctx.newPage();
        watch(page, f);
        await demoLogin(page, f, who);
        for (const p of pages) await visit(page, f, p);
        await ctx.close();
    }
}
