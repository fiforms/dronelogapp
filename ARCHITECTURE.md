# DroneLog Architecture

---

## Overview

DroneLog is a **Laravel 11 + Vue 3 SPA** with offline-first capabilities delivered as a PWA. The Laravel backend exposes a JSON REST API consumed by the Vue frontend. Flights are captured to the browser's IndexedDB immediately (even with no connectivity) and synced to the server in the background whenever the device is online.

---

## Data Model

### Tables and their purpose

```
users                 — pilots (email/password via Breeze/Sanctum)
teams                 — one team per user by default; supports future multi-pilot crews
team_user             — pivot: which users belong to which team, with role (owner|member)

drones                — belongs to team; tracks model, serial, FAA registration
batteries             — belongs to team; numbered (e.g. "Battery #1"), tracks cycles
accessories           — belongs to team; typed (filter|light|other)

checklist_templates   — belongs to team; one marked is_default per team
checklist_items       — belongs to template; ordered by sort_order

flights               — belongs to team + user; central log record
flight_accessories    — pivot: which accessories flew on which flight
flight_checklists     — per-item checklist result for a flight (checked + comment)
```

### Flight table columns of note

| Column | Type | Notes |
|---|---|---|
| `client_uuid` | char(36) unique | UUID generated client-side; used for server-side deduplication on sync |
| `started_at` | timestamp | Indexed — used for 30-day range queries |
| `purpose` | enum (recreational\|commercial) | Cast to `PurposeEnum` |
| `laanc_status` | enum (received\|not_needed\|na) | Cast to `LaancStatusEnum` |
| `synced_at` | timestamp nullable | Set by the server on receipt; null means never synced |

### Team scoping

Every controller action resolves the authenticated user's current team via `$request->user()->currentTeam()` and scopes all queries to that team's ID. This prevents data leakage between teams. Authorization uses `abort_unless($resource->team_id === $team->id, 403)`.

`User::currentTeam()` returns the first team from the `teams` relationship. For solo users (default) this is always their personal team. Multi-team support (team switching) is a future feature — the data model already supports it.

### Enums

Three PHP 8.1 backed string enums in `app/Enums/`:

- `PurposeEnum` — `recreational`, `commercial`
- `LaancStatusEnum` — `received`, `not_needed`, `na`
- `TeamRoleEnum` — `owner`, `member`

`PurposeEnum` and `LaancStatusEnum` are cast on the `Flight` model. API responses serialize to their string values.

---

## API Design

### Authentication

Sanctum SPA cookie auth. The Vue app calls `GET /sanctum/csrf-cookie` before the first mutating request to receive the `XSRF-TOKEN` cookie. Axios is configured with `withCredentials: true` and `withXSRFToken: true` globally in `resources/js/bootstrap.js`.

All API routes under `/api/v1` require `auth:sanctum`. Auth routes (`POST /login`, `POST /logout`, `GET /api/user`) are installed by Breeze at the root level.

### Route structure

```
/api/user                           GET    — authenticated user info
/api/v1/drones                      GET POST
/api/v1/drones/{drone}              GET PUT DELETE
/api/v1/batteries                   GET POST
/api/v1/batteries/{battery}         GET PUT DELETE
/api/v1/accessories                 GET POST
/api/v1/accessories/{accessory}     GET PUT DELETE
/api/v1/checklist-templates         GET POST
/api/v1/checklist-templates/{id}    GET PUT DELETE
/api/v1/checklist-templates/{id}/items   GET POST
/api/v1/items/{item}                GET PUT DELETE   ← shallow
/api/v1/flights                     GET POST         ← supports ?from=&to=&page=&per_page=
/api/v1/flights/{flight}            GET PUT DELETE
/api/v1/flights/{flight}/end        PUT              ← sets ended_at, accepts post_flight_notes
/api/v1/sync/flights                POST             ← bulk upsert
/api/v1/sync/status                 GET
```

### JSON response shape

