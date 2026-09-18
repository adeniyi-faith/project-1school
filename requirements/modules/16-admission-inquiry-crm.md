# Module 16 — Admission Inquiry & Visitor Management

## Overview
Pre-admission CRM for tracking enquiries from first contact through enrollment, plus a physical visitor logbook for the school reception.

> **Source:** PDF design chart — "Administration" section. This module was entirely absent from the original SRS.

---

## Features

### Admission Inquiry (CRM)

| Feature | Description | Tech Notes |
|---|---|---|
| Public Admission Form | Online form accessible via a public URL (no login required) | Public Inertia page, CSRF protected |
| Inquiry Tracking | Track each inquiry through statuses: New → Follow-up → Admitted | State machine / enum |
| Inquiry List | Admin/Receptionist sees paginated inquiry list, filter by status/class/date | DataTable |
| Follow-up Notes | Add notes and next follow-up date against each inquiry | Polymorphic notes |
| Convert to Admission | One-click to convert an approved inquiry into a full student admission | Calls student admission service |
| Inquiry Report | Summary: total inquiries, conversion rate, by class/month | Aggregated query |

**Inquiry Status Flow:**
```
New → Follow-up → Admitted
           ↓
        Dropped
```

### Visitor Book (Reception Log)

| Feature | Description | Tech Notes |
|---|---|---|
| Visitor Entry | Log visitor: Name, Phone, Purpose, Person to Meet, Time In | Simple form |
| Time Out | Mark visitor exit time | Timestamp update |
| Search & Filter | Search by name, date, purpose | Scoped query |
| Visitor Report | Daily/monthly visitor count, by purpose | Aggregated query |

---

## Database Tables

### admission_inquiries
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| student_name | string | Prospective student |
| class_interested | string | Grade applying for |
| guardian_name | string | |
| guardian_phone | string | |
| guardian_email | string | Nullable |
| status | enum | new / follow_up / admitted / dropped |
| notes | text | Latest follow-up note |
| next_followup_date | date | Nullable |
| source | string | Walk-in / online / referral |
| converted_student_id | FK | Nullable — set when converted |

### inquiry_followups
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| inquiry_id | FK | |
| staff_id | FK | Who followed up |
| note | text | |
| next_date | date | |

### visitor_logs
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| name | string | |
| phone | string | |
| purpose | string | |
| person_to_meet | string | Staff/teacher name |
| time_in | timestamp | |
| time_out | timestamp | Nullable |
| remarks | text | Nullable |

---

## Roles with Access
- **Receptionist** — primary user for both inquiry tracking and visitor log
- **School Admin** — full access including reports and conversion
- **Principal** — read-only reports

---

## Sprint
**Phase 2 — Sprint 4B** (extend Sprint 4 or add as separate sprint alongside student management)
