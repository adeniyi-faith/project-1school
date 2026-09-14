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

**Supabase** is used for login, file storage, and realtime updates.
Logging in works like this: Supabase checks the password and hands the
browser a signed "pass" (a token). The website sends that pass along with
every request to Laravel. Laravel checks that the pass is genuine, then
still decides everything about *what that person is allowed to do* (which
school they belong to, whether they're a Teacher or a Parent, etc.) — that
part stays in Laravel so the future mobile app can reuse it without
rebuilding any rules.

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

The frontend reads the API's address, and its Supabase project details,
from environment variables (`VITE_API_URL`, `VITE_SUPABASE_URL`,
`VITE_SUPABASE_ANON_KEY` in `web/.env.local`) instead of having them typed
directly into the code. The backend reads which website(s) are allowed
to call it, plus its own Supabase settings, from `api/.env`. This means
moving from your laptop to a real server later is just a matter of
changing these settings, not changing code.

### Connecting a real Supabase project

Until you create one, both apps run against placeholder values (the
website's login form will show, but signing in won't actually work). To
connect a real project:

1. Create a project at [supabase.com](https://supabase.com).
2. In its dashboard, go to **Settings → API** and copy the Project URL,
   anon key, and JWT Secret into `web/.env.local` (`VITE_SUPABASE_URL`,
   `VITE_SUPABASE_ANON_KEY`) and `api/.env` (`SUPABASE_URL`,
   `SUPABASE_ANON_KEY`, `SUPABASE_JWT_SECRET`).
3. For the database, go to **Settings → Database**, copy the connection
   details into `api/.env` (`DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`,
   `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then run
   `php artisan migrate`.
4. For file storage, create a bucket under **Storage**, then copy its
   S3-compatible access keys (**Storage → Connect**) into `api/.env`
   (`SUPABASE_STORAGE_*` variables).

## Deploying

- **Website (`web/`)**: built for [Vercel](https://vercel.com) — connect
  this GitHub repo and it will detect the Vite project automatically.
  Every branch and pull request gets its own preview link, so you can see
  changes live as they're built. Set the same `VITE_...` environment
  variables in the Vercel project settings.
- **API (`api/`)**: needs a host that can run a persistent PHP/Laravel
  app — [Laravel Cloud](https://cloud.laravel.com) is the simplest option.
  Not set up yet.

## What's built so far

- A working Laravel API with a public `/api/health` endpoint and a
  `/api/me` endpoint that requires a valid Supabase login.
- A working React + TypeScript website with a sign-in form, wired to
  Supabase, that proves the whole chain works: website → Supabase login →
  Laravel checking that login.
- CORS (the browser security rule that controls which websites are
  allowed to call an API) is configured through environment variables
  rather than hardcoded.
- Supabase Storage is configured as a Laravel filesystem disk, ready for
  file uploads once a bucket exists.
- The website is set up to deploy on Vercel with automatic preview links.
- [Laravel Sanctum](https://laravel.com/docs/sanctum) is also installed,
  kept in reserve for any case where the API itself needs to hand out its
  own tokens (separate from Supabase logins).

## What's coming next

This is intentionally a small step. Upcoming work will add: school
registration, user accounts and roles (School Admin, Teacher,
Parent/Guardian, Student), and the multi-school data model where each
school's data is kept separate.
