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
            } catch {
                this.user = null;
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
            await axios.post('/logout');
            this.user = null;
        },
    },
});
