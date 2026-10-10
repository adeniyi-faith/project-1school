# Module 08 — Fee Management

## Overview
Complete fee lifecycle: categories, structures, collection, receipts, online payments (Stripe), fines, reports, and expense tracking.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Fee Categories | Define fee types (tuition, transport, library, exam, hostel) | Polymorphic fee model |
| Fee Structure | Assign fee amounts per class/year; discount categories (sibling, merit) | Pivot with overrides |
| Fee Collection | Collect payment: cash/card/online; generate receipt PDF | Transaction + receipt |
| Payment History | Per-student payment history, outstanding balance | Aggregated query |
| Fine / Penalty | Add late fee fine; auto-calculate after due date | Scheduled job |
| Fee Reports | Collection report (daily/monthly), outstanding report, class-wise | Chart + table export |
| Online Payment | Stripe checkout integration; webhook to confirm payment | Stripe SDK + webhook |
| Bangladesh Gateways | bKash, Nagad, Rocket payment integration; webhook confirmation | Respective SDKs + webhook |
| Partial Payment | Allow partial fee payment; track remaining balance automatically | amount_paid vs amount_due |
| Installment System | Split a fee into installments with individual due dates and tracking | installment_plans table |
| Scholarship / Discount | Named scholarships and discount types (merit, sibling, need-based, quota) with % or fixed amount | Discount model + override |
| Student Wallet | Prepaid wallet per student; deduct fees from wallet balance | wallet_transactions table |
| Fee Waiver | Full or partial fee waiver by admin with reason; reflected in reports | Waiver model |
| Ledger System | Double-entry income/expense ledger; balance sheet per school | Ledger entries table |
| Expense Tracking | Record school expenses (utilities, supplies); expense report | Simple CRUD + report |

## Database Tables

### fee_structures
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| academic_year_id | FK | |
| class_id | FK | |
| fee_category_id | FK | |
| amount | decimal | |
| due_date | date | |
| frequency | enum | monthly / quarterly / annual / one-time |

### fee_payments
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| student_id | FK | |
| fee_structure_id | FK | |
| amount_paid | decimal | |
| payment_date | date | |
| method | enum | cash / card / online |
| receipt_no | string | Unique |
| status | enum | paid / partial / pending / overdue |

## REST API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| GET | /api/v1/fees/structures | List fee structures for academic year/class | Admin, Accountant |
| POST | /api/v1/fees/payments | Record a fee payment | Admin, Accountant |
| GET | /api/v1/fees/payments | List payments (filter: student, date, status) | Admin, Accountant |
| GET | /api/v1/students/{id}/fees | Student's fee ledger (paid, outstanding, upcoming) | Admin, Student(own), Parent |
| GET | /api/v1/fees/payments/{id}/receipt | Download fee receipt PDF | Admin, Accountant, Student |
| POST | /api/v1/fees/online/checkout | Create Stripe checkout session for online payment | Student, Parent |
| POST | /api/v1/fees/online/webhook | Stripe webhook receiver | Public (signature verified) |

## Zustand Store
- `useFeeStore` — state: `selectedStudent, paymentDraft`
- Actions: `setStudent, updateDraft, clearDraft`

## New Database Tables (Additions)

### student_wallets
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| student_id | FK | |
| balance | decimal | Current balance |

### wallet_transactions
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| wallet_id | FK | |
| type | enum | credit / debit |
| amount | decimal | |
| reference | string | Payment receipt / fee payment ID |
| note | text | |

### fee_waivers
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| student_id | FK | |
| fee_payment_id | FK | |
| waiver_type | enum | full / partial |
| amount | decimal | |
| reason | text | |
| approved_by | FK | User |

### installment_plans
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| fee_structure_id | FK | |
| student_id | FK | |
| installment_no | integer | 1, 2, 3... |
| amount | decimal | |
| due_date | date | |
| paid_at | timestamp | Nullable |
| status | enum | pending / paid / overdue |

## Stripe Integration
- Checkout session created server-side
- Webhook endpoint at `/api/v1/fees/online/webhook` receives Stripe events
- Webhook signature verified before processing
- Payment record updated on `checkout.session.completed` event

## Scheduled Jobs
- Late fee fine auto-calculated via scheduled job after due date passes
- Sends overdue notifications to students/parents

## Sprint
- **Phase 4 — Sprint 9**: Fee categories, fee structure, collection UI, receipt PDF, outstanding report, fine job
- **Phase 4 — Sprint 10**: Stripe online payment, webhook handler, payment confirmation, dashboard fee widget
