import { defineStore } from 'pinia';
import axios from 'axios';
import db from '../db';

export const useFleetStore = defineStore('fleet', {
    state: () => ({
        drones: [],
        batteries: [],
        accessories: [],
        checklistTemplates: [],
        loaded: false,
    }),

    getters: {
        defaultTemplate: (state) =>
            state.checklistTemplates.find((t) => t.is_default) ?? state.checklistTemplates[0] ?? null,
        activeDrones: (state) => state.drones.filter((d) => d.is_active !== false),
        activeBatteries: (state) => state.batteries.filter((b) => b.is_active !== false),
        activeAccessories: (state) => state.accessories.filter((a) => a.is_active !== false),
    },

    actions: {
        async fetchAll() {
            const [drones, batteries, accessories, templates] = await Promise.all([
                axios.get('/api/v1/drones').then((r) => r.data.data),
                axios.get('/api/v1/batteries').then((r) => r.data.data),
                axios.get('/api/v1/accessories').then((r) => r.data.data),
                axios.get('/api/v1/checklist-templates').then((r) => r.data.data),
            ]);

            this.drones = drones;
            this.batteries = batteries;
            this.accessories = accessories;
            this.checklistTemplates = templates;
            this.loaded = true;

            // Also persist to IndexedDB for offline access
            await this._cacheToIndexedDB(drones, batteries, accessories, templates);
        },

        async loadFromIndexedDB() {
            this.drones = await db.drones.toArray();
            this.batteries = await db.batteries.toArray();
            this.accessories = await db.accessories.toArray();
            this.checklistTemplates = await db.checklist_templates.toArray();
            // Attach items to templates
            for (const t of this.checklistTemplates) {
                t.items = await db.checklist_items.where('template_id').equals(t.id).sortBy('sort_order');
            }
            this.loaded = true;
        },

        async _cacheToIndexedDB(drones, batteries, accessories, templates) {
            await db.transaction('rw', db.drones, db.batteries, db.accessories, db.checklist_templates, db.checklist_items, async () => {
                await db.drones.clear();
                await db.batteries.clear();
                await db.accessories.clear();
                await db.checklist_templates.clear();
                await db.checklist_items.clear();

                await db.drones.bulkPut(drones.map((d) => ({ ...d, server_id: d.id })));
                await db.batteries.bulkPut(batteries.map((b) => ({ ...b, server_id: b.id })));
                await db.accessories.bulkPut(accessories.map((a) => ({ ...a, server_id: a.id })));

                for (const t of templates) {
                    await db.checklist_templates.put({ ...t, server_id: t.id });
                    if (t.items?.length) {
                        await db.checklist_items.bulkPut(t.items.map((i) => ({ ...i, server_id: i.id })));
                    }
                }
            });
        },

        // Drone CRUD
        async saveDrone(data) {
            if (data.id) {
                const { data: updated } = await axios.put(`/api/v1/drones/${data.id}`, data);
                const idx = this.drones.findIndex((d) => d.id === data.id);
                if (idx !== -1) this.drones[idx] = updated.data;
                return updated.data;
            }
            const { data: created } = await axios.post('/api/v1/drones', data);
            this.drones.push(created.data);
            return created.data;
        },

        async deleteDrone(id) {
            await axios.delete(`/api/v1/drones/${id}`);
            this.drones = this.drones.filter((d) => d.id !== id);
        },

        async setDroneActive(id, isActive) {
            const { data: updated } = await axios.put(`/api/v1/drones/${id}`, { is_active: isActive });
            const idx = this.drones.findIndex((d) => d.id === id);
            if (idx !== -1) this.drones[idx] = updated.data;
        },

        // Battery CRUD
        async saveBattery(data) {
            if (data.id) {
                const { data: updated } = await axios.put(`/api/v1/batteries/${data.id}`, data);
                const idx = this.batteries.findIndex((b) => b.id === data.id);
                if (idx !== -1) this.batteries[idx] = updated.data;
                return updated.data;
            }
            const { data: created } = await axios.post('/api/v1/batteries', data);
            this.batteries.push(created.data);
            return created.data;
        },

        async deleteBattery(id) {
            await axios.delete(`/api/v1/batteries/${id}`);
            this.batteries = this.batteries.filter((b) => b.id !== id);
        },

        async setBatteryActive(id, isActive) {
            const { data: updated } = await axios.put(`/api/v1/batteries/${id}`, { is_active: isActive });
            const idx = this.batteries.findIndex((b) => b.id === id);
            if (idx !== -1) this.batteries[idx] = updated.data;
        },

        // Accessory CRUD
        async saveAccessory(data) {
            if (data.id) {
                const { data: updated } = await axios.put(`/api/v1/accessories/${data.id}`, data);
                const idx = this.accessories.findIndex((a) => a.id === data.id);
                if (idx !== -1) this.accessories[idx] = updated.data;
                return updated.data;
            }
            const { data: created } = await axios.post('/api/v1/accessories', data);
            this.accessories.push(created.data);
            return created.data;
        },

        async deleteAccessory(id) {
            await axios.delete(`/api/v1/accessories/${id}`);
            this.accessories = this.accessories.filter((a) => a.id !== id);
        },

        async setAccessoryActive(id, isActive) {
            const { data: updated } = await axios.put(`/api/v1/accessories/${id}`, { is_active: isActive });
            const idx = this.accessories.findIndex((a) => a.id === id);
            if (idx !== -1) this.accessories[idx] = updated.data;
        },
    },
});
