# CampusLedger Lite

A vanilla PHP 8.1 transaction import and reporting application. No framework —
a small router, layered architecture (Controller → Service → Repository →
Database), and services shared between the Web UI, the REST API and the CLI.

## Setup

Requirements: PHP 8.1+, MySQL 8, Composer. Node is only needed if you want to
recompile Tailwind CSS locally — the compiled `web/assets/css/app.css` is
already committed, so it is **not** required on the deployment server.

```bash
composer install
cp .env.example .env      # edit DB_* credentials if needed
php bin/console migrate   # creates the database (if missing) and tables
```

Point Apache's document root at `web/` (e.g.
`/home/candidate1/sandbox/web`) — `web/.htaccess` rewrites all requests to
`web/index.php`, the single front controller. For local development you can
instead run:

```bash
php -S 127.0.0.1:8000 -t web
```

To rebuild the CSS after changing a view's Tailwind classes:

```bash
npm install
npm run build:css
```

## Architecture

```
Controller  -> receives the request, validates input, calls a Service, returns HTML/JSON
Service     -> business logic (CSV import pipeline, reports), coordinates Repositories
Repository  -> PDO queries, hydrates Models
Model       -> plain domain objects
Validator   -> CSV row validation, upload validation
```

- `app/Core` — router, PDO connection, config loader, `.env` loader, migration
  runner, file logger. Deliberately small; no service container or ORM.
- `app/Controllers/Web` — Web controllers (return rendered HTML) and
  `Controllers/Api` (return arrays that `web/index.php` JSON-encodes).
- `app/Services/ImportService` is the CSV import pipeline: it streams the
  file through `CsvReader`, normalizes each row with `TransactionNormalizer`,
  validates it with `TransactionValidator`, and persists it — all three
  layers are unit-tested independently.
- `web/index.php` is the only front controller for the Web UI and the API; it
  builds the routes, dispatches, and centralizes exception handling (friendly
  HTML error pages for the web, JSON for `/api/*`), logging unexpected errors
  and non-404 `HttpException`s to `storage/logs/app.log`.
- `bin/console` is a ~5-line command dispatcher (`migrate`, `import`) that
  reuses the exact same `ImportService` and `Migrator` used by the web app.

## Assumptions

- **Required CSV columns**: `transaction_id`, `occurred_at`, `amount`,
  `currency`, `transaction_type`, `status` (case-insensitive header). A file
  missing any of these is rejected outright as a file-level validation error
  before any row is processed. `merchant`, `account`, `card_number` are
  optional columns used for filtering.
- **"Never load the whole file into memory"** is implemented as streaming
  via `fgetcsv()` one row at a time, with valid/unique rows buffered in
  chunks of 1000 for a single bulk duplicate-check query, then inserted row
  by row. This keeps memory bounded regardless of file size while avoiding
  one duplicate-check round-trip per row.
- **Duplicates vs. rejected rows**: a row with a `transaction_id` that
  already exists (either earlier in the same file or in a previous import)
  is **not** double-counted as "rejected" — it increments `duplicate_count`
  instead, keeping the three dashboard counters mutually exclusive. It is
  still written to `rejected_transactions` (with error `"Duplicate
  transaction_id"`) so the Import Details page can explain every row that
  didn't become a transaction, per the spec's "make it easy to understand
  why rows were rejected."
- **`currency`** is required (blank is rejected) and uppercased on
  normalization; no further ISO-4217/length format check is enforced beyond
  the column's 3-character width.
- **Import result display**: after a successful web upload, the browser is
  redirected (POST/redirect/GET) straight to `/imports/{id}`, which already
  shows the full summary and rejected-rows table — rather than duplicating
  that UI inline on the Imports page behind a flash message.
- **CSRF** protection applies to web form submissions (session-token based).
  The JSON API is stateless and has no CSRF check, matching typical
  token/API-key-protected API conventions — no authentication is implemented
  for either surface (see Future Improvements).
- Local dev credentials default to `root` / no password against
  `127.0.0.1:3306`, matching a typical local MySQL install; override in
  `.env` for the sandbox.

## Database Schema

**`import_batches`** — one row per import run.
`id, filename, checksum, imported_count, rejected_count, duplicate_count, started_at, finished_at, created_at`

**`transactions`** — successfully imported rows.
`id, transaction_id (unique), occurred_at, amount, currency, transaction_type, status, merchant, account, card_number, import_batch_id (FK), created_at`

**`rejected_transactions`** — rows that failed validation or were duplicates, one row each, always tied to an import batch.
`id, import_batch_id (FK), row_no, transaction_id, errors (JSON), raw_data (JSON), created_at`

Migrations are plain `.sql` files in `database/migrations/`, applied in
filename order and tracked in a `migrations` table — `php bin/console
migrate` is idempotent.

## API Documentation

All responses are JSON.

| Method | Endpoint | Description |
|--------|----------|--------------|
| GET | `/api/transactions` | Paginated transactions. Query params: `page`, `q`, `date_from`, `date_to`, `merchant`, `status`, `account`, `card_number`, `amount_min`, `amount_max`. |
| GET | `/api/imports` | Paginated import batches, newest first. Query param: `page`. |
| GET | `/api/imports/{id}` | One import batch plus its rejected transactions. 404 if not found. |
| GET | `/api/reports/daily` | Transaction count/total for a single day, grouped by currency. Query param: `date` (`YYYY-MM-DD`, defaults to today; invalid values fall back to today). |

Example — `GET /api/transactions`:

```json
{
  "data": [{"id": 1, "transaction_id": "TXN0001", "amount": "120.00", "currency": "USD", "...": "..."}],
  "meta": { "page": 1, "per_page": 25, "total": 9, "last_page": 1 }
}
```

Errors are `{"error": "message"}` (plus `"errors"` field-level detail for
422s) with the matching HTTP status code (404, 422, 500).

## Testing

```bash
composer test   # or: vendor/bin/phpunit
```

Tests run against a separate `campus_ledger_lite_test` database (created and
migrated automatically by `tests/bootstrap.php` the first time you run the
suite), so they never touch your dev data. Each test truncates the relevant
tables in `setUp()`.

- `tests/Unit` — `TransactionValidator`, `TransactionNormalizer`,
  `CsvReader`, `ReportService`.
- `tests/Integration` — `ImportService`, `TransactionRepository`,
  `ImportBatchRepository`, `RejectedTransactionRepository`, exercised
  against the real database.
- `tests/Feature` — Imports list/details pages, Reports page, and all API
  endpoints, exercised through the actual controllers. (The web upload
  endpoint itself, `ImportController::store()`, is not covered by a test.)

A sample dirty file, `transactions_dirty.csv`, is included at the repo root
(mixed valid rows, bad dates, bad amounts, missing fields, and duplicates)
for manual testing: `php bin/console import transactions_dirty.csv`.

## Future Improvements

Documented only, intentionally not built for this assessment:

- Authentication
- Background imports / queued processing for very large files
- OpenAPI documentation
- Docker
- Scheduled reports
