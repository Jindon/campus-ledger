# CampusLedger Lite

A vanilla PHP 8.1 transaction import and reporting app. No framework — a
small router, layered architecture (Controller → Service → Repository →
Database), shared between the Web UI, the REST API and the CLI.

## Setup

Requirements: PHP 8.1+, MySQL 8, Composer.

```bash
composer install
cp .env.example .env      # edit DB_* credentials if needed
php bin/console migrate   # creates the database (if missing) and tables
php -S 127.0.0.1:8000 -t web
```

For Apache, point the document root at `web/` — `.htaccess` routes
everything through `web/index.php`.

CSS is precompiled and committed (`web/assets/css/app.css`). Only rebuild it
if you change Tailwind classes: `npm install && npm run build:css`.

## Architecture

```
Controller  -> validates input, calls a Service, returns HTML/JSON
Service     -> business logic (CSV import pipeline, reports)
Repository  -> PDO queries, hydrates Models
Validator   -> CSV row validation, upload validation
```

- `app/Core` — router, PDO connection, config/env loading, migrations,
  logging. No service container or ORM by design.
- `app/Controllers/Web` returns HTML; `app/Controllers/Api` returns arrays
  that `web/index.php` JSON-encodes.
- `app/Services/ImportService` streams the CSV via `CsvReader`, normalizes
  and validates each row, and bulk-inserts in chunks of 1000 — memory stays
  bounded regardless of file size.
- `web/index.php` is the single front controller for both Web and API;
  centralizes exception handling (HTML error pages vs. JSON) and logs to
  `storage/logs/app.log`.
- `bin/console migrate|import` reuses the same `ImportService`/`Migrator`
  as the web app. `bin/console reset [--force]` truncates all imported data
  — CLI-only, since neither surface has authentication.

## Key decisions

- Required CSV columns: `transaction_id`, `occurred_at`, `amount`,
  `currency`, `transaction_type`, `status`. Missing any of these rejects the
  whole file upfront. The rest — `merchant_name`, `account`, `card_number`,
  `terminal_id`, `merchant_id`, `external_reference` — are optional.
- A row whose `transaction_id` already exists (in this file or a prior
  import) counts as a **duplicate**, not a rejection — but it's still logged
  to `rejected_transactions` so the Import Details page explains it.
- Web form submissions are CSRF-protected (session token); the JSON API is
  stateless with no CSRF check. No authentication on either surface.

## Database Schema

- **import_batches** — one row per import run (counts, timestamps).
- **transactions** — successfully imported rows (`transaction_id` unique).
- **rejected_transactions** — every row that failed validation or was a
  duplicate, with JSON `errors`/`raw_data`, tied to an import batch.

Migrations are plain `.sql` files in `database/migrations/`, applied in
filename order, tracked in a `migrations` table. `php bin/console migrate`
is idempotent.

## API

All responses are JSON. Errors: `{"error": "message"}` (plus `"errors"` for
422s) with a matching HTTP status.

| Method | Endpoint | Description                                                                                                                          |
|--------|----------|--------------------------------------------------------------------------------------------------------------------------------------|
| GET | `/api/transactions` | Paginated. Filters: `page`, `q`, `date_from`, `date_to`, `merchant`, `status`, `account`, `card_number`, `amount_min`, `amount_max`. |
| GET | `/api/imports` | Paginated import batches, newest first. Paginate with `?page=<page_no>`                                                              |
| GET | `/api/imports/{id}` | One import batch + its rejected transactions. 404 if missing. To paginate rejected transactions -> `?page=<page_no>`                 |
| GET | `/api/reports/daily` | Count/total for one day, grouped by currency. Query: `?date=2026-07-16` (defaults to today).                                         |

## Testing

```bash
composer test
```

Config is read from `.env.test` (committed, separate from your `.env`), so
tests run against their own `campus_ledger_test` database — auto-created and
migrated by `tests/bootstrap.php` — and never touch dev data. Each test
truncates its tables in `setUp()`.

- `tests/Unit` — validators, normalizer, CSV reader, report service.
- `tests/Integration` — import pipeline and repositories against a real DB.
- `tests/Feature` — Web pages, uploads, and all API endpoints through the
  actual controllers.

## Future Improvements

Documented only, intentionally not built for this assessment:

- Authentication
- Background imports / queued processing for very large files
- OpenAPI documentation
- Docker
- Scheduled reports
