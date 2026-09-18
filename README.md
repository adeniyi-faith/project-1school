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

- **The app**: set up to deploy on [Vercel](https://vercel.com) using a
  community PHP runtime (`vercel-php`) so Laravel can run as a Vercel
  serverless function — see `vercel.json`. This is a different setup
  from a typical Laravel host, so a few things work differently:
  - There's no long-running process, so background jobs run immediately
    within the same request instead of waiting in a queue
    (`QUEUE_CONNECTION=sync`).
  - Nothing saved to local disk survives between requests, so uploaded
    files must go to Supabase Storage, not this server's own disk
    (`FILESYSTEM_DISK=supabase`).
  - Logins and cached data are kept in the database (Supabase Postgres)
    rather than in local files, since that's the only storage that's
    actually shared between requests.
  - Set the same environment variables from your `.env` in the Vercel
    project's settings.
  - This path is less battle-tested than a normal PHP host (e.g.
    [Laravel Cloud](https://cloud.laravel.com)) — if anything doesn't
    behave as expected after deploying, that's the first thing to check.
- **Documentation site** (`docs/`): a separate [VitePress](https://vitepress.dev)
  site with its own build step. Deploy it as its own Vercel project with
  its root directory set to `docs/`, rather than through the root
  `vercel.json` (which now points at the app itself).

## What's built so far

See `requirements/00-overview.md` and the module files under
`requirements/modules/` for the full feature list, and `docs/` for
day-to-day usage guides for each role.
