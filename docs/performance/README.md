# Performance review

Measured 2026-11-14 against main at 7cfb1c7. PHP 8.4.27 CLI, Laravel 12,
Filament 4, MySQL 8.0 in a disposable Docker container on the same cloud host.
No production database, credentials, schema, or data were changed.

## Findings and implementation

- **Project progress:** each access issued two counts, including repeated accesses
  for the same table cell's text/color. `withProgressCounts` batches unfiltered
  total/completed counts into the project query; ProjectResource and Reporting
  opt in. The accessor still performs live queries elsewhere. Completion continues
  to use `is_completed`, not status names. Filtered ticket collections are never
  mistaken for the complete project. Requery after writes as for any Eloquent
  aggregate; there is no persisted cache and no authorization cache.
- **Reporting:** eager-loading projects alone did not prevent progress accessor
  queries for each task. The project eager load now includes progress counts.
- **Dashboard project timeline:** range calculation reloaded the full project/task/
  status/assignee/priority graph already loaded by the renderer. Reuse that graph
  within this render only; filtering and subsequent requests remain fresh.
- **Task timeline:** status order was fetched once per task. Fetch it once per data
  calculation and pass the collection to the existing progress calculation.
- **Board:** assignee options hydrated all tasks and assignees, then queried users
  again. A scoped `whereHas(assignedTickets)` returns distinct users directly.
  Selecting a project no longer eagerly loads tasks which the board queries again.
  Status grouping, assignment filters, sorting, drag/drop and authorization remain.
- **Member stats:** project IDs were materialized and two queries scanned the same
  visible task set. Use an authorization-scoped subquery and one conditional count.
  Existing status-name rules for overdue/completed stats are intentionally retained.
- **Monthly trend:** use MIN(created_at) instead of hydrating the earliest task.
  Retain full history and role filters. SQLite month grouping was unsupported;
  add a SQLite expression while preserving MySQL/PostgreSQL expressions.
- **Bootstrap:** config/excel.php eagerly evaluated classes absent from both
  composer.json and composer.lock. Optional Excel settings now load only when its
  packages exist. The application's CSV import/export does not use these classes.
  No Excel feature or dependency was removed.
- **Docker/runtime:** PHP 8.3 could not satisfy locked Symfony dependencies requiring
  >=8.4.1. Align Docker with PHP 8.4, install OPcache, compile extensions with two
  jobs. Development OPcache validates timestamps on every request. No debug flag,
  configuration cache, session store, queue driver or PHP-FPM pool limit was changed.
- **Nginx:** only content-hashed Vite `/build/assets/` receive immutable caching;
  HTML, authentication, Livewire and unhashed Filament assets are unaffected.

## Measurements

`tools/benchmark.php` creates 20 projects, 200 tasks, 20 completed statuses and one
super_admin/member, then measures five repetitions per data path. Values below
are the median elapsed sample; query count and DB time belong to that sample.
A new task timeline component is created per sample to avoid Livewire's computed
property cache masking queries. Role checks are warm. PHP OPcache CLI is off.
Both revisions ran on equivalent freshly migrated schemas on the same MySQL
container, with the same bootstrap fix applied to baseline. Baseline app files
were read from git HEAD, temporarily substituted, then restored before the after
run. This is **not** an HTTP/browser benchmark or a large customer workload.

| Data path | Queries before → after | Elapsed ms before → after | DB ms before → after |
|---|---:|---:|---:|
| Project progress, 20 rows × 5 accesses | 201 → 1 | 98.561 → 1.712 | 76.40 → 1.04 |
| Reporting, 25 task rows | 54 → 4 | 29.925 → 3.790 | 22.13 → 2.14 |
| Dashboard project timeline | 10 → 6 | 41.107 → 30.765 | 7.93 → 4.49 |
| Board assignee options | 3 → 1 | 2.007 → 0.846 | 1.33 → 0.66 |
| Task timeline, 10 tasks | 12 → 3 | 10.531 → 3.103 | 5.14 → 1.50 |

Process peak memory: 57,147,392 → 55,050,240 bytes (includes bootstrap/fixtures;
not isolated per request). Raw MySQL and SQLite results are committed alongside
this report. Timing is sensitive to host load; query counts are the primary
regression guard. Asset baseline: app CSS 68,297 bytes, theme CSS 645,969 bytes, app JS
35,279 bytes (749,545 bytes total). After the rendering tests and final build:
app CSS 68,857 bytes, theme/JS unchanged (750,105 bytes total). Tailwind scans
compiled Blade views, so generated view state affects the app CSS. No asset-size
reduction is claimed.

Initial dashboard HTTP time, full board/list/timeline HTTP time, browser rendering,
and Livewire network latency: **NOT MEASURED**. Successful HTTP/Livewire tests
verify behavior, not network latency. The dashboard uses lazy widgets, so an
initial HTML request alone would not measure complete dashboard readiness.

## Validation and existing defects

Baseline after fixing Excel bootstrap: 29 passed, 10 failed (57 assertions).
After: MySQL full suite 36 passed, the same 10 failed (86 assertions).
Seven new regression tests pass (29 assertions), covering query ceilings,
completion rounding/empty projects, constrained relation safety, member isolation,
fresh reads after writes, board assignees, monthly grouping, ordered timeline
progress, authenticated dashboard/project/task HTML and timeline Livewire action.
The existing Google integration tests pass with mocked HTTP failure/success;
Google OAuth/Calendar/Drive with real credentials and SMTP: **NOT TESTED**.

