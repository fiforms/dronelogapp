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

Create your first user account:

```bash
php artisan user:create
```

Then start both dev servers:

```bash
php artisan serve   # API → http://localhost:8000
npm run dev         # SPA → http://localhost:5173
```

Visit `http://localhost:5173` and log in with the credentials you just created.

### User management

```bash
php artisan user:create                      # create a new account
php artisan user:reset-password              # reset a password (prompts for email)
php artisan user:reset-password you@email    # reset a password (email as argument)
```

---

## Google OAuth Setup

Users can sign in with their Google account. OAuth is intended for production use on a real domain — Google requires a publicly accessible HTTPS redirect URI.

### 1. Create credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/) → **APIs & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth 2.0 Client ID**
3. Set application type to **Web application**
4. Under **Authorized redirect URIs**, add your production callback URL:
   ```
   https://yourdomain.example.com/auth/google/callback
   ```
5. Copy the **Client ID** and **Client Secret**

### 2. Add to `.env`

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://yourdomain.example.com/auth/google/callback
```

Also make sure `APP_URL` and `FRONTEND_URL` are set to your domain:

```env
APP_URL=https://yourdomain.example.com
FRONTEND_URL=https://yourdomain.example.com
```

Then clear the config cache:

```bash
php artisan config:cache
```

### Account collision policy

If someone attempts to sign in with Google using an email address that already has a password-based account, the login **hard fails** with an error message. Accounts are never auto-linked. The user must sign in with their password.

---

## Production Deployment

### Prerequisites

- PHP 8.4+ with extensions: `pdo_mysql`, `mbstring`, `xml`, `curl`
- Composer 2
- Node 20+ (only needed to build the frontend; not required on the server after that)
- MySQL 8+
- A web server (nginx or Apache) pointed at `public/`
- HTTPS — required for PWA install and the Geolocation API

### First deploy

```bash
git clone <repo> /var/www/dronelogapp
cd /var/www/dronelogapp

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at minimum:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.example.com
FRONTEND_URL=https://yourdomain.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=dronelog
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SANCTUM_STATEFUL_DOMAINS=yourdomain.example.com
SESSION_DOMAIN=.yourdomain.example.com
```

Then run:

```bash
php artisan migrate --force

# Create the storage directory structure Laravel needs
mkdir -p storage/framework/{cache,sessions,testing,views}
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache

php artisan user:create   # create your first account
```

### Subsequent deploys

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Google OAuth (optional)

See the [Google OAuth Setup](#google-oauth-setup) section above. Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `.env`, then run `php artisan config:cache`.

**HTTPS is required** for PWA install and the Geolocation API.

---

## PWA Installation

- **Android (Chrome):** Install prompt appears automatically. Or: menu → "Add to Home Screen".
- **iOS (Safari):** Share → "Add to Home Screen".

Once installed the app runs standalone (no browser chrome) and works fully offline.

---

## Customizing the Pre-Flight Checklist

The default 4-item checklist is created automatically when a user registers. Edit it in the **Checklists** section of the app. You can add/remove items, create multiple templates, and enable optional comment boxes per item.
