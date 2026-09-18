# xgenious School Management System — Requirements Overview

**Version:** 1.0.0
**Status:** Draft — Claude Code Ready
**Date:** 2026

---

## Project Summary

A fully-featured, production-ready, free open-source School Management System (SMS) built with a modern Laravel + React stack. Designed to attract inbound leads for xgenious custom development services while supporting schools of all sizes.

## Goals
- Provide a complete, production-ready SMS as a free open-source product
- Attract inbound leads for xgenious custom development services
- Support primary, secondary, and college-level schools
- Enable white-label deployment for agency clients
- Build with modern, maintainable stack

## Tech Stack

| Layer | Technology | Purpose |
|---|---|---|
| Backend | Laravel 11 (PHP 8.3) | REST API, Auth, Business Logic, Queue, Jobs |
| Frontend | React 18 + TypeScript | SPA via Inertia.js |
| Bridge | Inertia.js | Server-driven SPA — replaces traditional API responses for pages |
| State | Zustand | Global client state (auth user, notifications, UI) |
| UI | shadcn/ui + Tailwind CSS | Accessible component library, dark mode support |
| Database | MySQL 8 / PostgreSQL 16 | Primary relational store |
| Cache | Redis | Sessions, queue, rate limiting, real-time |
| Queue | Laravel Horizon | Email, SMS, PDF generation jobs |
| Storage | Laravel Storage (S3-compat) | Documents, avatars, reports |
| Auth | Laravel Sanctum + Spatie RBAC | API tokens + role/permission guard |
| PDF | Laravel DomPDF / Snappy | Report cards, fee receipts, payslips |
| Testing | PestPHP + Vitest + Playwright | Unit, integration, E2E |

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

## Development Roadmap

| Phase | Sprint | Focus |
|---|---|---|
| Phase 1 | Sprint 1 | Infrastructure: Laravel setup, MySQL, Redis, Sanctum, Spatie RBAC, Inertia + React + TS, shadcn/ui, Zustand, base layout, dark mode, auth pages |
| Phase 1 | Sprint 2 | Auth & Tenancy: Multi-school seed, super-admin scaffold, school CRUD, global school_id scope, activity log, role/permission seeder |
| Phase 2 | Sprint 3 | School Config: School profile, classes, sections, subjects, shifts, holidays |
| Phase 2 | Sprint 4 | Students: Admission wizard, student list, profile tabs, document upload, bulk import, ID card PDF |
| Phase 2 | Sprint 5 | HR Basic: Staff registration, staff list, departments, designations, staff profile |
| Phase 3 | Sprint 6 | Attendance: Daily mark, calendar view, QR scan, parent SMS notification, staff attendance |
| Phase 3 | Sprint 7 | Scheduling: Timetable builder (DnD), conflict detection, exam schedule, room management, iCal export |
| Phase 3 | Sprint 8 | Exams: Examination setup, marks entry, grading system, report card PDF, merit list, tabulation sheet |
| Phase 4 | Sprint 9 | Fees: Fee categories, structures, collection UI, receipt PDF, outstanding report, fine job |
| Phase 4 | Sprint 10 | Online Payments: Stripe integration, webhook handler, payment confirmation, dashboard widget |
| Phase 4 | Sprint 11 | HR Advanced: Leave workflow, payroll structure, monthly payroll generation, payslip PDF |
| Phase 5 | Sprint 12 | Library: Book catalog, ISBN lookup, issue/return, fine, e-library upload |
| Phase 5 | Sprint 13 | Transport: Routes, vehicle fleet, student assignment, driver portal, live tracking stub |
| Phase 5 | Sprint 14 | Academics: Homework assignment/submission, lesson plan, syllabus upload, online class links |
| Phase 6 | Sprint 15 | Communication: Announcements, messaging, noticeboard, SMS/email blast, push notifications, parent portal |
| Phase 6 | Sprint 16 | Reports: Custom report builder, all dashboards per role, audit log UI, export PDF/CSV |
| Phase 7 | Sprint 17 | API & PWA: PWA manifest, service worker, offline support, API keys, webhook config, Swagger docs |
| Phase 7 | Sprint 18 | Testing & CI: PestPHP unit tests, Vitest component tests, Playwright E2E, GitHub Actions CI/CD |
| Phase 7 | Sprint 19 | Optimization: N+1 fixes, index review, lazy loading, security hardening, Horizon tuning |
| Phase 7 | Sprint 20 | Launch Prep: README, Docker compose, update script, demo seeder, video walkthrough plan |

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
- API rate limit: 60 req/min per token
- 2FA enforced for Admin and Accountant roles
- Audit log for all financial and role changes

### Scalability
- Stateless app servers + Redis sessions (horizontal scaling)
- Horizon queue workers (auto-restart on failure)
- S3-compatible storage (MinIO self-hosted / AWS S3)
- Read replicas supported via Laravel DB::connection('read')

### Accessibility
- shadcn/ui ARIA-compliant components
- Keyboard navigation for all primary workflows
- WCAG AA color contrast (>= 4.5:1)

### i18n
- Laravel lang files + react-i18next
- Initial languages: English, Bengali
- RTL support (Arabic, Urdu) via Tailwind RTL plugin

## Architecture Principles
- **Modular monolith**: `app/Modules/{ModuleName}/{Controllers,Models,Services,Requests,Resources}`
- **Multi-tenancy**: `school_id` global scope on every model and query
- **Inertia-first**: pages served via Inertia; REST endpoints only for external API consumers and AJAX sub-requests
- **Queue everything**: PDF generation, bulk imports, SMS/email dispatched via Horizon
