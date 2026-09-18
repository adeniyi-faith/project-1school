# Module 01 — Authentication & Access Control

## Overview
Handles all authentication, authorization, 2FA, activity logging, and user profile management.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| Login/Logout | Email+password login, remember-me, session management, logout all devices | Sanctum, Inertia shared data |
| Password Reset | Forgot password flow via email OTP / link, change password on first login | Laravel Notifications |
| 2FA | TOTP (Google Authenticator) + email OTP fallback | pragmarx/google2fa |
| Role & Permission | Spatie roles/permissions, dynamic permission assignment per school | spatie/laravel-permission |
| Activity Log | Log every login, role change, critical action with IP and user-agent | spatie/laravel-activitylog |
| Profile | Update name, avatar, password, notification preferences | Zustand user store |

## Role Hierarchy

| Role | Capabilities |
|---|---|
| Super Admin | Full system access, school creation, billing, global settings |
| School Admin | All modules for their school, staff management, reports |
| Principal | Academic oversight, approvals, all reports for their school |
| Teacher | Attendance, marks entry, homework, own timetable, messaging |
| Accountant | Fee collection, expense, salary, financial reports |
| Librarian | Book management, issue/return, member management |
| Receptionist | Student enquiry, admission form, visitor log |
| Student | Own dashboard, results, attendance, fee status, homework |
| Parent/Guardian | Child's dashboard, attendance, results, fee, messaging |
| Driver | Own route, attendance punch (bus), schedule |
| Warden | Own hostel student list, hostel attendance marking |
| Store Manager | Inventory stock management, item issue/return |

## REST API Endpoints

| Method | Endpoint | Description | Auth |
|---|---|---|---|
| POST | /api/v1/auth/login | Authenticate user, return token + user object | Public |
| POST | /api/v1/auth/logout | Revoke current token | Auth required |
| POST | /api/v1/auth/forgot-password | Send password reset link/OTP | Public |
| POST | /api/v1/auth/reset-password | Reset password with token | Public |
| GET | /api/v1/auth/me | Return authenticated user with roles & permissions | Auth required |
| POST | /api/v1/auth/2fa/enable | Enable TOTP 2FA, returns QR URI | Auth required |
| POST | /api/v1/auth/2fa/verify | Verify TOTP code | Auth required |

## Zustand Store
- `useAuthStore` — state: `user, role, permissions, school, academicYear`
- Actions: `setUser, logout, refreshPermissions`
- `useNotificationStore` — state: `unreadCount, notifications[]`
- Actions: `fetchNotifications, markRead, subscribe`

## Security Notes
- 2FA enforced for Admin and Accountant roles
- All logins logged with IP and user-agent
- CSRF protection on all state-changing requests
- Passwords stored as bcrypt hashes, never logged

## Sprint
**Phase 1 — Sprint 1 & 2**
- Sprint 1: Auth pages (login, forgot password, 2FA), base layout
- Sprint 2: Multi-school seed, super-admin scaffold, role/permission seeder
