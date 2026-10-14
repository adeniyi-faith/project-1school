# SchoolRuns

[![Tests and deploy](https://github.com/adeniyi-faith/project-1school/actions/workflows/deploy.yml/badge.svg?branch=main)](https://github.com/adeniyi-faith/project-1school/actions/workflows/deploy.yml)

**SchoolRuns is one website that runs a whole school.** Admissions, students, staff, attendance, timetables, exams, report cards, fees, the library, buses, hostels, homework and messages all live in one place. Many schools can use the same install, and each school only ever sees its own information.

It is built for Nigerian schools first: three terms a year, continuous assessment, WAEC-style report cards, and fees in naira with online payment. It also has setups for British, American and Montessori schools.

## The problem it solves

Many schools keep students in one notebook, fees in another, and results in a spreadsheet. Parents ask the same questions again and again. SchoolRuns puts it all in one system, with a separate view for each kind of person, so each one only sees what they need.

## What it can do

- **People and logins:** Super Admin, School Admin, Principal, Teacher, Accountant, Librarian, Receptionist, Driver, Warden, Store Manager, Student and Parent. Menus and buttons show only what that person is allowed to use.
- **Admissions:** inquiry, entrance exam, interview, offer, then enrolment in one step.
- **Results:** score parts that each school sets up, grade scales, positions, approval steps, report card PDFs (one student or a whole school as a ZIP), and broadsheets in PDF and Excel.
- **Computer-based tests:** question banks, class tests, entrance exams and printed exam papers.
- **Fees:** invoices, a payment record that cannot be edited, scholarships, paying in dated parts, and online payment with Paystack, Flutterwave, Monnify, Remita or Interswitch.
- **AI helpers (Google Gemini, optional):** report card comments, lesson notes and question drafts. Each school can switch AI off.
- **Also:** attendance, timetable, staff and payroll, library, inventory, transport, hostels, homework, certificates, student ID cards with QR scanning, SMS and email messages, and reports.

## Try it

Run it on your own computer (you need PHP 8.4, Composer and Node 20):

```bash
composer install && npm install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed && npm run build && php artisan serve
```

Then open the address it prints. Run the tests with `php artisan test`.

## Built with

Laravel 13 (PHP) for the server, React and TypeScript for the screens, [Inertia.js](https://inertiajs.com/) to join the two so there is one codebase, Tailwind CSS for styling, and MySQL in production (SQLite on your own computer). Permissions use Spatie laravel-permission. PDFs use DomPDF.

## Where things are

- [Architecture in plain English](docs/architecture.md)
- [User guides for each role](docs/index.md) and the [cPanel install guide](docs/cpanel-installation.md)
- [Original feature specs](requirements/00-overview.md)
- [Notes for people (and AI tools) changing the code](CLAUDE.md) and [CONTRIBUTING.md](CONTRIBUTING.md)

## How the live site is updated

Every change merged to `main` is tested by GitHub, and only if every test passes is it put on the live cPanel server. The old Railway files are kept in `deploy/old-railway/`.

## Licence

All rights reserved. The code is public to show how it was built, not to be reused. See [LICENSE](LICENSE).
