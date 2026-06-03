# DroneLog — Claude Project Context

## What this project is

A Part 107-compliant drone flight logging PWA. Laravel 11 backend + Vue 3 frontend. Fully offline — flights are written to IndexedDB (Dexie.js) the moment they're created and synced to the server when the device comes back online.

See [ARCHITECTURE.md](ARCHITECTURE.md) for the full design write-up.

---

## Dev environment

```bash
# Start both servers in separate terminals
php artisan serve        # API at http://localhost:8000
npm run dev              # Vite SPA at http://localhost:5173
```

Local database: SQLite (`database/database.sqlite`). MySQL in production.

Dev login: `pilot@example.com` / `password`

Reset and re-seed everything:
```bash
php artisan migrate:fresh --seed
```

Build frontend for production:
```bash
npm run build
```

Run backend tests:
```bash
php artisan test
```

---

## Stack at a glance

| Concern | Tool |
|---|---|
| PHP framework | Laravel 11 |
| Auth | Sanctum SPA cookies (Breeze API stack) |
| Database | SQLite dev, MySQL prod — all schema via migrations |
| Frontend | Vue 3, Vite, Pinia, Vue Router |
| Offline storage | Dexie.js (IndexedDB) |
| PWA / SW | vite-plugin-pwa (Workbox) |
| Styling | Tailwind CSS + `@tailwindcss/forms` |

---

## Key file locations

### Backend
| Path | What it does |
|---|---|
| `app/Models/Flight.php` | Central model — enum casts, all relationships |
| `app/Models/Team.php` | Team owns all resources; User has `currentTeam()` |
| `app/Enums/` | `PurposeEnum`, `LaancStatusEnum`, `TeamRoleEnum` |
| `app/Http/Controllers/Api/` | All API controllers — scoped to `currentTeam()` |
| `app/Http/Controllers/Api/SyncController.php` | Bulk flight upsert — the offline sync endpoint |
| `app/Http/Requests/StoreFlightRequest.php` | Full flight validation including nested accessories + checklist |
| `app/Http/Resources/FlightResource.php` | JSON shape for flight responses |
| `database/migrations/` | All table definitions |
| `database/seeders/DatabaseSeeder.php` | Creates test user + team + calls other seeders |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | Auto-creates team + default checklist on registration |
| `routes/api.php` | All 33 API routes |
| `routes/web.php` | SPA catch-all → `view('app')` |

### Frontend
| Path | What it does |
|---|---|
| `resources/js/db.js` | Dexie IndexedDB schema — change here when adding flight fields |
| `resources/js/sync.js` | Sync engine: reads Dexie, POSTs to `/api/v1/sync/flights` |
| `resources/js/stores/flights.js` | Flight state — writes to IndexedDB, triggers sync |
| `resources/js/stores/fleet.js` | Drone/battery/accessory CRUD + IndexedDB cache |
| `resources/js/stores/sync.js` | Online detection, pending count, `syncNow()` |
| `resources/js/stores/auth.js` | User state, login/logout, fetchUser |
| `resources/js/router/index.js` | Routes + auth guard |
| `resources/js/views/FlightStartView.vue` | Two-step flight wizard (details → checklist → launch) |
| `vite.config.js` | Vite + Vue plugin + PWA manifest + Workbox caching rules |
| `resources/css/app.css` | Tailwind + custom utility classes (`.btn-primary`, `.card`, `.input-field`, etc.) |

---

## Architecture invariants to preserve

**All resources are team-scoped.** Controllers always call `$request->user()->currentTeam()` and scope queries to that team's ID. Never query drones/batteries/accessories/flights without a team constraint.

**Flights must have a `client_uuid`.** This is the deduplication key for offline sync. Every flight created client-side gets a `uuidv4()` before it's written to IndexedDB. The server uses `updateOrCreate(['client_uuid' => ...])`.

**IndexedDB is the source of truth for the last 30 days.** Don't add code that skips the local write — the app must work without a network connection. Always write to Dexie first, sync second.

**The sync endpoint is idempotent.** Posting the same flight twice is safe. Don't add logic that breaks this.

**New flight fields require 8 changes** — see [ARCHITECTURE.md § Adding a New Field](ARCHITECTURE.md#adding-a-new-field-to-flights) for the full checklist.

---

## Patterns used throughout

**Team authorization in controllers:**
```php
private function authorizeTeam(Request $request, int $teamId): void
{
    abort_unless($request->user()->currentTeam()->id === $teamId, 403);
}
```

**Fleet CRUD stores (Pinia):**
All three fleet stores (`fleet.js`) follow the same pattern: `fetchAll()` fetches from API and caches to IndexedDB; `save{Model}(data)` does POST or PUT based on whether `data.id` exists; `delete{Model}(id)` calls DELETE then removes from the reactive array.

**Vue modal forms:**
Fleet management views (Drones, Batteries, Accessories) use a local `showForm` ref + a `form` reactive object. The modal renders in a `fixed inset-0` overlay. Pattern: `openForm(item = null)` sets editing/form state, `save()` calls the store action.

**Route lazy-loading:**
All views are lazy-loaded in the router with `() => import('../views/XView.vue')`. Don't use eager imports — bundle splitting is intentional.

---

## CSS conventions

Custom utility classes in `resources/css/app.css`:

| Class | Use |
|---|---|
| `.btn-primary` | Blue full-width action button |
| `.btn-secondary` | Slate secondary button |
| `.btn-danger` | Red destructive button |
| `.card` | Rounded dark panel (`bg-slate-800`) |
| `.input-field` | Dark text input / select / textarea |
| `.label` | Form label above a field |
| `.section-title` | Section heading (`text-lg font-bold`) |

Dark-first design. Base: `bg-slate-900` body, `bg-slate-800` cards. Primary accent: `blue-600` / `blue-400`. Success: `emerald`. Warning: `amber`. Danger: `red`.

---

## Database notes

Both SQLite (dev) and MySQL (prod) are supported. All schema is in migrations — never alter tables manually. Use `php artisan make:migration` for any schema change.

Enum columns are stored as VARCHAR in the database (not MySQL ENUM type) — this keeps them portable and lets the PHP enum handle validation.

The `team_user` pivot includes `timestamps()` and a `role` column. The `flight_accessories` and `flight_checklists` pivots do not have timestamps (`$timestamps = false` on those models).

---

## Registering a new user (how teams work)

`RegisteredUserController::store()` (Breeze auth):
1. Creates the `User`
2. Creates a `Team` named `"{name}'s Team"`
3. Attaches user to team with `role = 'owner'`
4. Creates the default checklist template + 4 items for that team
5. Fires `Registered` event and logs the user in

The `DatabaseSeeder` does the same thing manually for the dev test user.
