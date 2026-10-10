# SchoolRuns School Management System — Requirements Overview

**Version:** 1.0.0
**Status:** Built and running; this file was corrected in October 2026 to match the code
**Date:** 2026

> **Read this first.** This overview used to describe an earlier plan (Laravel 11, Redis,
> Horizon, Sanctum, Stripe, 2FA, a `/api/v1` API, a mobile app). Those were **not built**.
> The tables below now describe what exists. The module files in `./modules/` are still the
> original specs: use them as the *goal* for each area, and check `CLAUDE.md` ("What is built")
> and the code before assuming a feature exists.

---

## Project Summary

A fully-featured, production-ready, free open-source School Management System (SMS) built with a modern Laravel + React stack. Designed to attract inbound leads for xgenious custom development services while supporting schools of all sizes.

## Goals
- Provide a complete, production-ready SMS as a free open-source product
- Attract inbound leads for xgenious custom development services
- Support primary, secondary, and college-level schools
- Enable white-label deployment for agency clients
- Build with modern, maintainable stack

## Tech Stack (what is actually used)

| Layer | Technology | Purpose |
|---|---|---|
| Backend | Laravel 13 (PHP 8.4 in practice) | Pages, login handling, business logic, jobs |
| Frontend | React 19 + TypeScript, Vite, Tailwind CSS 4 | Pages served through Inertia.js |
| Bridge | Inertia.js 3 | Server-driven pages — no separate page API |
| State | Zustand | Small global client state (signed-in user, UI, attendance) |
| UI | shadcn-style components on Base UI | Accessible components, dark mode |
| Login | Supabase (password check) + Laravel session | Supabase confirms the password; Laravel decides access |
| Permissions | Spatie laravel-permission | Roles and per-action permissions |
| Audit | Spatie laravel-activitylog | Who changed fees, marks, payroll, users |
| Database | Supabase Postgres (production), SQLite (local/tests) | Primary store |
| Cache / sessions / queue | Database drivers (queue is `sync` unless changed) | No Redis |
| Storage | Laravel Storage — `supabase` (S3 style) and a `private` disk for documents | Files |
| PDF | Laravel DomPDF | Receipts, payslips, exports (generated in the request) |
| Hosting | Railway | `railway.json`, `Procfile` |
| Testing | PHPUnit | Backend only; no frontend tests, no CI yet |

## Core Modules (19 Total)

> Modules 16–19 added after PDF design chart analysis.

| # | Module | Sprint | Phase |
|---|---|---|---|
| 01 | [Authentication & Access Control](./modules/01-authentication-access-control.md) | Sprint 1–2 | Phase 1 |
| 02 | [School Setup & Configuration](./modules/02-school-setup-configuration.md) | Sprint 3 | Phase 2 |
| 03 | [Student Management](./modules/03-student-management.md) | Sprint 4 | Phase 2 |
| 04 | [Staff & HR Management](./modules/04-staff-hr-management.md) | Sprint 5, 11 | Phase 2, 4 |
| 05 | [Attendance Management](./modules/05-attendance-management.md) | Sprint 6 | Phase 3 |
| 06 | [Timetable & Scheduling](./modules/06-timetable-scheduling.md) | Sprint 7 | Phase 3 |
| 07 | [Examination & Results](./modules/07-examination-results.md) | Sprint 8 | Phase 3 |
| 08 | [Fee Management](./modules/08-fee-management.md) | Sprint 9–10 | Phase 4 |
| 09 | [Library Management](./modules/09-library-management.md) | Sprint 12 | Phase 5 |
| 10 | [Transport Management](./modules/10-transport-management.md) | Sprint 13 | Phase 5 |
| 11 | [Homework & Lesson Planning](./modules/11-homework-lesson-planning.md) | Sprint 14 | Phase 5 |
| 12 | [Communication](./modules/12-communication.md) | Sprint 15 | Phase 6 |
| 13 | [Reports & Analytics](./modules/13-reports-analytics.md) | Sprint 16 | Phase 6 |
| 14 | [System Administration](./modules/14-system-administration.md) | Sprint 17 | Phase 7 |
| 15 | [Mobile PWA & API](./modules/15-mobile-pwa-api.md) | Sprint 17 | Phase 7 |
| 16 | [Admission Inquiry & Visitor Management](./modules/16-admission-inquiry-crm.md) | Sprint 4B | Phase 2 |
| 17 | [Hostel Management](./modules/17-hostel-management.md) | Sprint 13B | Phase 5 |
| 18 | [Inventory & Asset Management](./modules/18-inventory-asset-management.md) | Sprint 12B | Phase 5 |
| 19 | [Subscription & Package Management](./modules/19-subscription-package-management.md) | Sprint 17B | Phase 7 |

## Where the project stands

Modules 01–14 and 16–19 were built across the original sprints (see `CLAUDE.md` for the
per-area status and caveats). Module 15 (Mobile PWA & API) was removed from scope.
The online payments sprint (Sprint 10), the API and PWA work, the Vitest/Playwright/CI test setup
and the Docker launch pieces from the original roadmap were **not delivered**.

Since then, the work is organised in four phases (checklist kept with the project files):

| Phase | Goal | State |
|---|---|---|
| 1 Foundation | Close security gaps (school separation, permissions, login limits, private files, audit trail), fix broken report, correct these docs | Done |
| 2 Nigerianization | Terms, continuous assessment, stored results with positions, invoices and payment ledger | Next |
| 3 Education OS | Admission-to-enrolment, promotion, report cards, SMS, payment gateway, CBT | Planned |
| 4 Scale readiness | Queue PDFs, Redis, safe ID generation, split fat controllers into services | Planned |

## Non-Functional Requirements Summary

### Performance
- Inertia page load: < 500ms (LAN), < 2s (3G)
- API response: < 200ms (CRUD), < 800ms (reports)
- No N+1 queries; all FK indexed
- PDF generation: async queue

### Security
- CSRF on all state-changing requests
- Eloquent parameterized queries only (no raw SQL injection risk)
- React default XSS escaping (no dangerouslySetInnerHTML)
- File uploads: MIME validated, stored outside webroot
- No public API exists yet (target only)
- 2FA: **not built** (target)
- Audit log: built for fee payments, marks, payroll, user changes and role changes
- Login: 5 wrong tries per email + device, then a short wait
- Every `/school` screen is guarded by a specific permission

### Scalability
- **Today:** database sessions/cache/queue, one app server on Railway, Supabase storage. Not ready for many busy schools yet.
- **Target (Phase 4):** Redis for cache/sessions/queue, real queue workers, PDFs on the queue.

### Accessibility
- shadcn/ui ARIA-compliant components
- Keyboard navigation for all primary workflows
- WCAG AA color contrast (>= 4.5:1)

### i18n
- Laravel lang files + react-i18next
- Initial languages: English, Bengali
- RTL support (Arabic, Urdu) via Tailwind RTL plugin

## Architecture Principles
- **Single-folder monolith**: `app/Http/Controllers/{SchoolAdmin,SuperAdmin,...}`, flat `app/Models`. The `app/Modules/*` layout in the old plan was never used; moving to services is Phase 4 work
- **Multi-tenancy**: `school_id` + the `SchoolScope` rule on every school-owned model (four platform-level models are the documented exceptions)
- **Inertia-first**: pages served via Inertia; there is no REST API
- **Queue slow work (target):** today PDFs run inside the request and message blasts use a stub job. Moving them to a real queue is Phase 4
