# DroneLog

A Part 107-compliant drone flight logging PWA. Log flights in the field with or without internet connectivity — records sync to a Laravel backend when you're back online.

See [ARCHITECTURE.md](ARCHITECTURE.md) for the full design and [CLAUDE.md](CLAUDE.md) for developer context.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11 + Breeze API (Sanctum cookie auth) |
| Database | SQLite (dev) / MySQL (production) |
| Frontend | Vue 3 + Vite |
| State | Pinia |
| Offline storage | Dexie.js (IndexedDB) |
| PWA | vite-plugin-pwa (Workbox) |
| Styling | Tailwind CSS |

---

## Quick Start (Local Dev)

### Prerequisites

- PHP 8.4+ with extensions: `pdo_sqlite`, `pdo_mysql`
- Composer 2
- Node 20+

### Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
```

Then start both dev servers:

```bash
php artisan serve   # API → http://localhost:8000
npm run dev         # SPA → http://localhost:5173
```

Visit `http://localhost:5173` and log in with:
- **Email:** `pilot@example.com`
- **Password:** `password`

---

## Production Deployment

1. Set `APP_ENV=production`, `APP_DEBUG=false`
2. Configure MySQL in `.env`
3. Set `APP_URL` and `SANCTUM_STATEFUL_DOMAINS` to your production domain
4. Run:
   ```bash
   npm run build
   php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
5. Point nginx/Apache web root to `public/`

**HTTPS is required** for PWA install and the Geolocation API.

---

## PWA Installation

- **Android (Chrome):** Install prompt appears automatically. Or: menu → "Add to Home Screen".
- **iOS (Safari):** Share → "Add to Home Screen".

Once installed the app runs standalone (no browser chrome) and works fully offline.

---

## Customizing the Pre-Flight Checklist

The default 4-item checklist is created automatically when a user registers. Edit it in the **Checklists** section of the app. You can add/remove items, create multiple templates, and enable optional comment boxes per item.
