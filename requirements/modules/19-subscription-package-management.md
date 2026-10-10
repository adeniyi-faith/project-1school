# Module 19 — Subscription & Package Management (Super Admin)

## Overview
SaaS subscription lifecycle management for the Super Admin: define packages, assign modules per plan, manage school subscriptions, renewals, trials, and discount coupons.

> **Source:** PDF design chart — "Package & Subscription" + "Module Manager" sections. Absent from the original SRS (SRS mentioned billing conceptually but had no dedicated module).

---

## Features

### Package Management

| Feature | Description | Tech Notes |
|---|---|---|
| Package Setup | Create subscription packages (Silver / Gold / Premium) with pricing | Package model |
| Module Assignment | Assign which modules are enabled per package | Pivot: package_modules |
| Pricing Tiers | Monthly / yearly billing; price per package | Enum frequency |
| Feature Limits | Set limits per package (e.g., max students, max staff, storage quota) | JSON limits column |

### School Subscription

| Feature | Description | Tech Notes |
|---|---|---|
| Assign Package | Super admin assigns a package to a school | Subscription model |
| Subscription Period | Set start date, end date, status (active/expired/suspended) | Date fields + observer |
| Renewal System | Extend subscription on payment; send expiry reminders | Scheduled notification |
| Trial System | Assign a trial period (e.g., 30 days) with full/limited access | Trial flag + expiry |
| Subscription Dashboard | Super admin: active vs expired vs trial subscriptions count | Aggregated KPI |

### Marketing Features

| Feature | Description | Tech Notes |
|---|---|---|
| Coupon / Discount | Create discount coupons (% or fixed amount) with expiry | Coupon model |
| Coupon Redemption | Apply coupon at subscription/renewal time | Validation service |
| Trial-to-Paid Conversion | Track schools that converted from trial to paid | Analytics query |

### Module Manager (per School)

| Feature | Description | Tech Notes |
|---|---|---|
| Module Toggle | Super admin can enable/disable individual modules for a school | school_modules pivot |
| School-Level Override | Override package defaults for a specific school | Override flag |
| Module Status View | See which modules each school has active | Admin DataTable |

---

## Database Tables

### packages
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| name | string | Silver / Gold / Premium |
| slug | string | |
| price_monthly | decimal | |
| price_yearly | decimal | |
| max_students | integer | 0 = unlimited |
| max_staff | integer | 0 = unlimited |
| storage_gb | integer | |
| is_active | boolean | |

### package_modules
| Column | Type | Notes |
|---|---|---|
| package_id | FK | |
| module_slug | string | e.g., "library", "hostel", "transport" |

### school_subscriptions
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK | |
| package_id | FK | |
| start_date | date | |
| end_date | date | |
| status | enum | trial / active / expired / suspended |
| is_trial | boolean | |
| trial_ends_at | date | Nullable |
| amount_paid | decimal | |
| payment_method | string | |
| coupon_id | FK | Nullable |

### school_modules (override table)
| Column | Type | Notes |
|---|---|---|
| school_id | FK | |
| module_slug | string | |
| is_enabled | boolean | Override package default |

### coupons
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| code | string | Unique |
| type | enum | percent / fixed |
| value | decimal | |
| expires_at | date | |
| max_uses | integer | |
| used_count | integer | |
| is_active | boolean | |

---

## Impact on School Panel
- Every school panel page must check `school_modules` before showing a module link in the sidebar
- If a module is disabled for the school's package, the sidebar link is hidden and the route returns 403
- Subscription expiry check runs on each request via middleware (`CheckSubscription`)

---

## Roles with Access
- **Super Admin only** — Package, Coupon, Module Manager
- **School Admin** — read-only: view their own current package, expiry date, enabled modules

---

## Sprint
**Phase 7 — Sprint 17B** (alongside PWA/API sprint, or new Sprint 21)
