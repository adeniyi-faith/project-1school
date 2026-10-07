# Module 09 — Library Management

## Overview
Book catalog with ISBN lookup, member management (students/staff), issue/return workflow, reservations, fines, and optional e-library.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Book Catalog | Add books (ISBN, author, category, copies, location, cover image) | External ISBN lookup API |
| Member Management | Students/staff as members; membership card | Polymorphic members |
| Issue & Return | Issue book to member; due date; return and fine calculation | Status machine |
| Reservations | Reserve unavailable book; auto-notify when available | Queue notification |
| Library Reports | Popular books, overdue list, member activity report | Aggregated query |
| E-Library | Upload e-books / PDFs accessible to students (optional) | S3 + permission guard |

## Database Tables

### books
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| isbn | string | |
| title | string | |
| author | string | |
| category | string | |
| publisher | string | |
| total_copies | integer | |
| available_copies | integer | Decremented on issue |
| cover | string | S3 path |

### book_issues (Polymorphic)
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| book_id | FK | |
| member_type | string | App\Models\Student or App\Models\Staff |
| member_id | BIGINT | Morphable ID |
| issued_date | date | |
| due_date | date | |
| returned_date | date | Null if not returned |
| fine | decimal | Calculated on return |

## Book Issue Workflow
1. Librarian selects book and member → status: `issued`
2. `available_copies` decremented
3. On return → `returned_date` set, fine calculated if overdue
4. `available_copies` incremented
5. If reservation exists → notification sent to waiting member

## Sprint
**Phase 5 — Sprint 12**
- Book catalog, ISBN lookup, issue/return, fine calculation, e-library upload
