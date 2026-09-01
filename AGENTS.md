# AGENTS.md

## Project Overview

Laravel 13 API backend + Vue 3 SPA frontend. Despite the repo name ("GDA-REACT-API-LARAVEL"), this uses **Vue 3** (not React). The frontend is in `frontend/` as a separate Vite project.

- **Backend**: PHP 8.3, Laravel 13, Sanctum token auth, MySQL (production) / SQLite in-memory (tests)
- **Frontend**: Vue 3 + Vue Router + Axios + ag-grid, separate from the Laravel Blade assets

## Commands

```bash
# Full dev server (runs php artisan serve + vite concurrently)
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
- No queue jobs — the multi-search export builds the Excel **client-side** via `xlsx`: it calls `POST /products/multi-search-rows` (one request per keyword, capped by `max_results`), accumulates rows, and generates the file in the browser
- `routes/api.php` — All API routes, protected by `auth:sanctum` middleware
- `frontend/src/App.vue` — **THE actual running app** (login + dashboard inline, hardcoded `https://api.gda.sarlpro.com/api/...` URLs). `frontend/src/views/Products.vue` is a SEPARATE dead view imported only via the router — edits there will NOT show up.
- `frontend/src/components/` — ImportModal, Toast, MultiSearchExportModal
- `frontend/src/main.js` — Axios base URL is `http://localhost:8000/api` but the running app overrides it with hardcoded full URLs in App.vue

## Key Gotchas

- **Two separate npm projects**: root `package.json` (Laravel Vite plugin + Tailwind v4) and `frontend/package.json` (Vue SPA). Do not mix them up.
- **Frontend API URL is hardcoded** in `frontend/src/main.js` line 9 to `localhost:8000`. Must be changed for non-local dev.
- **Auth tokens** are stored in `localStorage` as `auth_token`, sent as `Authorization: Bearer` header.
- **Tests use SQLite in-memory** (`phpunit.xml`), not MySQL. RefreshDatabase trait is commented out in `tests/Pest.php`.
- **`composer dev`** runs two processes concurrently: `php artisan serve` and `npm run dev`. No queue worker is needed.
- **Multi-search export builds the Excel client-side**: `POST /products/multi-search-rows` (one request per keyword, optional `max_results` cap) returns `{ keyword, total, rows }` where each row is `[search value, ...selected columns]` (date already `m/d/Y`); the modal accumulates rows and generates the .xlsx in the browser with `xlsx` (`XLSX.utils.aoa_to_sheet`, sheet name `Search Results`, first column `Search Value`). No queue job, no `product_exports` table, no server file, no polling. The legacy queued flow (job, `ProductExport` model, endpoints, migrations, `exportMonitor.js` store) was deleted.
- **Product search** uses a tolerant token-based search (`applyTolerantSearch`) that normalizes input and does AND-across-tokens matching against ref/designation/marque/fournisseur fields.
- **Fournisseur IDs are index-based** (1-based index from distinct product fournisseur values), not real foreign key IDs. See `ProductController::importMapping()` line 114.
- **Date filtering** in `applyDateFilter` supports year-only ("2026"), year-month ("2026-03"), or full date — no standard Laravel date query.
- **No `.env.example`** committed. `.env` has real credentials — never commit secrets.

## Code Style

- 4 spaces indentation, LF line endings (`.editorconfig`)
- Laravel Pint for PHP formatting (`./vendor/bin/pint`)
- Pest v4 for tests, not PHPUnit directly
- API responses return `{ rows, total }` for paginated lists
