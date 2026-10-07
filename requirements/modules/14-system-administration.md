# Module 14 — System Administration

## Overview
Super-admin tools for school management, user administration, system settings, backups, updates, API key management, and webhook configuration.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| School Management | Super-admin: create/edit/suspend schools, manage subscription | Super-admin only |
| User Management | Create/edit users, assign roles, reset passwords, suspend | Admin panel |
| System Settings | Global defaults: timezone, date format, currency, mail config | Config table |
| Backup | Database + file backup to S3; scheduled daily/weekly | Spatie Backup |
| Update System | Check for and apply updates (Git-based or zip); maintenance mode | Artisan commands |
| API Keys | Generate/revoke API keys for external integrations | Sanctum tokens |
| Webhook Config | Register webhooks for events (payment, admission, attendance) | Webhook model |
| Module Manager | Enable/disable individual modules per school; override package defaults | school_modules table (see Module 19) |
| Email Templates | Manage and customize system email templates (fee reminder, result, alerts) | Template model + Blade/variable injection |
| Cron Job Scheduler | UI to view and manually trigger scheduled jobs (backup, fine calc, result SMS) | Artisan command list + trigger |
| Button / UI Manager | Show/hide specific UI actions (e.g., hide delete button for certain roles) | Permission-based component flag |
| User Login Log | View login history per user: time, IP, device, success/failure | Extends activity log |
| Error Log | View application error log in admin panel (filtered, searchable) | Laravel Telescope / log viewer |
| Server Monitoring | Basic server health: CPU, memory, disk, queue status, failed jobs | Horizon + PHP server stats |
| LMS Settings | Configure LMS integration (Jitsi self-hosted URL, OAuth keys for Zoom/Meet) | Per-school config |

## Database Tables

### api_tokens
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| user_id | FK | |
| name | string | Token label |
| token_hash | string | SHA-256 hash |
| abilities | JSON | Array of allowed endpoints |
| last_used_at | timestamp | |
| expires_at | timestamp | Nullable |

### activity_logs
| Column | Type | Notes |
|---|---|---|
| id | BIGINT PK | |
| school_id | FK, indexed | |
| causer_type | string | User model |
| causer_id | BIGINT | |
| subject_type | string | Affected model |
| subject_id | BIGINT | |
| event | string | created / updated / deleted |
| properties | JSON | Before/after values |

## Backup Schedule
- Daily: Database backup to S3
- Weekly: Full backup (database + files) to S3
- Retention: 30 days configurable
- Package: `spatie/laravel-backup`

## API Rate Limiting
- 60 requests/minute per token (configurable)
- Configurable per school or per token

## Maintenance Mode
- Artisan command to enable/disable maintenance mode
- Custom maintenance page shown to users
- Super-admin can still access during maintenance

## Sprint
**Phase 7 — Sprint 17** (partial — API keys, webhook config)
- API Keys and webhook config are part of the API & PWA sprint
