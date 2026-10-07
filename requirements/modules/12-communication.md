# Module 12 — Communication

## Overview
Internal messaging, announcements, noticeboard, bulk SMS/email blasts, browser push notifications, and parent portal.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Announcements | Admin/teacher posts school-wide or class-specific announcements | Role-scoped feed |
| Messaging | Internal DM between any two users; group messaging per class | Polling or Pusher |
| Noticeboard | Digital noticeboard for events, circulars; archive old notices | CMS-style CRUD |
| SMS Blast | Send bulk SMS to parents/students (fee reminders, events) | Queue + Twilio/Vonage |
| Result SMS | Send term-wise and yearly result via SMS to parents automatically after result publish | Observer on result publish event |
| Email Blast | Bulk email with template support | Laravel Mail + queue |
| Email Templates | Admin-editable email templates for: fee reminder, result, absent alert, announcements | Template model + variable injection |
| Push Notification | Browser push + PWA notification for critical alerts | Web Push (VAPID) |
| Parent Portal | Parents access child info, pay fees, view results, message teachers | Separate role view |

## Database Tables

### announcements
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| author_id | FK | User |
| title | string | |
| body | text | |
| audience | enum | all / class / role |
| class_id | FK | Nullable — if audience=class |
| is_pinned | boolean | |
| published_at | timestamp | |

### notifications
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| user_id | FK | Recipient |
| type | string | |
| title | string | |
| body | text | |
| data | JSON | Extra payload |
| read_at | timestamp | Null = unread |
| channel | enum | email / sms / push / in-app |

## REST API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| GET | /api/v1/announcements | List announcements (scoped to user's classes/role) | All authenticated |
| POST | /api/v1/announcements | Create announcement | Admin, Teacher |
| GET | /api/v1/messages | Inbox / conversation list | All authenticated |
| POST | /api/v1/messages | Send message (DM or group) | All authenticated |
| POST | /api/v1/notifications/sms | Send bulk SMS to a group | Admin |
| GET | /api/v1/notifications | User's notification list | All authenticated |
| PATCH | /api/v1/notifications/read-all | Mark all notifications as read | All authenticated |

## SMS/Email Gateways
- SMS: Twilio or Vonage (configurable per school via admin settings)
- Email: SMTP (configurable per school via admin settings)
- Both dispatched via Laravel Queue (Horizon) for bulk sends

## Push Notifications
- VAPID keys generated per installation
- Web Push API for browser notifications
- PWA notification support for mobile

## Parent Portal Features
- View child's attendance, results, fee status, homework
- Pay fees online (Stripe)
- Message teachers directly
- Receive push/SMS/email notifications

## Sprint
**Phase 6 — Sprint 15**
- Announcements, internal messaging, noticeboard, SMS blast, email blast, browser push notifications, parent portal
