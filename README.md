# CampusLedger

A vanilla PHP 8.1 transaction import and reporting app. No framework - a
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

For Apache, point the document root at `web/` - `.htaccess` routes
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

- `app/Core` - router, PDO connection, config/env loading, migrations,
  logging. No service container or ORM by design.
- `app/Controllers/Web` returns HTML; `app/Controllers/Api` returns arrays
  that `web/index.php` JSON-encodes.
- `app/Services/ImportService` streams the CSV via `CsvReader`, normalizes
  and validates each row, and bulk-inserts in chunks of 1000 - memory stays
  bounded regardless of file size.
- `web/index.php` is the single front controller for both Web and API;
  centralizes exception handling (HTML error pages vs. JSON) and logs to
  `storage/logs/app.log`.
- `bin/console migrate|import` reuses the same `ImportService`/`Migrator`
  as the web app. `bin/console reset [--force]` truncates all imported data
  - CLI-only, since neither surface has authentication.
- `transactions` VARCHAR widths (`VARCHAR(255)`) are intentionally generous
  and decoupled from the actual business length rules, which live only in
  `TransactionValidator::MAX_LENGTHS`. The DB is a storage safety net; the
  validator is the single source of truth, so tightening or loosening a
  rule is a code change, not a migration.
- The transactions list table shows the seven most-scanned fields; less
  common ones (`terminal_id`, `merchant_id`, `external_reference`, and the
  full `card_number`) are one click away behind a "Details" expand row per
  transaction, mirroring the raw-data expand pattern already used on the
  Import Details page for rejected rows. The Amount column is colored
  green/red for `credit`/`debit` transaction types as a scannability aid.

## Assumptions

The CSV schema (`transaction_id, occurred_at, terminal_id, card_number,
account, amount, transaction_type, status, merchant_id, merchant_name,
currency, external_reference`) and the task itself left several rules
unspecified. Decisions made, and why:

- **Required vs. optional columns.** Only `transaction_id`, `occurred_at`,
  `amount`, `currency`, `transaction_type`, `status` are required; a file
  missing any of these is rejected outright before any row is processed.
  The rest - `merchant_name`, `account`, `card_number`, `terminal_id`,
  `merchant_id`, `external_reference` - are optional per row. Real
  settlement data legitimately has transactions with no card (ACH), no
  terminal (card-not-present), or no merchant (fees/adjustments), and the
  task states the file "intentionally contains imperfect data" - rejecting
  every row missing a secondary identifier would defeat that intent.
- **Duplicates vs. rejected rows.** A row whose `transaction_id` already
  exists (earlier in the same file, or in a prior import) increments
  `duplicate_count`, not `rejected_count` - they're mutually exclusive
  dashboard counters. It's still written to `rejected_transactions` (error:
  `"Duplicate transaction_id"`) so the Import Details page can explain
  every row that didn't become a transaction. This is what makes re-import
  of the same file safe (a stated functional requirement): re-running an
  import is a no-op for rows already present, not an error.
- **`transaction_type` is free text, not a validated enum.** The validator
  only checks it's non-blank and under the length limit - there's no
  allow-list of `credit`/`debit`/`purchase`/etc. The UI's amount
  color-coding matches the literal (case-insensitive) strings `credit` and
  `debit`; any other value, typo, or abbreviation (e.g. `"CR"`) is
  displayed uncolored rather than rejected or flagged. Chosen to avoid
  guessing at a closed vocabulary the task didn't specify - see Future
  Improvements.
- **`card_number` is stored and displayed in full, unmasked.** No
  tokenization, truncation (e.g. last 4 digits), or encryption at rest -
  treated as an opaque identifier for filtering/display purposes only, the
  same as `account`. This is a deliberate scope cut for the assessment, not
  a recommendation for how this would be built for real cardholder data -
  see Future Improvements.
- **Upload size is bounded by PHP's own `upload_max_filesize`/
  `post_max_size` ini settings, not application code.** `UPLOAD_MAX_BYTES`
  exists in `.env`/`config/app.php` but nothing currently reads it in
  `UploadValidator` - it's a documented placeholder for wiring up an
  application-level check.
- **`currency`** is required (blank rejected), uppercased on normalization;
  no ISO-4217 whitelist beyond the 3-character column width.
- **CSRF** protection applies to web form submissions (session-token
  based). The JSON API is stateless with no CSRF check, matching typical
  token/API-key-protected API conventions. No authentication is implemented
  for either surface - see Future Improvements.
- Local dev credentials default to `root` / no password against
  `127.0.0.1:3306`, matching a typical local MySQL install.

## Database Schema

- **import_batches** - one row per import run (counts, timestamps).
- **transactions** - successfully imported rows (`transaction_id` unique).
- **rejected_transactions** - every row that failed validation or was a
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
tests run against their own `campus_ledger_test` database - auto-created and
migrated by `tests/bootstrap.php` - and never touch dev data. Each test
truncates its tables in `setUp()`.

- `tests/Unit` - validators, normalizer, CSV reader, report service.
- `tests/Integration` - import pipeline and repositories against a real DB.
- `tests/Feature` - Web pages, uploads, and all API endpoints through the
  actual controllers.

## Future Improvements

Documented only, intentionally not built for this assessment:

- Authentication
- Mask or tokenize `card_number` instead of storing the full value (if it makes sense)
- Enforce `UPLOAD_MAX_BYTES` in `UploadValidator` (currently unused)
- Group merchant reporting by `merchant_id` instead of the free-text
  `merchant` name, which is fragile across inconsistent casing/spelling (may be extract the merchants to a merchants table)
- Validate `transaction_type` against a known vocabulary instead of any
  non-blank string
- Background imports / queued processing for very large files
- OpenAPI documentation
- Scheduled reports
