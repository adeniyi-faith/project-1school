# Module 17 — Hostel Management

## Overview
Dormitory/boarding management: hostel buildings, room allocation, student assignment, hostel attendance, and fee integration.

> **Source:** PDF design chart — "Hostel Management" section. Entirely absent from the original SRS.

---

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Hostel Setup | Create hostels (name, type: boys/girls, warden assignment) | Simple CRUD |
| Room Management | Define rooms per hostel (room no, floor, type, capacity, AC/non-AC) | Nested resource |
| Room Allocation | Assign student to a specific room/bed; track occupancy | Pivot: student_hostel_rooms |
| Bed Management | Track individual beds per room; available/occupied status | Bed model |
| Student Assignment | Assign/remove students; record joining and leaving dates | Status + date |
| Hostel Attendance | Daily attendance for hostel students (separate from class attendance) | Extends AttendanceService |
| Hostel Fees | Link hostel stay to fee category; generate hostel fee bills | Fee module integration |
| Warden Portal | Warden sees their hostel's students, can mark attendance | Restricted role view |
| Hostel Reports | Occupancy report, vacant rooms, student list per hostel/room | Aggregated query |
| Student Dormitory Report | Full report of which students are in which hostel/room | Export PDF/CSV |

---

## Database Tables

### hostels
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| name | string | e.g., "Boys Hostel Block A" |
| type | enum | boys / girls / mixed |
| warden_id | FK | Staff |
| address | text | |
| total_rooms | integer | |
| total_capacity | integer | |
| status | enum | active / inactive |

### hostel_rooms
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| hostel_id | FK | |
| school_id | FK, indexed | |
| room_no | string | |
| floor | string | |
| type | enum | single / double / dormitory |
| capacity | integer | Number of beds |
| ac | boolean | |
| status | enum | available / full / maintenance |

### hostel_allocations
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| hostel_id | FK | |
| room_id | FK | |
| student_id | FK | |
| bed_no | string | |
| joining_date | date | |
| leaving_date | date | Nullable |
| status | enum | active / left |
| fee_linked | boolean | Linked to hostel fee category |

---

## Fee Integration
- Hostel fee is a fee category in Module 08
- When a student is assigned to a hostel, the hostel fee is automatically added to their fee ledger
- Fee amount can vary by room type (single/double/dormitory, AC/non-AC)

## Reports Added to Module 13
- Student Dormitory Report (listed in PDF report list) — student + hostel + room details

---

## Roles with Access
- **Warden** (new role) — own hostel attendance and student list
- **School Admin** — full access, room allocation, reports
- **Accountant** — hostel fee collection view

---

## Sprint
**Phase 5 — Sprint 13B** (alongside or after Transport, Sprint 13)
