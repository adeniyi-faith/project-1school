---
title: How it is built
---

# How SchoolRuns is built (plain English)

**One application.** The server (Laravel, written in PHP) also makes the screens (React, written in TypeScript). A tool called Inertia passes information from one to the other, so there is no separate "back end" and "front end" to keep in step.

**Many schools, one install.** Almost every table of information has a `school_id`. A rule applied to every database lookup (`SchoolScope`) adds "only this school's rows". That is why one school can never see another's students, fees or results.

**Who may do what.** Every person has a role. Each role has permissions (for example `fees.collect`). Every school screen checks a permission on the server, and the menu hides what the person could not open anyway. Automatic tests fail if a new screen forgets the check.

**Where the work happens.**
- `routes/web.php` lists every address.
- `app/Http/Controllers/` receives each request. Admin areas are in `SchoolAdmin/`.
- `app/Services/` holds the careful calculations (results, fee balances, promotions, report cards).
- `app/Models/` are the database tables.
- `resources/js/Pages/` are the screens.

**Money is never edited.** Fees use a ledger: each charge, payment, discount or reversal is a new line that can't be changed. A mistake is fixed by adding an opposite line, so there is always a record.

**Logins.** By default a person's password is checked against this app's own database (`LOGIN_DRIVER=database`). There is a switch to let Supabase check passwords instead.

**Hosting.** The live site is on cPanel. A merge to `main` runs all tests, builds the screens, uploads them, and updates the server.

**Honest limits.** PDFs and message blasts run while the page waits, not in a background queue. There is no real SMS provider connected yet. There are no automatic tests for the screens themselves, only for the server.
