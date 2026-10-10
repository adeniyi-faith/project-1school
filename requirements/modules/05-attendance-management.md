# Module 05 — Attendance Management

## Overview
Daily attendance tracking for students and staff, with QR/barcode scan support, calendar views, reports, and automated parent notifications.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Student Attendance | Daily class-wise attendance (present/absent/late/half-day), bulk mark | Mass upsert, date pivot |
| Attendance Sheet | Monthly calendar view per student/class; color-coded status | React calendar component |
| QR / Barcode | Optional QR-code scan to mark attendance via mobile camera | jsQR library + API endpoint |
| Reports | Class-wise, student-wise, date-range reports; export PDF/CSV | Aggregated query + DomPDF |
| Notifications | Auto-notify parent via SMS/email when student is absent | Observer + notification |
| Staff Attendance | Same logic applied to staff; biometric import via CSV | Shared AttendanceService |

## Database Table

### attendances (Polymorphic)
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| academic_year_id | FK | |
| date | date | |
| attendable_type | string | App\Models\Student or App\Models\Staff |
| attendable_id | BIGINT | Morphable ID |
| status | enum | present / absent / late / half-day |
| remarks | text | Optional |

## REST API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| GET | /api/v1/attendance | List records (filter: date, class, section, student) | Admin, Teacher |
| POST | /api/v1/attendance | Mark attendance for a class on a date (batch upsert) | Teacher |
| GET | /api/v1/attendance/report | Attendance summary report (date range, class/student) | Admin, Teacher |
| GET | /api/v1/attendance/{student_id}/monthly | Calendar view for a student's monthly attendance | Admin, Parent(own) |

## Zustand Store
- `useAttendanceStore` — state: `currentDate, currentClass, markedStudents{}`
- Actions: `setDate, markStudent, submitBatch`

## Status Color Coding
- Present → Green
- Absent → Red
- Late → Orange/Yellow
- Half-Day → Blue

## Notification Trigger
- Observer on `Attendance` model
- If `status = absent` → fires `StudentAbsentNotification`
- Notification dispatched via queue to parent's SMS/email

## Sprint
**Phase 3 — Sprint 6**
- Daily mark (class-wise), calendar view, QR scan, parent SMS notification, staff attendance
