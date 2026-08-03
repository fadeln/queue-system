# Online Queue Application

A real-time queue management app built with Laravel 13, PHP 8.5, PostgreSQL,
React + Inertia, and Laravel Reverb (WebSockets).

## Features

- **Admin panel** (`/admin/queue`)
  - Login via Laravel Fortify (only `is_admin` users can access).
  - Queue board with the list of tickets.
  - **Next** — call the next waiting ticket (current becomes `called`).
  - **Prev** — recall the last served ticket.
  - Mark individual tickets as served; reset the whole queue.
  - **Add** — create a new ticket with an auto-generated code (`A-011` …).
  - **Cancel** — skip a waiting ticket (soft, status `skipped`; history kept).
- **Customer display** (`/queue`)
  - Shows the current number and the queue list.
  - Updates in real-time via WebSockets (Reverb + Echo) — no page refresh.

## Stack

| Layer | Tech |
| --- | --- |
| Back-end | Laravel 13, PHP 8.5 |
| Database | PostgreSQL 17 |
| Front-end | React 19, Inertia 3, Tailwind 4, shadcn/ui |
| Realtime | Laravel Reverb + laravel-echo + pusher-js |

## Prerequisites

- PHP 8.2+ with `pdo_pgsql` (this project uses `C:\php\8.5`)
- Composer 2
- Node.js + npm
- PostgreSQL 17 (a running server; this project uses a Docker container
  `tugas-antrian-pg` on `127.0.0.1:5433`)

## Setup

```bash
composer install
npm install
cp .env.example .env      # then edit the DB_* / REVERB_* values
php artisan key:generate
php artisan migrate --seed
npm run build             # compile frontend assets
```

`.env` database and broadcasting keys:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=tugas_antrian
DB_USERNAME=postgres
DB_PASSWORD=secret

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Run

Three processes (use two terminals, or the background approach shown below):

```bash
# Terminal 1 — web app
php artisan serve

# Terminal 2 — websocket server
php artisan reverb:start

# If developing the frontend, also run: npm run dev
```

## Seed accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password` |
| User | `test@example.com` | `password` |

The seeder also creates 10 queue tickets (`A-001` … `A-010`) with varied
statuses: one currently being served, two already served, one skipped, and
the rest waiting.

## Try it

1. Open http://localhost:8000/queue (customer display) in one tab.
2. Open http://localhost:8000/login in another, sign in as
   `admin@example.com` / `password`, then open **Kelola Antrian**.
3. Press **Berikutnya (Next)** on the board — the number on the display tab
   changes instantly without a refresh.

## Tests

```bash
php artisan test          # 53 tests, includes queue workflow + CRUD + realtime
```

## Documentation

- `docs/database-design.md` — schema, ER model, PostgreSQL `CREATE TABLE` SQL
  and design justifications (assignment deliverable).
- `docs/TODOLIST.md` — step tracker used during development.