Existing failures, reproduced before performance changes:

- AttachmentTest creates a task without required ticket_status_id (one failure).
- DivisionManagementTest expects an immediately hydrated database default and
  role-only access without explicit permissions (three failures).
- OrganizationInformationTest expects role-only access (two failures).
- PolicyOwnershipTest exposes existing overly broad task/comment/project/
  notification authorization (four failures). These are real security concerns;
  do not consider this PR a security fix or all authorization validated.

No assertions or permission checks were removed to make tests pass. These defects
remain separate from this optimization and prevent a clean full-suite result.

## Further audit decisions

RecentActivityTable already eager-loads ticket.project/user/status and paginates.
TicketsPerProjectChart and UserStatisticsChart already use withCount. Preserve
30s/60s polling to keep the existing freshness contract; no measured evidence
justifies weakening it. Existing widget lazy loading and panel SPA are retained.
Session/cache use database and mail uses a database queue/log driver; no external
Redis/Octane introduced. Providers do not make outbound requests during boot.
Google Calendar is explicitly invoked by actions; its token refresh/API latency
can still affect those actions and needs credentialed profiling.

Existing indexes cover project/status, project/created, memberships and assignees.
No new migration without a representative MySQL EXPLAIN and workload demonstrating
need. Charts and timelines still load full visible histories; safely windowing
these requires a separate UI/product decision. Docker Desktop bind mounts, remote
Gantt/Tailwind/Alpine CDNs and the large theme remain candidates for browser/host
profiling. FPM concurrency sizing requires real RSS/load evidence and was not
changed speculatively. No cross-request cache was introduced: invalidation for
role, membership and task mutations would outweigh the demonstrated benefit.

## Reproduce: native development

Requires PHP >=8.4.1 with Composer platform extensions, Node/npm, and configured .env.
On the onboarding machine first `source /workspace/.prism-tools/activate`.

```sh
composer install --prefer-dist --no-interaction
npm ci
npm run build
php vendor/bin/pest
php tools/benchmark.php
php artisan migrate
php artisan serve
# separate terminal, required for queued notifications
php artisan queue:work --tries=1
# optional live asset development, instead of compiled build
npm run dev
```

The default benchmark always uses disposable in-memory SQLite. For MySQL, create
an **empty disposable** database whose name starts `prism_perf_`, provide DB_*
variables for it and run `php tools/benchmark.php --mysql-test`. The harness
refuses nonempty MySQL schemas and other deployment databases. Do not run this
while Laravel configuration is cached. Never migrate:fresh/reset a real database.

## Reproduce: Windows / Docker Desktop

PowerShell from the checkout, with .env already configured for MySQL (`DB_HOST=db`),
using a non-root application DB_USERNAME and a strong locally supplied DB_PASSWORD:

```powershell
docker compose build app
docker compose up -d db app nginx
docker compose exec app composer install --prefer-dist --no-interaction
npm ci
npm run build
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
docker compose exec app php artisan queue:work --tries=1
```

Run tests against a separate disposable database/configuration; `phpunit.xml`
uses in-memory SQLite, so either install pdo_sqlite for Docker tests or provide
DB_CONNECTION/DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD for a disposable MySQL
schema when invoking `docker compose exec app php vendor/bin/pest`.
The Docker image intentionally retains the application's MySQL engine.
Host vendor/node_modules bind mounts may remain slower on Docker Desktop; compare
with a checkout inside WSL2's Linux filesystem before changing volume ownership.

## Production-like local profile and browser verification

Use an isolated local copy and database, retain the app's production MySQL engine,
queue worker and HTTP stack. Supply a real APP_KEY and local test credentials
securely. Set APP_ENV=production and APP_DEBUG=false **only in that copy**; development
stays debug-enabled. Build assets and run:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Restart FPM/queue workers. Do not run the dev Vite server for this comparison.
Log in as both member and super_admin, open DevTools Network with Preserve log,
record five dashboard loads (including lazy `/livewire/update` widget requests),
then board, tasks, reporting and both timelines. Capture document TTFB, full
waterfall, Livewire payload/timing and transferred bytes separately for cold
browser cache and warm navigation. Exercise filters, drag/drop, assignment, edits,
comments, attachments, logout and Google Calendar/Drive with local test credentials.
Use the same dataset/user, machine, image, debug flags and caches for both branches.
Do not infer full page latency from the data-path numbers above.
After profiling an isolated copy, `php artisan optimize:clear` before returning to
active development. Never commit .env, cookies, keys or access tokens.

Container validation completed: `docker build -t prism-perf-review .`,
`docker compose config --quiet`, image `composer check-platform-reqs`, `php-fpm -t`,
and `nginx:alpine nginx -t` passed. The baseline full suite was also rerun on
MySQL (29 passed, the same 10 failed, 57 assertions). New tests plus Google mocks:
9 passed, 32 assertions on SQLite; the full MySQL suite includes these tests.
No full production Compose deployment or Windows filesystem benchmark was run.
