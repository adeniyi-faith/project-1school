# SchoolRuns

SchoolRuns is a multi-school platform: schools sign up, run their day-to-day
operations, and manage their own branding and public website. It's being
built as a website first, with a mobile app planned for later using the
same backend.

## How the project is organised

This repository holds two separate applications that talk to each other
over the network, plus room to grow:

- **`api/`** — the Laravel backend. This is where all the important rules
  live: what a "school" is, who can log in, what a Teacher is allowed to
  do versus a Parent, and so on. It exposes this as a set of web
  addresses (an "API") that return data as JSON rather than full web
  pages. Any app — this website today, a mobile app tomorrow — asks the
  API for data and follows the same rules.
- **`web/`** — the React + TypeScript website people actually see and use
  in a browser. It is deliberately "dumb" about business rules: it asks
  the API for data, and displays it. This keeps the rules in one place
  (the API) instead of being duplicated and risking going out of sync.

Splitting things this way now means a future mobile app can be added as
its own folder later, reusing the same `api/` backend without any of its
rules needing to be rewritten.

## Running it locally

**Backend (Laravel API):**

```bash
cd api
composer install
cp .env.example .env   # if you don't already have one
php artisan key:generate
php artisan migrate
php artisan serve --port=8000
```

**Frontend (React web app):**

```bash
cd web
npm install
cp .env.example .env.local   # if you don't already have one
npm run dev
```

The frontend reads the API's address from an environment variable
(`VITE_API_URL` in `web/.env.local`) instead of having it typed directly
into the code. The backend reads which website(s) are allowed to call it
from another environment variable (`FRONTEND_URLS` in `api/.env`). This
means moving from your laptop to a real server later is just a matter of
changing these settings, not changing code.

## What's built so far

- A working Laravel API with a `/api/health` endpoint.
- A working React + TypeScript website that calls that endpoint and shows
  the result — proof the two sides can talk to each other correctly.
- CORS (the browser security rule that controls which websites are
  allowed to call an API) is configured through environment variables
  rather than hardcoded.
- [Laravel Sanctum](https://laravel.com/docs/sanctum) is installed for
  future login/authentication — not wired up to real user accounts yet.

## What's coming next

This is intentionally a small first step. Upcoming work will add: school
registration, user accounts and roles (School Admin, Teacher,
Parent/Guardian, Student), and the multi-school data model where each
school's data is kept separate.
