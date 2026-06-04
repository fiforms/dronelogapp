import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loading: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
    },

    actions: {
        async fetchUser() {
            this.loading = true;
            try {
                const { data } = await axios.get('/api/user');
                this.user = data;
                localStorage.setItem('auth.user', JSON.stringify(data));
            } catch (e) {
                // Network failure while offline — keep the cached user so the
                // router guard doesn't bounce an already-authenticated user to login.
                if (!e.response) {
                    const cached = localStorage.getItem('auth.user');
                    this.user = cached ? JSON.parse(cached) : null;
                } else {
                    this.user = null;
                    localStorage.removeItem('auth.user');
                }
            } finally {
                this.loading = false;
            }
        },

        async login(email, password) {
            await axios.get('/sanctum/csrf-cookie');
            await axios.post('/login', { email, password });
            await this.fetchUser();
        },

        async logout() {
            // Wipe all local data so the next user starts clean
            try {
                const { db } = await import('../db');
                await Promise.all(
                    Object.values(db.tables).map(t => t.clear())
                );
            } catch {}
            localStorage.clear();

            try {
                await axios.post('/logout');
            } catch {}

            this.user = null;
        },
    },
});
