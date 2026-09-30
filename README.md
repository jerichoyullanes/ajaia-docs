# Ajaia Collaborative Docs

A lightweight collaborative document editor built with Laravel, React, Inertia, and Tiptap. Documents are saved as Tiptap JSON, and owners can share them with other registered users as either editors or viewers.

## Requirements

- PHP 8.4 or later
- Composer
- Node.js and npm
- SQLite for the default local setup

Note: or use Laragon

## Local setup

From the project root, install the PHP dependencies and create your local environment:

```powershell
composer install
Copy-Item .env.example .env
New-Item -ItemType File -Force database/database.sqlite
php artisan key:generate
```

The provided `.env.example` configures SQLite. If you already have a `.env`, keep it and confirm `DB_CONNECTION=sqlite` and `DB_DATABASE` point to your local SQLite database.

Install frontend dependencies, prepare the database, and seed the demo accounts and documents:

```powershell
npm install
php artisan migrate:fresh --seed
```

> `migrate:fresh` drops and recreates all tables in the configured database. Use it only with a disposable local database.

## Run the application

```powershell
composer run dev
```

This starts the local Laravel development processes, including the web server and Vite. Open the local URL shown in the terminal (by default, `http://localhost:8000`).

## Demo accounts

Both accounts use the password `password`:

| Role     | Email                  |
| -------- | ---------------------- |
| Owner    | `owner@example.com`    |
| Reviewer | `reviewer@example.com` |

The seeded owner has an example document shared with the reviewer for editing and a private document. The reviewer also has a view-only document shared with the owner. The seeder is idempotent; running it again updates or reuses the demo data.

## Checks

Run the feature tests, frontend lint/format checks, TypeScript check, PHP static analysis, and production build with:

```powershell
php artisan test
npm run check
npm run types:check
composer run types:check
npm run build
```

## More project notes

- [Architecture](deliverables/ARCHITECTURE.md)
- [AI workflow](deliverables/AI-WORKFLOW.md)
- [Submission contents](deliverables/SUBMISSION.md)
