# Module 04 — Staff & HR Management

## Overview
Covers staff registration, leave management, attendance, payroll, payslips, departments, designations, and document management.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Staff Registration | Personal info, department, designation, joining date, documents, photo | Similar to student admission |
| Staff ID Card | Generate printable PDF staff ID card with photo, barcode/QR, designation | DomPDF + barcode lib |
| Staff List | Search, filter by department/designation, export, bulk actions | Reusable DataTable component |
| Leave Management | Leave types, apply leave, approval workflow (staff → principal → admin) | State machine |
| Attendance (Staff) | Mark daily attendance, late/early mark, monthly report | Shared attendance service |
| Payroll | Salary structure (basic, allowances, deductions), monthly payslip generation | Calculated at runtime |
| Payslip | PDF payslip download, email dispatch, salary history | Queue + DomPDF |
| Departments | Create departments, assign HOD, staff count display | Simple CRUD |
| Designations | Create designations per department | Simple CRUD |
| Experience/Cert | Upload certifications, experience letters, performance notes | Polymorphic docs |
| Performance Tracking | Periodic performance evaluation records per staff; ratings, remarks, reviewer | Evaluation model |
| Attendance-based Salary | Deduct salary proportionally based on attendance days/leave taken | Computed during payroll generation |

## Database Tables

### staff
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| user_id | FK | Linked user account |
| emp_id | string | Employee ID |
| department_id | FK | |
| designation_id | FK | |
| joining_date | date | |
| salary_type | enum | fixed / hourly |
| status | enum | active / resigned / terminated |

## REST API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| GET | /api/v1/staff | List staff (filter: dept, designation, status) | Admin |
| POST | /api/v1/staff | Register new staff member | Admin |
| GET | /api/v1/staff/{id} | Staff profile | Admin, Staff(own) |
| POST | /api/v1/payroll/generate | Generate monthly payroll for all staff | Admin, Accountant |
| GET | /api/v1/payroll/{staff_id}/{month} | Get payslip for staff/month | Admin, Staff(own) |
| GET | /api/v1/payroll/{id}/download | Download payslip PDF | Admin, Staff(own) |
| POST | /api/v1/leave/apply | Staff applies for leave | Staff |
| PUT | /api/v1/leave/{id}/approve | Approve/reject leave | Admin, Principal |

## Leave Workflow
1. Staff applies → status: `pending`
2. Principal reviews → status: `approved` / `rejected`
3. Admin has override capability at any stage
4. Notifications sent at each status change

## Payroll Structure
- Basic salary
- Allowances (configurable per staff)
- Deductions (tax, loans, etc.)
- Net salary calculated at runtime each month
- PDF payslip dispatched via queue + email

## Sprint
- **Phase 2 — Sprint 5**: Staff registration, staff list, departments, designations, staff profile
- **Phase 4 — Sprint 11**: Leave types, apply/approve workflow, payroll structure, monthly payroll generation, payslip PDF, email dispatch
