# Module 02 — School Setup & Configuration

## Overview
Manages school profile, academic year, class/section structure, subjects, shifts, holidays, and service configuration.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| School Profile | Name, logo, address, contact, academic year, timezone, currency, language | Polymorphic settings table |
| Academic Year | Create/switch academic years; data is scoped per year | Global scope |
| Classes & Sections | Create classes (Grade 1–12, etc.), sections (A/B/C), capacity | Nested resource |
| Subjects | Subject name, code, type (theory/practical), assign to class, optional teacher | Pivot table |
| Subject Groups | Academic stream grouping: Science / Arts / Commerce; assign subjects per group | Group model, pivot |
| Shifts | Morning/evening/full-day shifts, time ranges | Enum or model |
| Holidays | Define holidays, recurring events; affects attendance calendar | Carbon date logic |
| SMS/Email Config | SMTP, Twilio/Vonage gateway config; test message button | Env-backed settings |
| Payment Gateway | Stripe / manual cash; enable per school | Conditional service |

## Database Tables

### classes
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| name | string | e.g., "Grade 1" |
| numeric_name | integer | |
| capacity | integer | |
| class_teacher_id | FK | Staff |

### sections
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| class_id | FK | |
| name | string | e.g., "A", "B" |
| capacity | integer | |

### subjects
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| class_id | FK | |
| name | string | |
| code | string | |
| type | enum | theory / practical |
| pass_marks | integer | |
| full_marks | integer | |

### academic_years
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| name | string | |
| start_date | date | |
| end_date | date | |
| is_current | boolean | Scopes most queries |

## Multi-Tenancy
- Every table includes `school_id` FK with index
- Global Eloquent scope enforces `school_id` on every query automatically
- Each school can have its own subdomain or path prefix (configurable via `MULTITENANCY_MODE`)

## Sprint
**Phase 2 — Sprint 3**
- School profile, classes, sections, subjects, shifts, holidays UI + API + Zustand
