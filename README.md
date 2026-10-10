# SchoolRuns

SchoolRuns is a school management system: one Laravel application that
renders its own React (TypeScript) pages using [Inertia.js](https://inertiajs.com/),
so there's a single codebase instead of a separate backend and frontend.
It covers admissions, students, staff, attendance, timetables, exams,
fees, the library, transport, hostels, homework, communication, and
reporting, with separate views for Super Admin, School Admin, Teacher,
Accountant, Student, and Parent accounts.

**Supabase** checks logins: when someone signs in, Laravel sends their
email and password to Supabase, and Supabase confirms whether that
password is correct. Laravel never stores or checks the password itself
— it only decides, once Supabase confirms who is signing in, what that
person is allowed to do (which school they belong to, whether they're a
Teacher or a Parent, and so on), using the person's existing account
here. **Supabase's database (Postgres) is also this app's own database**,
and **Supabase Storage** holds uploaded files (documents, photos, school
logos).

## Running it locally

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate --seed

npm run build
php artisan serve
```

The app reads its Supabase project's details, and which database to use,
from environment variables in `.env` instead of having them typed
directly into the code.

### Connecting a real Supabase project

Until you create one, the app runs against placeholder values (the
sign-in form will show, but signing in won't work). To connect a real
project:

1. Create a project at [supabase.com](https://supabase.com).
2. In its dashboard, go to **Settings → API** and copy the Project URL,
   anon key, and service role key into `.env` (`SUPABASE_URL`,
   `SUPABASE_ANON_KEY`, `SUPABASE_SERVICE_ROLE_KEY`).
3. For the database, go to **Settings → Database**, copy the connection
   details into `.env` (`DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`,
   `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then run
   `php artisan migrate`.
4. For file storage, create a bucket under **Storage**, then copy its
   S3-compatible access keys (**Storage → Connect**) into `.env`
   (`SUPABASE_STORAGE_*` variables), and set `FILESYSTEM_DISK=supabase`.
5. **Important**: signing in only works for people who already have an
   account in *this app's* database (school admins create Staff,
   Student, and Guardian accounts, which is where roles and school
   membership come from). Creating someone in Supabase's own user list
   doesn't by itself give them access — a matching account (same email)
   also needs to exist here, and vice versa.

## Deploying

- **The app**: deploys on [Railway](https://railway.app), which runs it
  as a normal, always-on server (not a serverless function), so it
  behaves like it would on any regular host. `railway.json` tells
  Railway how to build it (`composer install` + `npm run build`), and
  `Procfile` tells it how to start it:
  - `web` runs pending database migrations, then starts the app.
  - `worker` (optional, enable it as a second Railway service if you
    want it) processes queued background jobs — e-mail/SMS blasts,
    report generation, and the like — instead of running them inline.
    If you enable it, switch `QUEUE_CONNECTION` to `database` in your
    environment variables so jobs actually wait in the queue for it.
  - **Important**: if your Railway project still has its root directory
    set to `api/` from before this app was swapped in, change it to the
    repository root in Railway's project settings — that folder no
    longer exists.
  - Set the same environment variables from your `.env` in Railway's
    project settings, plus `APP_ENV=production`, `APP_DEBUG=false`, and
    `APP_URL` set to the app's real Railway/custom domain.
  - Uploaded files still go to Supabase Storage rather than this
    server's own disk, since a redeploy replaces the container (and
    its disk) from scratch.
- **Documentation site** (`docs/`): a separate [VitePress](https://vitepress.dev)
  site with its own build step, deployed on [Vercel](https://vercel.com)
  — see `vercel.json`. Set the Vercel project's root directory to the
  repository root (it `cd`s into `docs/` itself during the build).

## What's built so far

See `requirements/00-overview.md` and the module files under
`requirements/modules/` for the full feature list, and `docs/` for
day-to-day usage guides for each role.
