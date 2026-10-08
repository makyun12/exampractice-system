> GitHub source package: dependencies, built assets, local databases, private uploads, and installation codes are excluded. Follow Local development below to install. Demo credentials are for local testing only; never use them in production.

# ExamPractice System

A Laravel learning and practice platform with English, Japanese, and Indonesian interfaces.

## Ready-to-upload Hostinger package

A separately generated Hostinger release is named `ExamPractice-Hostinger.zip`; it is not included in this source repository.
It includes production PHP dependencies, compiled CSS/JavaScript, sample learning content,
and a one-time browser installer. You do not need Composer, npm, or SSH on the hosting account.

1. Choose PHP **8.4+**, activate SSL, and create an empty MySQL database and database user in hPanel.
2. Extract the ZIP in the domain directory, **one level above `public_html`**:

```text
domains/your-domain.com/
  exampractice/       Private Laravel code, vendor, configuration, storage
  public_html/        Public entry point, assets, and one-time setup.php
  START-HERE-ID.txt   Installation code and Indonesian instructions
```

3. Open `https://your-domain.com/setup.php`. Enter the installation code from `START-HERE-ID.txt`.
4. Enter the website URL, database details, and your own administrator credentials.
5. The installer creates tables, generates a unique encryption key, creates the admin,
   optionally loads sample content, and permanently locks installation.
6. In hPanel, add a PHP Cron Job pointing to `exampractice/cron.php` every minute,
   using PHP 8.4+. The installer displays the full path for your hosting account.

Use a new domain/subdomain directory. This package installs at the domain root, not a URL subdirectory.
Do not place the private `exampractice` directory inside `public_html`.
The installer refuses an existing populated database and will not replace an installed application.
If an installation fails during table creation, inspect the private installer log, correct
the issue, and retry with a new empty database. The previous database is never deleted automatically.
There are no default production credentials. Local demo accounts are never installed by the web installer.

The browser submits saved answers when the timer ends. The cron also finalizes attempts
whose browser was closed. Without cron, those attempts are finalized on the next relevant request.

Hostinger references: [Laravel deployment](https://www.hostinger.com/support/6152127-how-to-deploy-laravel-8-at-hostinger/),
[Cron jobs](https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/).
These document the hosting layout and scheduler. The installer independently checks this project's PHP requirements.

## Local development

Requirements: PHP 8.4+, Composer 2, Node 22.12+ or 24+, pnpm, and SQLite or MySQL/MariaDB.
Required PHP extensions include ctype, curl, dom, fileinfo, filter, gd, hash, iconv, intl,
mbstring, openssl, pdo, pdo_mysql, pdo_sqlite (local preview), session, simplexml,
tokenizer, xml, xmlreader, xmlwriter, zip, and zlib.

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
pnpm install --frozen-lockfile
pnpm run build
php artisan serve --host=127.0.0.1 --port=8010
```

On Windows, use `Copy-Item .env.example .env` instead of `cp` if needed.
Create `database/database.sqlite` as an empty file before migrating if it does not exist.
`start-local.ps1` can use the isolated PHP runtime prepared in this workspace.

Local demo accounts:

| Role | ID | Password | Access |
| --- | --- | --- | --- |
| Admin | admin | AdminDemo2026! | All administration |
| Student | STU-001 | StudentDemo2026! | Japanese and English programs |
| Student | STU-002 | StudentDemo2026! | One vocabulary package and one material, device lock enabled |

DemoSeeder is limited to local/testing environments. Production installation can use
LearningContentSeeder, which creates content without any user accounts or practice results.
Sample content is illustrative practice content, not official exam material.

## Product behavior

- Admin issues student IDs/passwords. There is no public registration.
- Programs contain modules; modules contain practice packages and learning materials.
- Questions have exactly four choices (A-D), one correct answer, and an optional explanation.
- Content has draft, active, and archived states. Archiving hides content while preserving history.
- Granting a program/module gives access to its active descendants, including future additions.
  Individual package/material grants expose only those items and their navigation ancestors.
- Inactive ancestors hide descendants even when a leaf has a direct grant.
- Manual question entry and fixed-template CSV/XLSX import are available. Choose the destination
  package, download its template, preview all rows, then confirm. Limits: 500 questions, 2 MB,
  eight fixed columns. Invalid rows block the entire import; duplicates are rejected.
- Materials support safe Markdown, editor formatting tools, preview, and student completion tracking.
- Practice sessions snapshot questions, choices, answer keys, explanations, and package settings.
  Later edits do not alter historical results. Draft questions are never included.
- Starting a package again while it is in progress resumes that attempt. Refresh preserves the deadline.
- Answers autosave to the server. Failed network requests are retried while the page remains open.
  Only answers that reach the server before the deadline are counted. Unanswered questions score zero.
- The timer is sticky on desktop/mobile. Expiry and manual submission are idempotent and server-enforced.
- Students can view only their own results; admins can inspect and filter all results and export CSV.
- Device binding uses an encrypted random browser cookie. It identifies a browser installation,
  not a physical device. Incognito, clearing cookies, or changing browsers may require an admin reset.
- Device records contain browser/OS label, IP, last seen, and revocation status. No screen or location monitoring.
- Password changes and admin resets invalidate earlier sessions. Inactive accounts lose access.
- Optional per-package copy deterrence blocks copying/context menus. Screenshots cannot be reliably blocked.
- No watermark or tab-switch warning is included.
- Interface language changes do not translate authored learning content.

## Architecture

- Laravel 13, Blade, Tailwind CSS 4, Alpine.js, Lucide icons
- Eloquent/MySQL-compatible migrations; SQLite for the self-contained local preview
- `LearningAccess`: server-side learning permissions
- `AttemptService`: snapshots, answer saves, deadlines, and scoring inside transactions
- `QuestionImport`: CSV/XLSX parsing and validation using PhpSpreadsheet
- Admin controllers: content, accounts/access/devices, imports, and results
- Laravel authentication, CSRF, login rate limiting, session revocation, safe Markdown output
- Asset photos: Unsplash photo IDs `1493976040374-85c8e12f0c0e`,
  `1507842217343-583bb7270b66`, and `1497366754035-f200968a6e72`

## Verification

```sh
php artisan test --compact
pnpm run build
pnpm run test:browser
```

Browser tests use installed Google Chrome and the demo server at port 8010.
Override `EP_TEST_URL` for another local test server. Run browser tests only against demo data:
they create practice attempts and re-save existing sample question/access settings.
Screenshots are written to `storage/app/qa-screenshots`; traces are retained on failure.

## Build a fresh hosting ZIP

```sh
pnpm run build
php deployment/build-release.php prepare
cd ../delivery/ExamPractice-Hostinger/exampractice
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
cd ../../../exampractice
php deployment/build-release.php pack
```

The build creates a new installation code. Production ZIPs never include the local `.env`,
SQLite demo database, logs, sessions, test reports, or Node dependencies.
Use a clean staging folder for a new release after testing an installed copy.

## Operations

Back up the production database and private `.env` together. Keep APP_KEY unchanged during upgrades.
Use `APP_DEBUG=false` and HTTPS in production. Keep storage and bootstrap/cache writable by PHP;
do not use globally writable permissions. The app requires no persistent Node process or queue worker.
For a terminal-based installation, `php artisan app:create-admin` creates a new admin interactively.
Hosting/domain credentials are intentionally not stored in the source or delivered ZIP.
