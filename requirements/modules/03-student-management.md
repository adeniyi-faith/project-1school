# Module 03 — Student Management

## Overview
Full lifecycle management for students: from admission through promotion, ID cards, document management, and alumni handling.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Admission | Multi-step form: personal info, guardian info, previous school, documents upload, class assignment | Wizard component, file upload |
| Student Category | Classify student as General / Disabled / Quota; affects fee discounts and reports | Enum column on students table |
| Student List | Filterable/searchable table with export (CSV, Excel, PDF); bulk actions | Tanstack Table, Laravel Excel |
| Student Profile | Full profile view: personal, academic history, attendance summary, fee status, siblings | Tabbed layout |
| ID Card | Generate printable student ID card (PDF) with photo, barcode, QR | DomPDF + barcode lib |
| TC / Leaving | Transfer certificate generation, mark student as alumni/transferred | Status enum |
| Bulk Import | Import students via Excel/CSV with validation and error report | Laravel Excel + job |
| Document Vault | Upload/manage student documents (birth cert, marksheet, photos) | S3 storage |
| Promote Students | End-of-year bulk promotion to next class; handle failures/repeats | Transaction + queue |
| Student History | Full academic history across years: class progression, previous results, transfers | Aggregated from multiple tables |

## Database Tables

### students
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| user_id | FK | Linked user account |
| admission_no | string | Unique per school |
| class_id | FK | |
| section_id | FK | |
| guardian_id | FK | |
| roll_no | string | |
| admission_date | date | |
| status | enum | active / alumni / transferred |

### guardians
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| name | string | |
| relation | string | Father / Mother / Guardian |
| phone | string | |
| email | string | |
| occupation | string | |
| address | text | |
| user_id | FK | Linked parent portal account |

## REST API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| GET | /api/v1/students | Paginated list (filter: class, section, status, search) | Admin, Teacher |
| POST | /api/v1/students | Create new student admission | Admin |
| GET | /api/v1/students/{id} | Student full profile | Admin, Teacher, Parent(own) |
| PUT | /api/v1/students/{id} | Update student details | Admin |
| DELETE | /api/v1/students/{id} | Soft-delete (mark as left/transferred) | Admin |
| POST | /api/v1/students/import | Bulk import via Excel/CSV upload | Admin |
| GET | /api/v1/students/{id}/idcard | Generate & return ID card PDF | Admin |
| POST | /api/v1/students/promote | Bulk promote students to next class | Admin |

## Storage
- Documents stored in S3-compatible storage (MinIO self-hosted / AWS S3)
- Files stored outside webroot with MIME validation
- Optional virus scan hook (ClamAV)

## Sprint
**Phase 2 — Sprint 4**
- Student admission wizard, student list (DataTable), student profile tabs, document upload, bulk import, ID card PDF