All resources use Laravel JSON API Resources. Collections wrap data in `{ "data": [...], "links": {...}, "meta": {...} }`. Single resources return `{ "data": {...} }`.

Flight `store` returns 201 on creation, 200 if the `client_uuid` already exists (idempotent). The sync endpoint returns `{ synced: N, ids: { [client_uuid]: server_id }, errors: [] }`.

---

## Offline-First Architecture

### Write path (creating a flight)

```
User taps "Launch Flight"
  → stores/flights.js: startFlight(data)
      → Writes to IndexedDB (Dexie) with synced=0, client_uuid=uuidv4()
      → Calls registerBackgroundSync() → registers SW sync tag "sync-flights"
  → Navigates to /flights/:localId/active
  → Timer starts from started_at

User taps "End Flight"
  → stores/flights.js: endFlight(id, { ended_at, post_flight_notes })
      → Updates IndexedDB record, synced=0
      → Calls registerBackgroundSync() again
  → Navigates to FlightDetailView (reads from IndexedDB)
```

### Sync trigger points

| Event | Action |
|---|---|
| Background Sync API fires (Chrome/Android) | Service worker calls `syncPendingFlights` even with tab closed |
| `window.online` event | `stores/sync.js: onOnline()` → `syncNow()` |
| `document.visibilitychange` (tab focused) | `syncNow()` if online |
| App init (boot) | `syncNow()` if online |

The iOS Safari fallback (no Background Sync API) relies on the last three events. This covers the common pattern of flying in the field → flying home → opening the app on WiFi.

### Sync engine (`resources/js/sync.js`)

`syncPendingFlights(axios)`:
1. Queries IndexedDB for all rows where `synced = 0`
2. Maps them to the API payload shape (including nested `accessories[]` and `checklist[]`)
3. POSTs to `POST /api/v1/sync/flights`
4. On success: updates each row with `synced = 1` and `server_id` from the response

`syncFleetFromServer(axios)`:
1. Fetches drones, batteries, accessories, and checklist templates in parallel
2. Clears and replaces the relevant IndexedDB tables in a single transaction

Both are called by `stores/sync.js: syncNow()`, which also refreshes the pending count displayed in `SyncStatusBadge`.

### Conflict resolution

Last-write-wins using `client_uuid` on the server. The `SyncController` uses `Flight::updateOrCreate(['client_uuid' => ...], [...])`. If a flight is POSTed twice (e.g. sync retry), the second call updates the record in place — no duplicates. `synced_at` is always set to `now()` on the server side.

### 30-day window

- Flights with `started_at >= now - 30 days` are always stored in IndexedDB and available offline.
- Older flights are fetched on-demand from the API via `GET /api/v1/flights?to=<cutoff>&page=N` and displayed in `FlightListView` when online.

---

## Frontend Structure

```
resources/js/
├── app.js                    Entry point — creates Vue app, mounts Pinia + router
├── App.vue                   Root component: OfflineBanner + NavBar + RouterView
├── bootstrap.js              Axios global config (baseURL, credentials, CSRF)
├── db.js                     Dexie schema (IndexedDB)
├── sync.js                   Sync engine functions
│
├── router/
│   └── index.js              Routes + auth guard (redirects to /login if unauthenticated)
│
├── stores/
│   ├── auth.js               User state, login/logout, fetchUser
│   ├── fleet.js              Drones/batteries/accessories/templates — CRUD + IndexedDB cache
│   ├── flights.js            Recent flights (IndexedDB), currentFlight, older flights (API)
│   └── sync.js               Online status, pending count, syncNow()
│
├── composables/              (empty — use @vueuse/core directly in components)
│
├── components/
│   ├── NavBar.vue            App nav with dropdown menu + SyncStatusBadge
│   ├── OfflineBanner.vue     Amber banner shown when offline
│   ├── SyncStatusBadge.vue   Dot indicator: Synced / N pending / Offline / Syncing
│   ├── GpsCapture.vue        Wraps Geolocation API; emits { lat, lng }
│   ├── AccessoryPicker.vue   Chip-style multi-select for accessories
│   ├── ChecklistForm.vue     Renders checklist items with checkboxes + optional comments
│   └── FlightCard.vue        Summary card linking to FlightDetailView
│
└── views/
    ├── LoginView.vue          Email/password form
    ├── DashboardView.vue      Active flight callout + recent flights
    ├── FlightStartView.vue    Two-step wizard: flight details → pre-flight checklist
    ├── FlightActiveView.vue   Live timer, drone/battery info, End Flight button
    ├── FlightEndView.vue      Post-flight notes + Save
    ├── FlightDetailView.vue   Full read-only record view
    ├── FlightListView.vue     30-day list (IndexedDB) + older flights (API, paginated)
    ├── DronesView.vue         Drone CRUD with modal form
    ├── BatteriesView.vue      Battery CRUD with modal form
    ├── AccessoriesView.vue    Accessory CRUD with modal form
    └── ChecklistTemplatesView.vue  View/edit checklist items per template
```

