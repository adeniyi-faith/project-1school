# CLAUDE.md — SchoolRuns (School Management System)

> The guide for every Claude Code session on this project. Read it before writing code.
> It describes what is **actually built today**. Where the older planning files in
> `requirements/` describe something different, this file wins. Last checked: October 2026.

---

## What this project is

A school management system for many schools on one install. One Laravel app serves its own
React pages through Inertia.js (no separate API). Roles: Super Admin, School Admin, Principal,
Teacher, Accountant, Librarian, Receptionist, Driver, Warden, Store Manager, Student, Parent.

Long-term direction: a Nigerian-first "Education OS" (three terms, continuous assessment,
WAEC-style report cards, Naira payments). The build checklist for that lives in the project's
shared files; Phase 1 (safety fixes) is finished. Phase 2 (terms, score/grade setup, stored term results, invoices, the payment ledger, scholarships, and moving old fee payments into invoices) is finished. Phase 3 is next.

---

## Tech stack (what is really in the repo)

| Layer | What is used |
|---|---|
| Backend | Laravel 13, PHP (`composer.json` says ^8.3, but the lock file pulls Symfony 8 which needs PHP 8.4 — use 8.4) |
| Frontend | React 19 + TypeScript, Vite 8, Tailwind CSS 4 |
| Bridge | Inertia.js 3 (every page is rendered through it) |
| UI | shadcn-style components (Base UI) in `resources/js/components` |
| Client state | Zustand — three small stores (`useAuthStore`, `useUIStore`, `useAttendanceStore`) |
| Forms | react-hook-form + zod; charts with recharts |
| Login | **Supabase checks the password** (`app/Services/SupabaseAuthService.php`); Laravel then decides what the person may do |
| Permissions | Spatie laravel-permission (roles + permissions), Spatie laravel-activitylog (audit trail) |
| Database | Supabase Postgres in production; SQLite for local development and tests |
| Files | Laravel Storage. Disks: `local`, `public`, `supabase` (S3 style), and `private` (documents) |
| PDF | barryvdh/laravel-dompdf, generated inside the web request |
| Queue / cache / sessions | Database drivers. `QUEUE_CONNECTION=sync` by default (jobs run inside the request). The `Procfile` starts `queue:work`; set `QUEUE_CONNECTION=database` in production to use it |
| Hosting | Railway (`railway.json`, `Procfile`). `vercel.json` is left over from an earlier attempt |
| Tests | PHPUnit 12 (`php artisan test`). There are no frontend tests and no CI workflow yet |

**Not in this project (despite older docs):** Laravel 11, `app/Modules/*` folders, Redis,
Horizon, Sanctum, a `/api/v1` REST layer, Stripe or any payment gateway, 2FA, PestPHP,
Vitest, Playwright, a mobile/PWA app, and a real SMS provider.

---

## Code layout

```
app/
  Http/Controllers/
    Auth/            LoginController (Supabase login, rate limited)
    SchoolAdmin/     one controller per area (students, fees, exams ...) — "fat controllers"
    SuperAdmin/      schools, packages, subscriptions, module manager, platform settings
    StudentPortalController, ParentPortalController, PublicAdmissionController
  Models/            flat folder, ~60 models
  Scopes/SchoolScope.php      the "only my school" rule
  Traits/BelongsToSchool.php  adds that rule + fills school_id on create
  Services/          GradingService, TermResultService (works out and stores term results), FeeLedgerService (every change to what a student owes, plus money-in and still-owed totals), LegacyFeeImporter (copies old fee_payments into invoices, and undoes it), SupabaseAuthService
  Support/SchoolDefaults.php  starting terms, score setup and grade scales for every school
  Jobs/              SendSmsBlast (stub: writes to the log), SendEmailBlast
routes/web.php       ONE file for all routes (no api.php)
resources/js/        Pages/<Area>/..., components/, Layouts/, Stores/, Types/
database/seeders/    RolePermissionSeeder (roles + permissions), demo data seeders
tests/Feature/Security/   the safety-net tests (see below)
requirements/        the original specs — a target, not a description of the code
```

