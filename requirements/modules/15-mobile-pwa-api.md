# Module 15 — Mobile PWA & API

## Overview
Progressive Web App with offline access, full versioned REST API, auto-generated Swagger docs, mobile authentication, and parent-optimized mobile view.

## Features

| Feature | Description | Tech Notes |
|---|---|---|
| PWA | Installable Progressive Web App with offline basic access | Workbox service worker |
| REST API | Full REST API for each module (JSON); versioned at /api/v1/ | Laravel API Resource |
| API Docs | Auto-generated Swagger/OpenAPI docs | l5-swagger |
| Mobile Auth | App login with remember-token; biometric hint on mobile | Sanctum + PWA |
| Parent App | Simplified parent-facing view optimized for mobile | Responsive design |

## API Architecture
- Base URL: `/api/v1/`
- All endpoints require `Bearer` token (Sanctum) unless marked public
- Response format: `{ data, meta, links, message }` (JSON:API-lite)
- Versioned: future breaking changes go to `/api/v2/`

## API Rate Limiting
- 60 req/min per token (configurable)
- Headers returned: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

## PWA Features
- Workbox service worker for offline caching
- App manifest for installability (Android/iOS)
- Offline access to: dashboard, last-viewed attendance, schedule
- Push notifications via Web Push API (VAPID)
- Background sync for offline marks/attendance entry

## Swagger / OpenAPI
- Package: `darkaonline/l5-swagger`
- Auto-generated from PHPDoc annotations on controllers
- Available at `/api/documentation` (admin-only in production)

## Sprint
**Phase 7 — Sprint 17**
- PWA manifest + service worker, offline support, API key management, webhook config, Swagger docs, API rate limiting
