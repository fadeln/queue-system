# Online Queue Application — Todos

Stack: Laravel 13 · PHP 8.5 · PostgreSQL · React/Inertia · Laravel Reverb

Legend: [ ] pending  [~] in progress  [x] done

## Phase 0 — PostgreSQL driver (blocker)
- [x] 0.1 Confirm php.exe build: herd-lite php is STATIC (no php8.dll -> cannot load DLL ext)
- [x] 0.2 Downloaded full php-8.5.0-nts-Win32-vs17-x64 build (supports pdo_pgsql)
- [x] 0.3 Installed full PHP to C:\php\8.5 (own php8.dll + ext\php_pgsql.dll)
- [x] 0.4 Enabled extension=pgsql / extension=pdo_pgsql in C:\php\8.5\php.ini
- [x] 0.5 php -m shows pdo_pgsql; Postgres connected OK
- [x] 0.6 ichiran_pg bridge not host-reachable -> started dedicated container tugas-antrian-pg on 127.0.0.1:5433 (db tugas_antrian)

## Phase 1 — Database design
- [x] 1.1 Migration: add is_admin boolean to users
- [x] 1.2 Migration: create queue_entries (position, status, served_at, timestamps)
- [x] 1.3 QueueEntry model + status enum
- [x] 1.4 Run migrations against PostgreSQL
- [x] 1.5 Export PostgreSQL CREATE TABLE statements for submission (docs/database-design.md)

## Phase 2 — Back-end (admin panel)
- [x] 2.1 Admin middleware (guard on is_admin)
- [x] 2.2 QueueController: index / next / prev / markCalled (Inertia)
- [x] 2.3 Broadcast queue.updated on each action
- [x] 2.4 Seeder: admin user + ~10 sample queue entries

## Phase 3 — Front-end (React + Inertia + Reverb)
- [x] 3.1 Install reverb, echo, pusher-js; reverb:install
- [x] 3.2 .env: Postgres DSN + BROADCAST_CONNECTION=reverb
- [x] 3.3 Customer display page: current number + live list via Echo
- [x] 3.4 Admin board page: login + list + Next/Prev/mark
- [x] 3.5 QueueUpdated uses ShouldBroadcastNow (broadcast dispatched synchronously because QUEUE_CONNECTION=database and no queue worker runs)

## Phase 4 — Tooling, run & verify
- [x] 4.1 Start reverb:serve (background) alongside app server
- [x] 4.2 Build assets (Vite); confirm localhost:8000 up
- [x] 4.3 End-to-end: realtime queue.updated received via WebSocket client; 7/7 feature tests pass
- [x] 4.4 Wrap-up: README (steps, schema, how to run) + docs/database-design.md
- [x] 4.5 Wayfinder gotcha: regenerate TS routes via `npm run build` (formVariants), NOT `php artisan wayfinder:generate` (drops form variants and breaks auth pages)

## Phase 5 — Admin dashboard
- [x] 5.1 Home (/) now serves the customer queue display
- [x] 5.2 Dashboard endpoint: QueueController@dashboard (current/waiting/servedToday)
- [x] 5.3 Simple dashboard UI: "now serving" + 2 stat cards + link to board
- [x] 5.4 Live refresh on queue.updated (Echo listener)
- [x] 5.5 Tests: 8/8 queue feature tests + full suite 47/47; Pint + tsc clean
- [x] 5.6 DashboardTest: guests redirected to login + authenticated users can visit dashboard
- [x] 5.7 Admin nav "Kelola Antrian" added to app-sidebar

## Phase 6 — Add & cancel queue entries (soft, no edit)
- [x] 6.1 QueueEntry::nextCode() auto-generates A-XXX from max position
- [x] 6.2 QueueController: store() (new pending entry) + cancel() (pending -> skipped, soft)
- [x] 6.3 Routes: POST admin/queue, POST admin/queue/{entry}/cancel
- [x] 6.4 Board UI: "+ Tambah" dialog + "Batal" (X) per pending row + skipped styling
- [x] 6.5 QueueEntrySeeder: varied statuses (1 calling, 2 called, 1 skipped, 6 pending)
- [x] 6.6 Tests: 6/6 QueueCrudTest; full suite 53/53; Pint + tsc clean

## Phase 7 — Documentation polish
- [~] 7.1 Move TODOLIST.md to docs/TODOLIST.md; update README link
- [~] 7.2 Add docs/roadmap.md (English, hospital roadmap A-E)
- [~] 7.3 Initial git commit (single baseline commit, English conventional message)