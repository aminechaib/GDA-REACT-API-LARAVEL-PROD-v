# AGENTS.md

## Project Overview

Laravel 13 API backend + Vue 3 SPA frontend. Despite the repo name ("GDA-REACT-API-LARAVEL"), this uses **Vue 3** (not React). The frontend is in `frontend/` as a separate Vite project.

- **Backend**: PHP 8.3, Laravel 13, Sanctum token auth, MySQL (production) / SQLite in-memory (tests)
- **Frontend**: Vue 3 + Vue Router + Axios + ag-grid, separate from the Laravel Blade assets

## Commands

```bash
# Full dev server (runs php artisan serve + queue:listen + vite concurrently)
composer dev

# One-time setup (install, migrate, build)
composer setup

# Run all tests (clears config cache first, then uses Pest)
composer test

# Run a single test file
php artisan test --filter=Feature/ExampleTest.php

# PHP lint / format (Laravel Pint)
./vendor/bin/pint

# Frontend dev server (Vue SPA)
cd frontend && npm run dev

# Frontend build
cd frontend && npm run build
```

## Architecture

- `app/Http/Controllers/Api/` — API controllers (ProductController, AuthController, FournisseurController)
- `app/Models/` — Eloquent models (Product, User)
- `app/Exports/` + `app/Imports/` — Maatwebsite Excel export/import classes
- `routes/api.php` — All API routes, protected by `auth:sanctum` middleware
- `frontend/src/` — Vue 3 SPA (views: Login, Products; components: ImportModal, Toast)
- `frontend/src/main.js` — Axios base URL hardcoded to `http://localhost:8000/api`

## Key Gotchas

- **Two separate npm projects**: root `package.json` (Laravel Vite plugin + Tailwind v4) and `frontend/package.json` (Vue SPA). Do not mix them up.
- **Frontend API URL is hardcoded** in `frontend/src/main.js` line 9 to `localhost:8000`. Must be changed for non-local dev.
- **Auth tokens** are stored in `localStorage` as `auth_token`, sent as `Authorization: Bearer` header.
- **Tests use SQLite in-memory** (`phpunit.xml`), not MySQL. RefreshDatabase trait is commented out in `tests/Pest.php`.
- **`composer dev`** runs three processes concurrently: `php artisan serve`, `php artisan queue:listen`, `npm run dev`. The queue must be running for background jobs.
- **Product search** uses a tolerant token-based search (`applyTolerantSearch`) that normalizes input and does AND-across-tokens matching against ref/designation/marque/fournisseur fields.
- **Fournisseur IDs are index-based** (1-based index from distinct product fournisseur values), not real foreign key IDs. See `ProductController::importMapping()` line 114.
- **Date filtering** in `applyDateFilter` supports year-only ("2026"), year-month ("2026-03"), or full date — no standard Laravel date query.
- **No `.env.example`** committed. `.env` has real credentials — never commit secrets.

## Code Style

- 4 spaces indentation, LF line endings (`.editorconfig`)
- Laravel Pint for PHP formatting (`./vendor/bin/pint`)
- Pest v4 for tests, not PHPUnit directly
- API responses return `{ rows, total }` for paginated lists
