import { defineStore } from 'pinia';
import axios from 'axios';
import { v4 as uuidv4 } from 'uuid';
import db from '../db';
import { registerBackgroundSync } from '../sync';

const THIRTY_DAYS_MS = 30 * 24 * 60 * 60 * 1000;

export const useFlightsStore = defineStore('flights', {
    state: () => ({
        recentFlights: [],      // last 30 days, from IndexedDB
        currentFlight: null,    // active flight (in-progress)
        olderFlights: [],       // loaded on-demand from API
        pagination: { page: 1, total: 0, hasMore: false },
        loadingOlder: false,
    }),

    actions: {
        /** Load last 30 days of flights from IndexedDB. */
        async loadRecent() {
            const cutoff = new Date(Date.now() - THIRTY_DAYS_MS).toISOString();
            this.recentFlights = await db.flights
                .where('started_at')
                .aboveOrEqual(cutoff)
                .reverse()
                .toArray();

            // Find any in-progress flight (no ended_at)
            this.currentFlight = this.recentFlights.find((f) => !f.ended_at) ?? null;
        },

        /** Save a new flight to IndexedDB immediately (works offline). */
        async startFlight(data) {
            const flight = {
                client_uuid:                uuidv4(),
                drone_id:                   data.drone_id ?? null,
                battery_id:                 data.battery_id ?? null,
                battery_pct_start:          data.battery_pct_start ?? null,
                battery_pct_end:            null,
                started_at:                 new Date().toISOString(),
                ended_at:                   null,
                duration_minutes:           null,
                lat:                        data.lat ?? null,
                lng:                        data.lng ?? null,
                location_description:       data.location_description ?? null,
                flight_plan:                data.flight_plan ?? null,
                purpose:                    data.purpose ?? 'recreational',
                purpose_notes:              data.purpose_notes ?? null,
                laanc_status:               data.laanc_status ?? 'na',
                laanc_authorization_number: data.laanc_authorization_number ?? null,
                post_flight_notes:          null,
                accessories:                JSON.parse(JSON.stringify(data.accessories ?? [])),
                checklist:                  JSON.parse(JSON.stringify(data.checklist ?? [])),
                synced:                     0,
                server_id:                  null,
            };

            const id = await db.flights.add(flight);
            flight.id = id;

            this.currentFlight = flight;
            this.recentFlights.unshift(flight);

            registerBackgroundSync();

            return flight;
        },

        /** Save a completed past flight to IndexedDB immediately (works offline). */
        async logPastFlight(data) {
            const startedAt = new Date(`${data.flight_date}T${data.flight_time}`).toISOString();
            const durationMinutes = data.duration_minutes ? Number(data.duration_minutes) : null;
            const endedAt = durationMinutes
                ? new Date(new Date(startedAt).getTime() + durationMinutes * 60000).toISOString()
                : null;

            const flight = {
                client_uuid:                uuidv4(),
                drone_id:                   data.drone_id ?? null,
                battery_id:                 data.battery_id ?? null,
                battery_pct_start:          data.battery_pct_start ?? null,
                battery_pct_end:            data.battery_pct_end ?? null,
                started_at:                 startedAt,
                ended_at:                   endedAt,
                duration_minutes:           durationMinutes,
                lat:                        data.lat ?? null,
                lng:                        data.lng ?? null,
                location_description:       data.location_description ?? null,
                flight_plan:                data.flight_plan ?? null,
                purpose:                    data.purpose ?? 'recreational',
                purpose_notes:              data.purpose_notes ?? null,
                laanc_status:               data.laanc_status ?? 'na',
                laanc_authorization_number: data.laanc_authorization_number ?? null,
                post_flight_notes:          data.post_flight_notes ?? null,
                is_retrospective:           true,
                accessories:                JSON.parse(JSON.stringify(data.accessories ?? [])),
                checklist:                  [],
                synced:                     0,
                server_id:                  null,
            };

            const id = await db.flights.add(flight);
            flight.id = id;

            this.recentFlights.unshift(flight);
            this.recentFlights.sort((a, b) => new Date(b.started_at) - new Date(a.started_at));

            registerBackgroundSync();

            return flight;
        },

        /** Update checklist entries on the current flight (still in IndexedDB). */
        async updateChecklist(flightId, checklist) {
            await db.flights.update(flightId, { checklist, synced: 0 });
            const flight = this.recentFlights.find((f) => f.id === flightId);
            if (flight) flight.checklist = checklist;
            if (this.currentFlight?.id === flightId) this.currentFlight.checklist = checklist;
        },

        /** End the flight — sets ended_at, duration, battery end %, and notes, then queues sync. */
        async endFlight(flightId, { ended_at, duration_minutes, battery_pct_end, post_flight_notes }) {
            const update = {
                ended_at:         ended_at ?? new Date().toISOString(),
                duration_minutes: duration_minutes ?? null,
                battery_pct_end:  battery_pct_end ?? null,
                post_flight_notes,
                synced: 0,
            };
            await db.flights.update(flightId, update);

            const flight = this.recentFlights.find((f) => f.id === flightId);
            if (flight) Object.assign(flight, update);
            if (this.currentFlight?.id === flightId) this.currentFlight = null;

            registerBackgroundSync();
        },

        async getFlightById(id) {
            // Local first
            const local = await db.flights.get(Number(id));
            if (local) {
                // Older flights stored before labels were written — enrich from cached items
                if (local.checklist?.some((c) => !c.label)) {
                    const itemMap = {};
                    for (const c of local.checklist) {
                        if (!c.label && c.checklist_item_id) {
                            const item = await db.checklist_items
                                .where('server_id').equals(c.checklist_item_id).first();
                            if (item) itemMap[c.checklist_item_id] = item.label;
                        }
                    }
                    if (Object.keys(itemMap).length) {
                        local.checklist = local.checklist.map((c) =>
                            c.label ? c : { ...c, label: itemMap[c.checklist_item_id] ?? '' }
                        );
                    }
                }
                return local;
            }
            // Fall back to API
            const { data } = await axios.get(`/api/v1/flights/${id}`);
            return data.data;
        },

        /** Fetch older (>30 day) flights from the API. Online only. */
        async loadOlderFlights(page = 1) {
            this.loadingOlder = true;
            try {
                const cutoff = new Date(Date.now() - THIRTY_DAYS_MS).toISOString();
                const { data } = await axios.get('/api/v1/flights', {
                    params: { to: cutoff, page, per_page: 20 },
                });
                this.olderFlights = page === 1 ? data.data : [...this.olderFlights, ...data.data];
                this.pagination = {
                    page: data.meta?.current_page ?? page,
                    total: data.meta?.total ?? 0,
                    hasMore: !!data.links?.next,
                };
            } finally {
                this.loadingOlder = false;
            }
        },
    },
});
