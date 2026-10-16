# Browser checks (tests/e2e)

These scripts open the real app in a real (headless) Chromium browser, sign in as each kind of person, click through
the screens, and check that **the answers are right** (a fee balance to the kobo, a count of invoices, a staff number),
not only that the page opened. They also flag error pages, console errors, failed requests, slow pages and sideways scroll.

## Run them

```bash
npm install                      # once
npm run build                    # the app's front end must be built
npm run e2e                      # all phases
npm run e2e -- --phase 1         # one phase (1 = money and people, 2 = daily work, 3 = roles)
```

- It makes its own throw-away database in `tests/e2e/.work/` (filled with the demo schools), starts the app on port 8765
  (change with `E2E_PORT`), runs the checks, and prints **only the problems**. The real database is never touched.
- It uses the Chromium that is already installed (`/opt/pw-browsers`); set `CHROMIUM_PATH` to use another. Never run `playwright install`.
- To see the real cause of a server error, run with `E2E_DEBUG=1`. The web server's output is in `tests/e2e/.work/server.log`.
- Slow-page limit: `E2E_SLOW_MS` (default 2500 ms). `E2E_BASE=http://...` points the checks at an app that is already running
  (it must be a throw-away copy; the checks add and change records).
- Screenshots and a JSON copy of each report are saved in `tests/e2e/.work/out/`.

## How an answer check works

`lib/harness.mjs` gives each script `f.check(name, actual, expected)`. The expected figure is **written out by hand in the script**
(for example: a ₦150,000 fee with ₦1,000.55 paid leaves ₦148,999.45) or read straight from the database rows with `db()`,
never calculated with the app's own formula.

## Files

| File | What it does |
|---|---|
| `run.mjs` | starts everything, runs the chosen phases, prints the problem list |
| `lib/harness.mjs` | sign-in, error watching, page visit checks, form helpers, database reader |
| `phase1.mjs` | money and people: sign-in, admissions to enrolment, admitting students, staff, fees and payments, permissions, parent view |
| `phase3.mjs` | every role's view: sign-in checks (wrong password, throttle, sign out), platform owner and acting-in-a-school, each of the 11 roles (landing page, menu has/has-not, every menu link opens, forbidden screens show the plain not-allowed page), buttons follow permissions, parent and student see the right figures for the right child, demo is read-only |
| `phase4.mjs` | website: 16 test stories (paging, draft and future hidden, sitemap/feed counts, markup shown as text), all 6 themes on every page at desktop and 360px wide (one heading, no sideways scroll, no broken pictures, links open); notices (scheduled one hidden from the student); email and text blasts against hand-counted logs; AI off gives no crash; library, hostel and bus numbers on screen vs the loans, beds and seats made |
| `phase2.mjs` | daily school work: classes, attendance, score entry with hand-worked totals/grades/positions, report card and broadsheet files, CBT and timetable screens, teacher and student demo views |
| `lib/php83-polyfill.php`, `lib/router.php` | only used on a machine with PHP older than 8.4 (the app needs 8.4) |

The faster right-answer checks that need no browser live in `tests/Feature/Correctness` and run with `php artisan test`.
