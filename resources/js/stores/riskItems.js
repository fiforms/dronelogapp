import { defineStore } from 'pinia';
import axios from 'axios';
import db from '../db';

export const useRiskItemsStore = defineStore('riskItems', {
    state: () => ({
        items: [],
    }),

    getters: {
        activeItems: (state) => state.items.filter((i) => i.is_active !== false),
    },

    actions: {
        async fetchAll() {
            // Try IndexedDB cache first (sort_order is not a Dexie index — sort in memory)
            const cached = await db.risk_items.toArray();
            cached.sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
            if (cached.length) {
                this.items = cached;
                return;
            }
            // Fallback to API
            try {
                const { data } = await axios.get('/api/v1/risk-items');
                this.items = data.data;
                await db.risk_items.clear();
                await db.risk_items.bulkPut(data.data.map((r) => ({ ...r, server_id: r.id })));
            } catch {
                // Offline — no items available
            }
        },

        async save(item) {
            if (item.id) {
                const { data } = await axios.put(`/api/v1/risk-items/${item.id}`, item);
                const idx = this.items.findIndex((i) => i.id === item.id);
                if (idx !== -1) this.items[idx] = data.data;
            } else {
                const { data } = await axios.post('/api/v1/risk-items', item);
                this.items.push(data.data);
            }
            // Invalidate cache so next fetchAll pulls fresh data
            await db.risk_items.clear();
        },

        async remove(id) {
            await axios.delete(`/api/v1/risk-items/${id}`);
            const idx = this.items.findIndex((i) => i.id === id);
            if (idx !== -1) this.items[idx].is_active = false;
            await db.risk_items.clear();
        },

        /** Load risk_threshold from the user settings endpoint. */
        async fetchThreshold() {
            try {
                const { data } = await axios.get('/api/v1/settings');
                return data.risk_threshold ?? 6;
            } catch {
                return 6;
            }
        },

        async saveThreshold(value) {
            const { data } = await axios.patch('/api/v1/settings', { risk_threshold: value });
            return data.risk_threshold;
        },
    },
});
