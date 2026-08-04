# Online Queue Application

A real-time queue management system built with **Laravel 13**, **PHP 8.5**,
**PostgreSQL**, **React + Inertia**, and **Laravel Reverb** (WebSockets).

Admins manage the queue from a dedicated panel (call the next number, recall
the previous one, mark tickets served, add or cancel tickets), while customers
see the current number and the waiting list on a public display that updates
in real time — no page refresh required.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running the Application](#running-the-application)
- [Seed Accounts](#seed-accounts)
- [Try It Out](#try-it-out)
- [Tests](#tests)
- [Documentation](#documentation)

## Features

### Admin Panel (`/admin/queue`)

- Login via **Laravel Fortify** — only `is_admin` users can access the panel.
- Queue board showing the full list of tickets.
- **Next** — call the next waiting ticket (the current one becomes `called`).
- **Prev** — recall the last served ticket (safe recall, not "un-serve").
- **Mark served** — complete a single waiting ticket.
- **Add** — create a new ticket with an auto-generated code (`A-011` …).
- **Cancel** — skip a waiting ticket (soft, status `skipped`; history kept).
- **Reset** — return every ticket to `pending`.

### Customer Display (`/queue`)

- Shows the **current number** being served in large type.
- Shows the **waiting list** and recently served numbers.
- Updates **in real time** via WebSockets (Reverb + Echo) — no refresh.

### Dashboard (`/dashboard`)

- Now-serving number, waiting count, and served-today count.
- Refreshes live when the queue changes.

## Tech Stack

| Layer | Tech |
| --- | --- |
| Back-end | Laravel 13, PHP 8.5 |
| Authentication | Laravel Fortify |
| Database | PostgreSQL 17 |
| Front-end | React 19, Inertia 3, Tailwind CSS 4, shadcn/ui |
| Realtime | Laravel Reverb + laravel-echo + pusher-js |
| Routing (TS) | Laravel Wayfinder |
| Tooling | Pint, PHPStan, ESLint, Prettier, Vite |

## Project Structure

```
app/
├── Events/QueueUpdated.php        # broadcast snapshot on channel 'queue'
├── Http/
│   ├── Controllers/QueueController.php
│   └── Middleware/EnsureUserIsAdmin.php
└── Models/QueueEntry.php
database/
├── migrations/                    # users.is_admin + queue_entries
└── seeders/                       # admin user + queue tickets
resources/js/
├── echo.ts                        # Reverb/Echo client
└── pages/
    ├── queue/display.tsx          # customer display
    ├── queue/board.tsx            # admin board
    └── dashboard.tsx
docs/                              # database design, roadmap, TODOLIST
```

## Prerequisites

- PHP 8.3+ with the `pdo_pgsql` extension (this project uses `C:\php\8.5`)
- Composer 2
- Node.js + npm
- PostgreSQL 17 — this project uses a Docker container `tugas-antrian-pg`
  exposed on `127.0.0.1:5433`

## Installation

```bash
composer install
npm install
cp .env.example .env    # then apply the values below
php artisan key:generate
php artisan migrate --seed
npm run build           # compile frontend assets
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

## Running the Application

Two processes are required (use two terminals):

```bash
# Terminal 1 — web app
php artisan serve

# Terminal 2 — websocket server
php artisan reverb:start
```

While developing the frontend, also run `npm run dev` in a third terminal.

## Seed Accounts

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password` |
| User | `test@example.com` | `password` |

The seeder also creates 10 queue tickets (`A-001` … `A-010`); `A-001` starts
as the number currently being served and the rest are waiting (`pending`).

## Try It Out

1. Open http://localhost:8000/queue (customer display) in one tab.
2. Open http://localhost:8000/login in another, sign in as
   `admin@example.com` / `password`, then open **Kelola Antrian**.
3. Press **Berikutnya (Next)** on the board — the number on the display tab
   changes instantly without a refresh.

## Tests

```bash
php artisan test          # 54 tests, includes queue workflow + CRUD + realtime
```


