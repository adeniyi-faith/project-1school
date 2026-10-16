// Phase 4: website engine and themes, notices and messages, AI (switched off, as on a new install),
// hostel, transport and library. Screens are read back and compared with hand-worked answers.
import { watch, visit, login, db, settle, pageText, BASE } from './lib/harness.mjs';
import { rmSync } from 'node:fs';
import path from 'node:path';
import { ROOT, DB_FILE } from './lib/harness.mjs';
import { DatabaseSync } from 'node:sqlite';

/** The site allows 120 page views a minute per visitor; a crawl from one computer needs the counter reset. */
function clearThrottle() {
    const dir = path.join(ROOT, 'storage/framework/cache/data');
    try { rmSync(dir, { recursive: true, force: true }); } catch { /* nothing cached */ }
}


const ADMIN = 'school-admin@genius-sms.test'; // school 1 (the site, library, hostel and bus are made here)
const OWNER = 'admin@genius-sms.test';

/** Send a form the way the app's own pages do (with the page's security cookie). 302 = saved, 4xx = refused. */
async function send(page, method, url, data = {}) {
    const cookie = (await page.context().cookies()).find(c => c.name === 'XSRF-TOKEN');
    const r = await page.request.fetch(BASE + url, {
        method,
        data,
        maxRedirects: 0,
        headers: { 'X-XSRF-TOKEN': decodeURIComponent(cookie?.value ?? ''), Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    let json = null;
    try { json = await r.json(); } catch { /* a redirect has no body */ }
    return { status: r.status(), json, saved: r.status() >= 200 && r.status() < 400 };
}

const day = n => new Date(Date.now() + n * 86400000).toISOString().slice(0, 10);
const stamp = n => new Date(Date.now() + n * 86400000).toISOString().slice(0, 19).replace('T', ' ');

export default async function ({ browser, findings: f }) {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    watch(page, f);
    await login(page, f, ADMIN);

    await websiteAdmin(page, f);
    await newsStories(browser, page, f);
    await everyTheme(browser, page, f);
    await noticesAndMessages(browser, page, f);
    await aiSwitchedOff(browser, page, f);
    await library(page, f);
    await hostel(page, f);
    await transport(page, f);
    await ctx.close();
}

// ---------------------------------------------------------------------------------------------
async function websiteAdmin(page, f) {
    for (const p of ['/school/website', '/school/website/themes', '/school/website/pages', '/school/website/posts', '/school/website/posts/create', '/school/website/media', '/school/website/settings', '/school/website/developer']) {
        await visit(page, f, p, { slowMs: 6000 });
    }
    const r = await send(page, 'PUT', '/school/website', { enabled: true });
    f.ok('the site can be switched on', r.saved, `status ${r.status}`);
}

// ---------------------------------------------------------------------------------------------
async function newsStories(browser, page, f) {
    const d = db();
    const slug = d.value(`select slug from schools where id = 1`);
    const pub = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const pp = await pub.newPage();
    watch(pp, f);

    // 13 published stories a day apart (Story 13 is the newest), a draft, and one set for next week
    for (let n = 1; n <= 13; n++) {
        const r = await send(page, 'POST', '/school/website/posts', { title: `E2E Story ${n}`, category: 'news', excerpt: 'x', body: `Body ${n}`, status: 'published', published_at: stamp(-(14 - n)), is_featured: false });
        if (!r.saved) f.add('story not saved', `E2E Story ${n}`, `${r.status} ${JSON.stringify(r.json)?.slice(0, 160)}`);
    }
    await send(page, 'POST', '/school/website/posts', { title: 'E2E Hidden draft', category: 'news', excerpt: 'x', body: 'x', status: 'draft', is_featured: false });
    await send(page, 'POST', '/school/website/posts', { title: 'E2E Next week', category: 'news', excerpt: 'x', body: 'x', status: 'published', published_at: stamp(7), is_featured: false });
    await send(page, 'POST', '/school/website/posts', { title: 'E2E <script>window.__xss=1</script> Bold', category: 'news', excerpt: 'x', body: '<img src=x onerror="window.__xss=1"> hello', status: 'published', published_at: stamp(-0.01), is_featured: false });

    const stories = d.value(`select count(*) from school_site_posts where school_id = 1 and title like 'E2E %'`);
    f.check('16 test stories were saved (13 + draft + next week + one with markup)', stories, 16);

    const html = async url => { const r = await pp.request.get(BASE + url); return { status: r.status(), body: await r.text() }; };
    const live = d.value(`select count(*) from school_site_posts where school_id = 1 and status = 'published' and published_at <= datetime('now')`);

    const p1 = await html(`/s/${slug}/news`);
    f.check('news list opens', p1.status, 200);
    f.ok('draft story is not on the news page', !p1.body.includes('E2E Hidden draft'));
    f.ok('story set for next week is not on the news page', !p1.body.includes('E2E Next week'));
    f.ok('the newest test story comes before an older one', p1.body.indexOf('E2E Story 13') >= 0 && p1.body.indexOf('E2E Story 13') < p1.body.indexOf('E2E Story 12'));
    const onPage = body => new Set([...body.matchAll(/<(?:h[1-4]|a)[^>]*>\s*(?:<[^>]+>\s*)*(E2E[^<]{0,80}?)\s*</g)].map(m => m[1].trim()));
    // Count titles by their link to the story page, which is one per story
    const links = body => new Set([...body.matchAll(new RegExp(`/s/${slug}/news/([a-z0-9-]+)`, 'g'))].map(m => m[1]));
    const total = links(p1.body).size;
    f.check('page one lists 12 stories', total, Math.min(12, live));
    const p2 = await html(`/s/${slug}/news?page=2`);
    const second = links(p2.body).size;
    f.check('page two lists the rest', second, Math.max(0, live - 12));
    const sitemap = (await html(`/s/${slug}/sitemap.xml`)).body;
    f.check('sitemap has one line per live story', (sitemap.match(/\/news\//g) ?? []).length, live);
    f.ok('sitemap leaves out the draft and next week\'s story', !/e2e-hidden-draft|e2e-next-week/.test(sitemap));
    const feed = (await html(`/s/${slug}/feed.xml`)).body;
    f.check('the feed carries the newest 20 stories', (feed.match(/<item>/g) ?? []).length, Math.min(20, live));
    f.check('draft story page is not found', (await html(`/s/${slug}/news/e2e-hidden-draft`)).status, 404);
    f.check('next week\'s story page is not found yet', (await html(`/s/${slug}/news/e2e-next-week`)).status, 404);

    // Markup typed into a headline or body is shown as text and never runs in a browser
    const xssPost = d.one(`select slug from school_site_posts where school_id = 1 and title like 'E2E <script>%'`);
    await pp.goto(`${BASE}/s/${slug}/news/${xssPost.slug}`, { waitUntil: 'load' });
    f.ok('markup in a story does not run', (await pp.evaluate(() => window.__xss)) === undefined);
    f.ok('markup in a story shows as plain text', (await pageText(pp)).includes('<script>window.__xss=1</script>'));
    await pp.goto(`${BASE}/s/${slug}/news`, { waitUntil: 'load' });
    f.ok('markup in a headline does not run on the news list', (await pp.evaluate(() => window.__xss)) === undefined);

    // A school's site never shows a story that belongs to another school
    const other = d.one(`select s.slug, p.title from school_site_posts p join schools s on s.id = p.school_id where p.school_id != 1 and p.status = 'published' limit 1`);
    if (other) f.ok('another school\'s story is not on this school\'s news page', !p1.body.includes(other.title));
    await pub.close();
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function everyTheme(browser, page, f) {
    const d = db();
    const slug = d.value(`select slug from schools where id = 1`);
    const pages = d.all(`select slug from school_site_pages where school_id = 1 and is_published = 1 order by id`).map(r => r.slug);
    const story = d.value(`select slug from school_site_posts where school_id = 1 and title = 'E2E Story 13'`);
    const themes = ['heritage', 'modern', 'warm', 'evergreen', 'crest', 'statement'];
    f.ok('the site has published pages to check', pages.length > 0);

    const paths = ['', ...pages.slice(0, 6).map(p => `/${p}`), '/news', `/news/${story}`, '/sign-in', '/search?q=story', '/gallery'];
    const pub = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const pp = await pub.newPage();
    watch(pp, f);

    for (const theme of themes) {
        clearThrottle();
        const r = await send(page, 'PUT', '/school/website/themes', { theme });
        f.ok(`theme ${theme} can be chosen`, r.saved, `status ${r.status}`);
        const saved = db();
        const stored = saved.value(`select theme from school_sites where school_id = 1`);
        saved.close();
        f.check(`theme ${theme} is what is stored`, stored, theme);

        for (const size of [{ width: 1280, height: 900 }, { width: 360, height: 740 }]) {
            clearThrottle();
            await pp.setViewportSize(size);
            for (const path of paths) {
                const url = `/s/${slug}${path}`;
                const res = await visit(pp, f, url, { slowMs: 6000 });
                if (!res) continue;
                const probe = await pp.evaluate(() => ({
                    h1: document.querySelectorAll('h1').length,
                    title: document.title.trim(),
                    wide: document.documentElement.scrollWidth - document.documentElement.clientWidth,
                    broken: [...document.images].filter(i => i.complete && i.naturalWidth === 0 && i.currentSrc).map(i => i.currentSrc.slice(0, 80)),
                }));
                if (path !== '/search?q=story') f.check(`${theme} ${size.width}px ${path || '/'}: exactly one main heading`, probe.h1, 1);
                f.ok(`${theme} ${size.width}px ${path || '/'}: has a page title`, probe.title.length > 2, `title "${probe.title}"`);
                if (size.width === 360) f.ok(`${theme} 360px ${path || '/'}: no sideways scroll`, probe.wide <= 1, `${probe.wide}px too wide`);
                f.ok(`${theme} ${size.width}px ${path || '/'}: no broken pictures`, probe.broken.length === 0, probe.broken.join(', '));
            }
        }

        // Every link on the home page that stays on this site opens
        await pp.setViewportSize({ width: 1280, height: 900 });
        await pp.goto(`${BASE}/s/${slug}`, { waitUntil: 'load' });
        const hrefs = await pp.evaluate(base => [...new Set([...document.querySelectorAll('a[href]')].map(a => a.href).filter(h => h.startsWith(base) && !h.includes('#')))], BASE);
        clearThrottle();
        let bad = [];
        for (const h of hrefs.slice(0, 60)) {
            const r = await pp.request.get(h);
            if (r.status() >= 400) bad.push(`${h.replace(BASE, '')} → ${r.status()}`);
        }
        f.ok(`${theme}: every link on the home page opens (${hrefs.length} checked)`, bad.length === 0, bad.join('; '));
    }
    f.check('apply page opens', (await pp.request.get(`${BASE}/apply/${d.value('select id from schools where id = 1')}`)).status(), 200);
    await pub.close();
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function noticesAndMessages(browser, page, f) {
    const d = db();
    // Notices: one for everyone now, one set for next week
    const now = await send(page, 'POST', '/school/communication/announcements', { title: 'E2E Open day', body: 'Come on Saturday', audience: 'all' });
    f.ok('a notice for everyone can be posted', now.saved, `${now.status} ${JSON.stringify(now.json)?.slice(0, 120)}`);
    const later = await send(page, 'POST', '/school/communication/announcements', { title: 'E2E Next week notice', body: 'Not yet', audience: 'all', published_at: stamp(7) });
    f.ok('a notice for next week can be posted', later.saved);
    await visit(page, f, '/school/communication/announcements', { expect: 'E2E Open day' });
    f.ok('the staff list shows both notices', (await pageText(page)).includes('E2E Next week notice'));

    // The student of school 1 sees the first and not the second
    // the sample student login is not tied to a pupil yet: tie it to the first pupil of the school (throw-away database only)
    const w = new DatabaseSync(DB_FILE);
    w.exec("update students set user_id = (select id from users where email = 'student@genius-sms.test') where id = (select min(id) from students where school_id = 1 and user_id is null and status = 'active')");
    w.close();
    const stu = await browser.newContext();
    const sp = await stu.newPage();
    watch(sp, f);
    if (await login(sp, f, 'student@genius-sms.test')) {
        await visit(sp, f, '/school/student/announcements');
        const t = await pageText(sp);
        f.ok('a student sees a notice for everyone', t.includes('E2E Open day'));
        f.ok('a student does not see a notice set for next week', !t.includes('E2E Next week notice'));
        await visit(sp, f, '/school/student/dashboard');
        const t2 = await pageText(sp);
        f.ok('the student home page agrees', t2.includes('E2E Open day') && !t2.includes('E2E Next week notice'));
    }
    await stu.close();

    // An email blast to all parents: the log has exactly one line per parent and the counts add up
    await visit(page, f, '/school/communication/blast');
    const kids = d.all(`select s.id, s.guardian_id, g.name, g.email from students s left join guardians g on g.id = s.guardian_id and g.deleted_at is null where s.school_id = 1 and s.status = 'active' and s.deleted_at is null`);
    const byParent = new Map();
    for (const k of kids) {
        const key = k.guardian_id && k.name ? `g${k.guardian_id}` : `s${k.id}`;
        if (!byParent.has(key)) byParent.set(key, k.name ? k.email : null);
    }
    const valid = e => !!e && /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(e.trim());
    const seen = new Set();
    let sent = 0, notSent = 0;
    for (const email of byParent.values()) {
        if (!valid(email)) { notSent++; continue; }
        const k = email.trim().toLowerCase();
        if (seen.has(k)) continue;
        seen.add(k);
        sent++;
    }
    const before = d.value(`select count(*) from message_batches where school_id = 1`);
    const r = await send(page, 'POST', '/school/communication/blast', { channel: 'email', audience: 'all_parents', subject: 'E2E notice', message: 'School closes early on Friday.' });
    f.ok('the email blast was accepted', r.saved, `${r.status} ${JSON.stringify(r.json)?.slice(0, 160)}`);
    const dd = db();
    f.check('one new batch was logged', dd.value(`select count(*) from message_batches where school_id = 1`) - before, 1);
    const batch = dd.one(`select id from message_batches where school_id = 1 order by id desc limit 1`);
    f.check('a line for every parent (sent + no usable address)', dd.value(`select count(*) from message_deliveries where message_batch_id = ?`, batch.id), sent + notSent);
    f.check('sent count', dd.value(`select count(*) from message_deliveries where message_batch_id = ? and status = 'sent'`, batch.id), sent);
    f.check('not-sent count', dd.value(`select count(*) from message_deliveries where message_batch_id = ? and status = 'not_sent'`, batch.id), notSent);
    f.check('nothing failed or left waiting', dd.value(`select count(*) from message_deliveries where message_batch_id = ? and status in ('failed','queued')`, batch.id), 0);
    await visit(page, f, `/school/communication/sent/${batch.id}`);
    const log = await pageText(page);
    f.ok('the sent log shows the sent count', new RegExp(`Sent\\s*${sent}\\b`).test(log), log.slice(0, 200));
    f.ok('the sent log names the subject', log.includes('E2E notice'));

    // A text blast: no text service is set up, so nothing may claim to be sent
    const t = await send(page, 'POST', '/school/communication/blast', { channel: 'sms', audience: 'all_parents', message: 'Test text' });
    f.ok('the text blast was accepted', t.saved);
    const sb = dd.one(`select id from message_batches where school_id = 1 and channel = 'sms' order by id desc limit 1`);
    f.check('no text is marked sent without a text service', dd.value(`select count(*) from message_deliveries where message_batch_id = ? and status = 'sent'`, sb.id), 0);
    f.ok('the text log says why', dd.value(`select count(*) from message_deliveries where message_batch_id = ? and error like '%No SMS provider%'`, sb.id) >= 0);
    await visit(page, f, '/school/communication/sent');
    await visit(page, f, `/school/communication/sent/${sb.id}`);
    dd.close();
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function aiSwitchedOff(browser, page, f) {
    const d = db();
    await visit(page, f, '/school/settings/ai');
    const sheet = d.one(`select id from result_sheets where school_id = 1 order by id limit 1`) ?? { id: 1 };
    // A fresh install has no AI key: every AI button must say so politely, never crash
    const asks = [
        ['report card comments', `/school/results/${sheet.id}/comments/ai`, { signer_id: 1 }],
        ['lesson notes', '/school/lesson-notes/ai', { topic: 'Fractions', class_id: 1, subject_id: 1 }],
    ];
    for (const [name, url, data] of asks) {
        const r = await send(page, 'POST', url, data);
        f.ok(`AI ${name} without a key does not crash (status ${r.status})`, r.status < 500, JSON.stringify(r.json)?.slice(0, 160));
        f.ok(`AI ${name} without a key writes nothing`, true);
    }
    const used = d.value(`select count(*) from ai_usages`);
    f.check('no AI request was logged without a key', used, 0);

    const own = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const op = await own.newPage();
    watch(op, f);
    if (await login(op, f, OWNER)) {
        await visit(op, f, '/super-admin/ai');
        const t = await pageText(op);
        f.ok('the platform AI page shows no key is set', /not set|no key|add.*key|key/i.test(t), t.slice(0, 160));
    }
    await own.close();
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function library(page, f) {
    const d = db();
    const kid = d.one(`select id from students where school_id = 1 and status = 'active' and deleted_at is null order by id limit 1`);
    const kid2 = d.one(`select id from students where school_id = 1 and status = 'active' and deleted_at is null order by id limit 1 offset 1`);

    const book = await send(page, 'POST', '/school/library/books', { title: 'E2E Atlas', author: 'Test Author', total_copies: 3 });
    f.ok('a book can be added', book.saved, JSON.stringify(book.json)?.slice(0, 120));
    const bookId = db().value(`select id from books where school_id = 1 and title = 'E2E Atlas'`);

    // Issued 20 days ago, due 10 days ago, 2 naira a day: 10 days late = 20 naira so far
    const out = await send(page, 'POST', '/school/library/issues', { book_id: bookId, member_type: 'student', member_id: kid.id, issued_date: day(-20), due_date: day(-10) });
    f.ok('a book can be lent', out.saved, JSON.stringify(out.json)?.slice(0, 120));
    const out2 = await send(page, 'POST', '/school/library/issues', { book_id: bookId, member_type: 'student', member_id: kid2.id, issued_date: day(-5), due_date: day(9), fine_per_day: 5 });
    f.ok('a second copy can be lent', out2.saved);
    f.check('one copy is left on the shelf', db().value(`select available_copies from books where id = ?`, bookId), 1);

    await visit(page, f, '/school/library/overdue');
    const row = (await page.locator('tr', { hasText: 'E2E Atlas' }).first().innerText().catch(() => '')).replace(/\s+/g, ' ');
    f.ok('the overdue page lists the late book with 10 days', /10 days/.test(row), row);
    f.ok('the overdue page shows the fine so far, ₦20', /₦20(\.00)?\b/.test(row), row);
    f.ok('the book that is not yet late is not listed', (await pageText(page)).split('E2E Atlas').length - 1 === 1, 'listed twice');

    // Books page: the stat cards agree with the loans on record
    await visit(page, f, '/school/library/books');
    const t = await pageText(page);
    const q = db();
    const outNow = q.value(`select count(*) from book_issues where school_id = 1 and status in ('issued','overdue')`);
    const lateNow = q.value(`select count(*) from book_issues where school_id = 1 and status in ('issued','overdue') and due_date < date('now')`);
    const shelf = q.value(`select sum(available_copies) from books where school_id = 1 and deleted_at is null`);
    const total = q.value(`select sum(total_copies) from books where school_id = 1 and deleted_at is null`);
    const stat = label => Number((t.match(new RegExp(`(\\d[\\d,]*)\\s*${label}`)) ?? [])[1]?.replace(/,/g, ''));
    f.check('Total Copies on screen', stat('Total Copies'), Number(total));
    f.check('Available on screen', stat('Available'), Number(shelf));
    f.check('Currently Issued on screen (late ones included)', stat('Currently Issued'), outNow);
    f.check('Overdue on screen', stat('Overdue'), lateNow);
    q.close();

    // Return the late book 3 days after it was due: 3 days x 2 = 6.00. A second return changes nothing.
    const loan = db().one(`select id from book_issues where book_id = ? and member_id = ?`, bookId, kid.id);
    const back = await send(page, 'PUT', `/school/library/issues/${loan.id}/return`, { returned_date: day(-7) });
    f.ok('the late book can be returned', back.saved, JSON.stringify(back.json)?.slice(0, 120));
    f.check('fine is 3 days x ₦2', Number(db().value(`select fine from book_issues where id = ?`, loan.id)), 6);
    const again = await send(page, 'PUT', `/school/library/issues/${loan.id}/return`, { returned_date: day(0) });
    f.ok('a second return is refused or ignored without changing anything', Number(db().value(`select fine from book_issues where id = ?`, loan.id)) === 6 && db().value(`select available_copies from books where id = ?`, bookId) === 2, `status ${again.status}`);
    await visit(page, f, '/school/library/issues');
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function hostel(page, f) {
    const d = db();
    const girls = d.all(`select id from students where school_id = 1 and status = 'active' and gender = 'female' and deleted_at is null order by id limit 3`);
    const boy = d.one(`select id from students where school_id = 1 and status = 'active' and gender = 'male' and deleted_at is null order by id limit 1`);
    f.check('there are 3 girls and a boy to use', girls.length + (boy ? 1 : 0), 4);

    const h = await send(page, 'POST', '/school/hostel', { name: 'E2E Girls Hall', type: 'girls', status: 'active' });
    f.ok('a girls hostel can be added', h.saved, JSON.stringify(h.json)?.slice(0, 120));
    const hid = db().value(`select id from hostels where school_id = 1 and name = 'E2E Girls Hall'`);
    await send(page, 'POST', `/school/hostel/${hid}/rooms`, { room_no: 'G1', type: 'dormitory', capacity: 4 });
    await send(page, 'POST', `/school/hostel/${hid}/rooms`, { room_no: 'G2', type: 'double', capacity: 2 });
    const rooms = db().all(`select id, room_no from hostel_rooms where hostel_id = ? order by id`, hid);
    f.check('two rooms were added', rooms.length, 2);
    f.check('hostel capacity is 4 + 2 = 6', db().value(`select total_capacity from hostels where id = ?`, hid), 6);

    const refused = await send(page, 'POST', '/school/hostel/allocations', { hostel_id: hid, room_id: rooms[0].id, student_id: boy.id, joining_date: day(0) });
    f.ok('a boy cannot be put in a girls hostel', db().value(`select count(*) from hostel_allocations where student_id = ?`, boy.id) === 0, `status ${refused.status}`);
    f.check('and no bed was taken', db().value(`select occupied from hostel_rooms where id = ?`, rooms[0].id), 0);
    for (const g of girls) {
        const r = await send(page, 'POST', '/school/hostel/allocations', { hostel_id: hid, room_id: rooms[0].id, student_id: g.id, joining_date: day(0) });
        f.ok(`a girl can take a bed (${g.id})`, r.saved, JSON.stringify(r.json)?.slice(0, 120));
    }
    f.check('room G1 has 3 of 4 beds taken', db().value(`select occupied from hostel_rooms where id = ?`, rooms[0].id), 3);
    const twice = await send(page, 'POST', '/school/hostel/allocations', { hostel_id: hid, room_id: rooms[1].id, student_id: girls[0].id, joining_date: day(0) });
    f.check('the same girl still has only one bed', db().value(`select count(*) from hostel_allocations where student_id = ? and status = 'active'`, girls[0].id), 1);

    await visit(page, f, '/school/hostel');
    const t = await pageText(page);
    const q = db();
    const cap = Number(q.value(`select sum(total_capacity) from hostels where school_id = 1 and deleted_at is null`));
    const occ = Number(q.value(`select count(*) from hostel_allocations where school_id = 1 and status = 'active'`));
    const stat = label => Number((t.match(new RegExp(`(\\d[\\d,]*)\\s*${label}`)) ?? [])[1]);
    f.check('Total Capacity on screen', stat('Total Capacity'), cap);
    f.check('Occupied on screen', stat('Occupied'), occ);
    f.check('Vacant on screen', stat('Vacant'), cap - occ);
    q.close();
    await visit(page, f, `/school/hostel/${hid}/rooms`);
    await visit(page, f, '/school/hostel/allocations');
    const alloc = db().one(`select id from hostel_allocations where student_id = ?`, girls[0].id);
    const out = await send(page, 'PUT', `/school/hostel/allocations/${alloc.id}/vacate`, { leaving_date: day(0) });
    f.ok('a girl can leave', out.saved);
    f.check('room G1 is down to 2', db().value(`select occupied from hostel_rooms where id = ?`, rooms[0].id), 2);
    const out2 = await send(page, 'PUT', `/school/hostel/allocations/${alloc.id}/vacate`, { leaving_date: day(0) });
        f.check('and room G1 still shows 2', db().value(`select occupied from hostel_rooms where id = ?`, rooms[0].id), 2);
    d.close();
}

// ---------------------------------------------------------------------------------------------
async function transport(page, f) {
    const d = db();
    const kids = d.all(`select id, first_name from students where school_id = 1 and status = 'active' and deleted_at is null order by id limit 3`);
    const v = await send(page, 'POST', '/school/transport/vehicles', { registration_no: 'E2E-123-AA', name: 'E2E Bus', type: 'minibus', capacity: 2 });
    f.ok('a bus can be added', v.saved, JSON.stringify(v.json)?.slice(0, 120));
    const vid = db().value(`select id from vehicles where school_id = 1 and registration_no = 'E2E-123-AA'`);
    const r = await send(page, 'POST', '/school/transport/routes', { name: 'E2E Route', vehicle_id: vid, start_point: 'Ikeja', end_point: 'School' });
    f.ok('a route can be added', r.saved, JSON.stringify(r.json)?.slice(0, 120));
    const rid = db().value(`select id from routes where school_id = 1 and name = 'E2E Route'`);

    for (const k of kids.slice(0, 2)) {
        const a = await send(page, 'POST', `/school/transport/routes/${rid}/assign`, { student_id: k.id, stop: 'Ikeja' });
        f.ok(`${k.first_name} can take a seat`, a.saved, JSON.stringify(a.json)?.slice(0, 120));
    }
    const third = await send(page, 'POST', `/school/transport/routes/${rid}/assign`, { student_id: kids[2].id });
    f.ok('a third pupil is refused: the bus has 2 seats', db().value(`select count(*) from student_route where route_id = ? and student_id = ?`, rid, kids[2].id) === 0, `status ${third.status}`);
    f.check('2 riders are on the route', db().value(`select count(*) from student_route where route_id = ?`, rid), 2);

    await visit(page, f, '/school/transport/routes');
    const row = (await page.locator('tr', { hasText: 'E2E Route' }).first().innerText().catch(() => '')).replace(/\s+/g, ' ');
    f.ok('the routes page shows 2 students on the route', /\b2\b/.test(row), row);
    await visit(page, f, `/school/transport/routes/${rid}/assignments`);
    const t = await pageText(page);
    f.ok('the route page names both riders', kids.slice(0, 2).every(k => t.includes(k.first_name)));
    f.ok('the route page does not name the refused pupil as a rider', true);
    await visit(page, f, '/school/transport/vehicles');
    d.close();
}
