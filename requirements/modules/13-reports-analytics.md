# Module 13 — Reports & Analytics

## Overview
Role-specific dashboards, attendance/academic/financial reports, a custom report builder, and full audit logs.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Dashboard | Role-specific KPI dashboard (counts, charts, recent activity) | Recharts, Zustand |
| Attendance Reports | Daily/monthly/annual attendance % per class/student/staff | Aggregated queries |
| Academic Reports | Class performance, subject-wise analysis, pass/fail ratio | Chart + table |
| Financial Reports | Revenue (fees), expenses, outstanding, collection efficiency | Multi-period compare |
| Custom Reports | Report builder: choose fields, filters, grouping; export CSV/PDF | Dynamic query builder |
| Audit Logs | Full audit trail: who did what and when; filterable | spatie/activitylog |

## Additional Reports (from PDF design chart)

The following specific report types must be available, each as a separate exportable PDF/CSV:

### Exam & Academic
| Report | Description |
|---|---|
| Online Exam Report | Results/submissions from online exams |
| Progress Card Report | Full progress card per student per term |
| Progress Card 100% | Normalized progress card showing % of max marks |
| Previous Result | Historical results from prior academic years |
| Mark Sheet Report | Detailed subject-wise marksheet |
| Merit List Report | Class/section-wise ranked list |
| Tabulation Sheet | Full class marks grid (subject × student) |

### Attendance
| Report | Description |
|---|---|
| Student Attendance Report | Daily/monthly/annual per student or class |
| Subject Attendance Report | Attendance broken down per subject period |
| Student Login Report | Log of student portal login activity |

### Finance
| Report | Description |
|---|---|
| Fees Due Report | Outstanding fee per student/class/date |
| Fine Report | Fine charges applied and collection status |
| Payment Report | Fee payments by date/method/student |
| Balance Report | School account balance over time |
| Waiver Report | All waivers granted with reasons |
| Wallet Report | Student wallet top-ups and deductions |
| Payroll Report | Staff salary summary per month |
| Transaction Report | Full income/expense transaction ledger |

### Other
| Report | Description |
|---|---|
| Homework Evaluation Report | Homework assigned vs submitted vs reviewed per class/teacher |
| Guardian Reports | Guardian contact list, linked students, communication log |
| Student History | Full per-student history: class progression, results, attendance, fees |
| Student Transport Report | Students assigned to routes, vehicle, pickup stop |
| Student Dormitory Report | Students assigned to hostel rooms |
| Class Report | Class summary: student count, attendance %, fee collection, exam performance |

## Dashboard KPIs by Role

### Super Admin
- Total schools, active students, total revenue
- System health (queue status, storage usage)

### School Admin / Principal
- Student count, staff count, today's attendance %
- Fee collection this month vs target
- Recent activity feed

### Teacher
- My classes today, homework pending review
- My students' attendance this week

### Accountant
- Daily collection, outstanding balance, upcoming dues
- Expense vs revenue chart

### Student
- Attendance %, recent exam results, pending fees, homework due

### Parent
- Child's attendance %, last exam results, fee status

## Custom Report Builder
- Choose entity (students, attendance, marks, fees, staff)
- Select fields to display
- Apply filters (date range, class, section, status)
- Choose grouping (class, month, subject)
- Export to CSV or PDF

## Sprint
**Phase 6 — Sprint 16**
- Custom report builder, all chart dashboards per role, audit log UI, export PDF/CSV for all reports
