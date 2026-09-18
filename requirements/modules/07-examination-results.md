# Module 07 — Examination & Results

## Overview
Full exam lifecycle: setup, marks entry, grading, report cards, merit lists, tabulation sheets, and progress charts.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Exam Setup | Create exam (Mid-term, Final, Unit Test), assign to class/subject, set marks | Polymorphic exam model |
| Marks Entry | Subject-teacher enters marks online; validation (max marks, grace) | Real-time save (Inertia) |
| Grading System | Configurable grade scale (A+/A/B... or GPA); school-specific | Strategy pattern |
| Report Card | Auto-generate PDF report card per student with grade, rank, remarks | DomPDF template |
| Merit List | Class-wise rank list; export PDF | ORDER BY aggregate |
| Tabulation Sheet | Full class marks sheet (subject-wise) for teachers/principal | Pivot export |
| Progress Chart | Student's performance trend across exams (line chart) | Recharts |
| Marksheet | Individual detailed marksheet PDF with school letterhead | DomPDF |
| Marks Bulk Import | Upload marks via Excel/CSV per subject; validation + error report | Laravel Excel + job |
| Term-wise Weight | Configure % weightage per exam term (e.g., Mid 40% + Final 60%) | JSON weight config per exam group |
| Bangladesh GPA | GPA calculation per Bangladesh Education Board rules; configurable grade scale | Strategy pattern (BD Board preset) |
| Optional Subject | 4th/optional subject auto-calculation; additional GPA add logic | Subject flag + calculation service |
| Section-wise Ranking | Merit list broken down by section; tie-break rules configurable | ORDER BY with tiebreak |
| Admit Card | Generate exam-wise admit cards per student (PDF, with photo, exam schedule) | DomPDF + queued |
| Seat Plan | Room-wise seat allocation for exam hall; printable seating chart | Seat allocation model |
| Exam Hall Attendance | Subject-wise exam attendance sheet; mark present/absent per exam session | Extends attendance service |
| Result Lock / Unlock | Admin locks result after publish to prevent edits; unlock for corrections | Status guard on marks table |
| Publish Result | Controlled result publishing; students/parents see results only after publish | published_at timestamp |
| Fail / Improvement | Track failed subjects; flag students needing improvement exams | Computed from marks |
| Previous Result | View historical results from prior academic years | Cross-year query |
| Online Exam | Basic online exam: question bank, MCQ/descriptive, auto-marking for MCQ | Question model + submission |

## Exam Logistics (New Sub-Section)

### Admit Card
- Generated per student per exam
- Includes: student photo, name, roll no, exam schedule (subject + date + time + room)
- PDF queued via Horizon; bulk generate for entire class

### Seat Plan
- Admin assigns students to rooms
- Configurable: alphabetical, roll-no based, or random
- Printable seating chart PDF per room

### Exam Hall Attendance
- Separate from regular class attendance
- Subject-wise sheet: teacher/invigilator marks present/absent per session
- Feeds into marks entry (absent students automatically flagged)

## Database Tables

### exams
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| academic_year_id | FK | |
| name | string | Mid-term, Final, Unit Test |
| type | enum | |
| class_id | FK | |
| start_date | date | |
| end_date | date | |
| status | enum | draft / published / completed |

### marks
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| exam_id | FK | |
| student_id | FK | |
| subject_id | FK | |
| marks_obtained | decimal | |
| grade | string | Calculated from grading system |
| is_absent | boolean | |
| remarks | text | |
| UNIQUE | | exam + student + subject |

### exam_admit_cards
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| exam_id | FK | |
| student_id | FK | |
| roll_no | string | Exam-specific roll |
| room_id | FK | Nullable |
| seat_no | string | Nullable |
| pdf_path | string | S3 path |

### exam_seat_plans
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| exam_id | FK | |
| room | string | |
| student_id | FK | |
| seat_no | string | |

## REST API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| GET | /api/v1/exams | List exams for academic year | All authenticated |
| POST | /api/v1/exams | Create exam | Admin |
| GET | /api/v1/exams/{id}/marks | Get marks for exam (class-wise or student-wise) | Admin, Teacher |
| POST | /api/v1/exams/{id}/marks | Bulk submit marks for a subject | Teacher (own subjects) |
| GET | /api/v1/students/{id}/results | Student's full result history across exams | Admin, Student(own), Parent |
| GET | /api/v1/students/{id}/reportcard | Generate PDF report card | Admin |

## Zustand Store
- `useExamStore` — state: `currentExam, marksBuffer{}`
- Actions: `setExam, updateMark, submitMarks`

## PDF Generation
- Report cards, marksheets generated via DomPDF
- Generation is queued (async) — user receives download link when ready
- School letterhead applied from school profile settings

## Sprint
**Phase 3 — Sprint 8**
- Examination setup, marks entry, grading system config, report card PDF, merit list, tabulation sheet, progress chart
