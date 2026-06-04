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
                started_at:                 new Date().toISOString(),
                ended_at:                   null,
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

        /** Update checklist entries on the current flight (still in IndexedDB). */
        async updateChecklist(flightId, checklist) {
            await db.flights.update(flightId, { checklist, synced: 0 });
            const flight = this.recentFlights.find((f) => f.id === flightId);
            if (flight) flight.checklist = checklist;
            if (this.currentFlight?.id === flightId) this.currentFlight.checklist = checklist;
        },

        /** End the flight — sets ended_at and post_flight_notes, queues sync. */
        async endFlight(flightId, { ended_at, post_flight_notes }) {
            const update = { ended_at: ended_at ?? new Date().toISOString(), post_flight_notes, synced: 0 };
            await db.flights.update(flightId, update);

            const flight = this.recentFlights.find((f) => f.id === flightId);
            if (flight) Object.assign(flight, update);
            if (this.currentFlight?.id === flightId) this.currentFlight = null;

            registerBackgroundSync();
        },

        async getFlightById(id) {
            // Local first
            const local = await db.flights.get(Number(id));
            if (local) return local;
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
