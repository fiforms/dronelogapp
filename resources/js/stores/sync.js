import { defineStore } from 'pinia';
import { useOnline } from '@vueuse/core';
import axios from 'axios';
import db from '../db';
import { syncPendingFlights, syncFleetFromServer, pullFlightsFromServer } from '../sync';

export const useSyncStore = defineStore('sync', {
    state: () => ({
        online: navigator.onLine,
        syncing: false,
        lastSyncedAt: null,
        pendingCount: 0,
    }),

    actions: {
        /** Call once at app boot. Sets up online/offline listeners. */
        async init() {
            await this.refreshPendingCount();

            window.addEventListener('online', this.onOnline.bind(this));
            window.addEventListener('offline', () => { this.online = false; });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && this.online) {
                    this.syncNow();
                }
            });

            if (this.online) {
                await this.syncNow();
            }
        },

        async onOnline() {
            this.online = true;
            await this.syncNow();
        },

        async refreshPendingCount() {
            this.pendingCount = await db.flights.where('synced').equals(0).count();
        },

        async syncNow() {
            if (this.syncing) return;
            this.syncing = true;
            try {
                await syncPendingFlights(axios);
                await Promise.all([
                    syncFleetFromServer(axios),
                    pullFlightsFromServer(axios),
                ]);
                this.lastSyncedAt = new Date().toISOString();
                await this.refreshPendingCount();

                // Refresh reactive flight state now that IndexedDB has server data
                const { useFlightsStore } = await import('./flights');
                await useFlightsStore().loadRecent();
            } finally {
                this.syncing = false;
            }
        },
    },
});