---

## What is built (module status)

Everything below exists as working screens unless a caveat says otherwise.

| Area | Status | Caveats |
|---|---|---|
| Login, roles, permissions | Built | Password check via Supabase. No password reset, no self-sign-up, no 2FA. Accounts are created by admins |
| Multi-school (tenancy) | Built | `school_id` + `SchoolScope`. Super Admin manages schools, packages, subscriptions, module switches |
| School setup | Built | Classes, sections, subjects, shifts, holidays, academic year, settings, branding, integrations |
| Terms, score setups, grade scales | Built | Each school year has terms (3 by default, from the `terms_per_year` setting). Admins choose score parts (CA1, CA2, CA3, assignment, exam ... adding up to 100) and grade scales (WAEC A1–F9 default, simple A–F preset), per school, per class, and per subject for score parts. Pages: `/school/academics/terms`, `/school/academics/assessment` |
| Students | Built | Admission, profile, documents (private disk) |
| Admissions CRM, visitor log | Built | Inquiry → follow-ups. No automatic "turn an inquiry into a student" step |
| Staff & HR | Built | Staff, departments, designations, documents, leave, salary structure, payroll, payslip PDF |
| Attendance | Built | Student and staff, daily marking, calendar |
| Timetable | Built | |
| Exams (single marks) | Built | The older per-exam marks screen still works (one number per student/subject/exam), graded with the class's scale. Parents and students only see marks from exams marked published or completed |
| Term results | Built | `/school/results`: scores entered per score part (CA1, CA2, Exam ...) per class and term (`subject_scores`), stored in `term_results` / `term_result_summaries` with subject and class positions (ties share a place), averages, highest/lowest, and a version number. Worked out again on every save (`TermResultService`). Each class/term is a `result_sheet` with steps draft → submitted → approved → published → locked (`ResultSheet::ACTIONS`; submit needs `marks.entry`, approve/publish `results.publish`, lock `results.lock`). Scores only change in draft. Behaviour (affective) and skills (psychomotor) ratings 1–5. Portals show only published/locked results. No report card PDF yet (Phase 3) |
| Fees | Built | Two systems side by side for now. **New:** `/school/fees/invoices`: invoices (one per student, per fee structure, per period, made for a whole class at once) with a ledger underneath (`ledger_entries`: charge, fine, payment, discount, reversal). Ledger lines can never be edited or deleted (the model throws); a mistake is fixed by a reversal line. Balance and status are worked out from the ledger (`FeeLedgerService`). Overpaying is refused; an invoice can be cancelled only while nothing is paid. `/school/fees/scholarships`: named discounts (percent or fixed, all fees or one category), given to a student with the approver and reason recorded, applied to open and future invoices. Permissions: view `fees.view`, bill `fees.structure`, pay/fine `fees.collect`, reverse/cancel/scholarships `fees.waiver`. Dashboards, the finance report, the custom report and both portals read invoices and the ledger. **Old:** `fee_payments` was copied into invoices by migration `2026_10_14_000002` (copied lines carry `legacy_fee_payment_id`; `php artisan fees:copy-old-payments [--dry-run] [--undo]`; rolling back that migration undoes it but keeps invoices staff have added to since). The old rows are kept read-only under "Old Payment Records"; the old Collect and Outstanding screens now redirect to Invoices. Demo seeders write old rows and then run the copy. No payment gateway |
| Library, Inventory/Assets, Transport, Hostel | Built | Transport has a GPS location webhook protected by a per-vehicle token (no screen shows the token yet) |
| Homework, lesson plans, syllabus, online-class links | Built | |
| Communication | Partly | Announcements, messages, email templates. **SMS is a stub** (logs only). No delivery log |
| Reports | Built | Dashboard, attendance, academic, finance, custom builder, audit log, PDF/CSV exports |
| Student and Parent portals | Built | Read-only views |
| Mobile PWA & public API | Not built | Module 15 was removed from scope |

