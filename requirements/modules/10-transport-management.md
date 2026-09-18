# Module 10 — Transport Management

## Overview
Route and vehicle fleet management, student assignment to routes, driver portal, optional live GPS tracking, and transport reports.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Route Management | Define routes, stops with GPS coordinates and pickup times | JSON coordinates column |
| Vehicle Fleet | Vehicle details (number, type, capacity, driver assignment) | Simple model |
| Student Assignment | Assign students to route/vehicle; fee link | Pivot: student_route |
| Driver Portal | Driver sees route, student list, can mark attendance | Restricted role |
| Live Tracking | Optional GPS tracker integration (webhook from GPS device) | Webhook endpoint |
| Transport Reports | Route-wise student count, vehicle utilization, fee collection | Aggregated query |

## Database Tables

### routes
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| name | string | |
| start_point | string | |
| end_point | string | |
| stops | JSON | Array of {name, lat, lng, pickup_time} |
| vehicle_id | FK | |

### Pivot: student_route
| Column | Type | Notes |
|---|---|---|
| student_id | FK | |
| route_id | FK | |
| stop | string | Assigned pickup stop |
| fee_linked | boolean | Linked to transport fee category |

## Driver Portal
- Role: `driver`
- Can only see their assigned route and student list
- Can mark attendance for students on bus
- Cannot access any other module

## Live Tracking (Optional)
- GPS device sends webhooks to `/api/v1/transport/tracking`
- Stores last known coordinates per vehicle
- Optional real-time map view via polling or WebSocket

## Sprint
**Phase 5 — Sprint 13**
- Transport routes, vehicle fleet, student assignment, driver portal, live tracking webhook stub
