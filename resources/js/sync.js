/**
 * Sync engine — reads unsynced flights from IndexedDB and POSTs them to the API.
 *
 * Called from:
 *  1. stores/sync.js whenever the app comes online or regains focus
 *  2. The service worker's Background Sync handler (sw-custom.js) for background delivery
 */
import db from './db';
import axios from 'axios';

/**
 * Sync all pending (unsynced) flights to the server.
 * @param {import('axios').AxiosInstance} axiosInstance
 * @returns {{ synced: number, errors: string[] }}
 */
export async function syncPendingFlights(axiosInstance = axios) {
    const pending = await db.flights.where('synced').equals(0).toArray();

    if (!pending.length) {
        return { synced: 0, errors: [] };
    }

    const payload = pending.map((f) => ({
        client_uuid:                f.client_uuid,
        drone_id:                   f.drone_id,
        battery_id:                 f.battery_id,
        battery_pct_start:          f.battery_pct_start ?? null,
        battery_pct_end:            f.battery_pct_end ?? null,
        started_at:                 f.started_at,
        ended_at:                   f.ended_at ?? null,
        duration_minutes:           f.duration_minutes ?? null,
        lat:                        f.lat ?? null,
        lng:                        f.lng ?? null,
        location_description:       f.location_description ?? null,
        flight_plan:                f.flight_plan ?? null,
        purpose:                    f.purpose,
        purpose_notes:              f.purpose_notes ?? null,
        laanc_status:               f.laanc_status,
        laanc_authorization_number: f.laanc_authorization_number ?? null,
        post_flight_notes:          f.post_flight_notes ?? null,
        status:                     f.status ?? 'flown',
        accessories:                f.accessories ?? [],
        checklist:                  f.checklist ?? [],
        risk_scores:                f.risk_scores ?? [],
    }));

    try {
        const { data } = await axiosInstance.post('/api/v1/sync/flights', { flights: payload });

        // Mark each synced record with server_id
        for (const f of pending) {
            const serverId = data.ids?.[f.client_uuid];
            await db.flights.update(f.id, { synced: 1, server_id: serverId ?? null });
        }

        return { synced: data.synced ?? 0, errors: data.errors ?? [] };
    } catch (err) {
        // Network or server error — leave records as unsynced; will retry on next online event
        console.warn('[sync] Sync failed:', err.message);
        return { synced: 0, errors: [err.message] };
    }
}

/**
 * Pull the last 30 days of fleet data (drones, batteries, accessories, checklist templates)
 * from the API and cache them into IndexedDB.
 */
export async function syncFleetFromServer(axiosInstance = axios) {
    try {
        const [drones, batteries, accessories, templates, riskItems] = await Promise.all([
            axiosInstance.get('/api/v1/drones').then((r) => r.data.data),
            axiosInstance.get('/api/v1/batteries').then((r) => r.data.data),
            axiosInstance.get('/api/v1/accessories').then((r) => r.data.data),
            axiosInstance.get('/api/v1/checklist-templates').then((r) => r.data.data),
            axiosInstance.get('/api/v1/risk-items').then((r) => r.data.data),
        ]);

        await db.transaction('rw', db.drones, db.batteries, db.accessories, db.checklist_templates, db.checklist_items, db.risk_items, async () => {
            await db.drones.clear();
            await db.batteries.clear();
            await db.accessories.clear();
            await db.checklist_templates.clear();
            await db.checklist_items.clear();
            await db.risk_items.clear();

            await db.drones.bulkPut(drones.map((d) => ({ ...d, server_id: d.id })));
            await db.batteries.bulkPut(batteries.map((b) => ({ ...b, server_id: b.id })));
            await db.accessories.bulkPut(accessories.map((a) => ({ ...a, server_id: a.id })));
            await db.risk_items.bulkPut(riskItems.map((r) => ({ ...r, server_id: r.id })));

            for (const t of templates) {
                await db.checklist_templates.put({ ...t, server_id: t.id });
                if (t.items?.length) {
                    await db.checklist_items.bulkPut(t.items.map((i) => ({ ...i, server_id: i.id })));
                }
            }
        });
    } catch (err) {
        console.warn('[sync] Fleet sync failed:', err.message);
    }
}

/**
 * Register a Background Sync tag so the service worker can deliver pending flights
 * even when the tab is closed. Falls back silently on browsers without support (iOS Safari).
 */
export async function registerBackgroundSync() {
    if (!('serviceWorker' in navigator)) return;
    try {
        const sw = await navigator.serviceWorker.ready;
        if ('sync' in sw) {
            await sw.sync.register('sync-flights');
        }
    } catch {
        // Background Sync not available — stores/sync.js handles the fallback via online events
    }
}