---

## Rules for every session

1. **Read the module spec** in `requirements/modules/` first, but treat it as the goal; check the code for what exists.
2. **Every table that belongs to a school** has an indexed `school_id` (soft deletes on major tables) and its model uses `BelongsToSchool`. A test (`TenantCoverageTest`) fails if a new model with `school_id` skips the rule. Only four models are allowed to skip it (listed with reasons in that test).
3. **Every route under `/school` needs a `permission:` check** in `routes/web.php`. A test (`RoutePermissionTest`) fails if you add one without it. Pick the permission from `RolePermissionSeeder`; add a new permission there if none fits, and tell the user to re-run the seeder on deploy.
4. **Super Admin** passes every permission check (`Gate::before` in `AppServiceProvider`).
5. **Audit trail:** fee payments, marks, payroll and users are logged automatically; every log entry is stamped with the school. Role changes are logged by hand where they happen. Never log passwords.
6. **Inertia first:** pages get their data from the controller, not from a separate API call.
7. **No N+1 queries:** eager load with `with()`/`load()`. Computed values on `Mark` (`percentage`, `is_pass`, `total_marks`) need the `subject` relation loaded.
8. **TypeScript types** for page props live in `resources/js/Types/`. Avoid `any`.
9. **Zustand only for truly global state.** Local state stays in `useState`.
10. **Security defaults:** Eloquent only (no raw SQL); no `dangerouslySetInnerHTML`; validate uploads (type + size); uploaded documents go on the `private` disk and are served through a route that checks the person's school and permission.
11. **Slow work:** PDF, imports and message blasts currently run inside the request or the stub job. Moving them to the queue is Phase 4 work; do not pretend it is done.
12. **Keep this file true.** If you change the stack, add a module or fix a caveat above, update the table.

---

## Running things

```bash
composer install            # needs PHP 8.4 (see above)
npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan test            # PHPUnit; uses in-memory SQLite and a built-in test app key
```

There is no `npm run type-check`, no lint script and no frontend test runner yet.

### Environment variables that matter

| Variable | Purpose |
|---|---|
| `APP_URL`, `APP_KEY`, `APP_DEBUG` | Basics |
| `DB_*` | Database (SQLite locally, Supabase Postgres in production) |
| `SUPABASE_URL`, `SUPABASE_ANON_KEY`, `SUPABASE_SERVICE_ROLE_KEY` | Login checks |
| `FILESYSTEM_DISK`, `SUPABASE_STORAGE_*` | Where uploads go. With a bucket set, the `private` disk also uses it |
| `QUEUE_CONNECTION` | `sync` by default; use `database` with the worker |
| `SHOW_DEMO_ACCOUNTS` | `true` only on demo sites to show one-click demo logins |

### Safety-net tests (`tests/Feature/Security`)

School separation (models and by-id access), route permissions per role, user-account
permissions, login throttle, demo-login flag, private documents, GPS token, audit trail,
and the academic report. Run them before and after any change to routes, models or permissions.
`tests/Feature/CalculationsTest.php` checks grade and fee-balance sums at the edges (decimal scores on a boundary, kobo payments, rounding); `FeeLedgerTest` and `LegacyFeeImportTest` cover invoices and the copy from old records.

---

## Known gaps (planned work)

Phase 2 leftovers: old per-exam marks are not copied into term results.
Phase 3: admission-to-enrolment, promotion, report cards, real SMS, payment gateway, CBT.
Phase 4: queue for PDFs, Redis, safe ID generation (admission numbers and employee IDs are
made by counting rows, which can collide), splitting the fat controllers into services.
Small open items: the Receptionist, Driver, Warden and Store Manager roles exist in the seeder but the `/school` route group only lets six roles in (Super Admin, School Admin, Principal, Teacher, Accountant, Librarian), so those four cannot reach any school screen yet; the side menu is chosen by role in the frontend, so it can show items a role
can no longer open; a screen to show each vehicle's GPS token; no CI workflow.