### State flow: starting a flight

```
FlightStartView (step 1)
  form: drone_id, battery_id, accessories[], gps, location, flight_plan, purpose, laanc

FlightStartView (step 2: pre-flight checklist)
  checklist[]: built from fleet.defaultTemplate.items
  each item: { checklist_item_id, label, has_comment_box, checked, comment }

"Launch" button
  → flights.startFlight({ ...form, ...gps, checklist })
      → Dexie.flights.add({ synced: 0, client_uuid: uuidv4(), ... })
  → router.push(`/flights/${localId}/active`)
```

### Auth guard

`router/index.js` runs `beforeEach`. If `auth.user` is null and not loading, it calls `auth.fetchUser()` (which hits `GET /api/user`). If the user is still null and the route requires auth, it redirects to `/login`. Authenticated users visiting `/login` are redirected to `/`.

---

## PWA / Service Worker

Configured in `vite.config.js` via `vite-plugin-pwa` (Workbox under the hood).

### Caching strategy

| Asset category | Strategy | Cache name | TTL |
|---|---|---|---|
| App shell (JS, CSS, HTML, icons) | Precache (build-time) | `workbox-precache-v2` | Forever (versioned) |
| Fleet API (drones/batteries/accessories/checklist-templates) | StaleWhileRevalidate | `api-fleet-cache` | 24 hours |
| Flights API | NetworkFirst (5s timeout) | `api-flights-cache` | 30 days, max 200 entries |

`registerType: 'autoUpdate'` — the service worker self-updates without prompting the user.

### Background Sync

The service worker intercepts the `sync` event for tag `"sync-flights"`. `registerBackgroundSync()` in `resources/js/sync.js` registers this tag via `navigator.serviceWorker.ready.then(sw => sw.sync.register('sync-flights'))`. The handler calls the same `syncPendingFlights` function used in foreground sync.

### PWA manifest (in `vite.config.js`)

```js
name: 'DroneLog'
short_name: 'DroneLog'
display: 'standalone'
theme_color: '#1e40af'      // Tailwind blue-800
background_color: '#0f172a' // Tailwind slate-900
start_url: '/'
icons: 192px, 512px, 512px maskable   // in public/icons/
```

---

## Adding a New Field to Flights

Changes must be made in all 8 of these places:

1. **Migration** — `php artisan make:migration add_X_to_flights_table`
2. **`Flight::$fillable`** — add the column name
3. **`StoreFlightRequest::rules()`** — add validation rule
4. **`UpdateFlightRequest::rules()`** — add validation rule (often `sometimes`)
5. **`FlightResource::toArray()`** — add to the returned array
6. **`SyncController::flights()`** — add to the validation block + payload mapping
7. **`resources/js/sync.js`** — add to the `payload` object in `syncPendingFlights()`
8. **UI** — add field to `FlightStartView.vue` (pre-flight) or `FlightEndView.vue` (post-flight)

If it's an enum field, also add a new enum class in `app/Enums/` and register it in `Flight::$casts`.
