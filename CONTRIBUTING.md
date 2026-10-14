# Contributing

This is a showcase project, so changes are made by the owner and the people the owner invites.

1. Make your change on a new branch, never directly on `main`.
2. Run `php artisan test`. Every test must pass. GitHub runs the same tests on every pull request.
3. Open a pull request and say, in plain words, what works differently afterwards.
4. Read [CLAUDE.md](CLAUDE.md) first. It lists the rules (for example: every school screen needs a permission check, and every school table needs a `school_id`). Tests fail if those rules are broken.

Merging to `main` puts the change on the live site automatically.
