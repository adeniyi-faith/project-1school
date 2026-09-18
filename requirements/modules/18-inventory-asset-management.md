# Module 18 — Inventory & Asset Management

## Overview
School inventory (consumable items, stationery, equipment) with stock tracking, staff issue records, purchase history, and fixed asset management with depreciation and maintenance logs.

> **Source:** PDF design chart — "Inventory & Assets" section. Entirely absent from the original SRS.

---

## Features

### Inventory (Consumable Items)

| Feature | Description | Tech Notes |
|---|---|---|
| Item Catalog | Create item categories and items (name, unit, description) | Nested CRUD |
| Stock Management | Track current stock quantity per item; low-stock alert | Quantity column + observer |
| Purchase Entry | Record purchases (vendor, date, quantity, unit price, total) | Purchase model |
| Purchase History | Full purchase log per item; filterable by date/vendor | Aggregated query |
| Issue Item | Issue items to staff; record quantity, purpose, date | Issue model |
| Staff Issue Record | Track what was issued to which staff member | Pivot with notes |
| Return Item | Mark issued items as returned (partial/full) | Status update |
| Stock Report | Current stock levels, low-stock items, movement history | Export PDF/CSV |

### Asset Management (Fixed Assets)

| Feature | Description | Tech Notes |
|---|---|---|
| Asset Register | Register fixed assets (name, category, purchase date, purchase price, location) | Asset model |
| Asset Tracking | Track asset location (room/building), assigned to (staff/department) | Polymorphic assignment |
| Depreciation | Calculate annual depreciation (straight-line / reducing balance method) | Scheduled job |
| Maintenance Log | Log maintenance/repair events (date, description, cost, vendor) | Maintenance model |
| Asset Disposal | Mark asset as disposed/sold; record disposal value | Status enum |
| Asset Report | Asset list with current value, maintenance history, depreciation schedule | Export PDF/CSV |

---

## Database Tables

### inventory_items
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| category_id | FK | |
| name | string | |
| unit | string | pcs / kg / litre / box |
| current_stock | decimal | |
| minimum_stock | decimal | Alert threshold |
| description | text | |

### inventory_purchases
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| item_id | FK | |
| vendor | string | |
| purchase_date | date | |
| quantity | decimal | |
| unit_price | decimal | |
| total_price | decimal | |
| invoice_no | string | |
| notes | text | |

### inventory_issues
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| item_id | FK | |
| issued_to_type | string | staff / department |
| issued_to_id | BIGINT | |
| quantity | decimal | |
| issue_date | date | |
| return_date | date | Nullable |
| purpose | string | |
| status | enum | issued / returned / partial |

### assets
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| name | string | |
| category | string | Furniture / Electronics / Vehicle |
| purchase_date | date | |
| purchase_price | decimal | |
| current_value | decimal | Updated by depreciation job |
| depreciation_method | enum | straight_line / reducing_balance |
| depreciation_rate | decimal | % per year |
| location | string | Room/building |
| assigned_to | string | Staff name or department |
| status | enum | active / disposed / maintenance |

### asset_maintenance_logs
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| asset_id | FK | |
| school_id | FK, indexed | |
| date | date | |
| description | text | |
| cost | decimal | |
| vendor | string | |
| next_maintenance_date | date | Nullable |

---

## Roles with Access
- **School Admin** — full access
- **Accountant** — purchase entry, expense integration
- **Store Manager** (optional role) — stock management, issue items

---

## Sprint
**Phase 5 — Sprint 12B** (alongside or after Library, Sprint 12)
