import { defineStore } from 'pinia';
import { apiClient } from '@/bootstrap';
import { useUiStore } from './ui';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token'),
        loading: false,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.user),
        isGuest: (state) => !state.token,
        isAdmin: (state) => {
            if (!state.user) {
                return false;
            }

            if (typeof state.user.is_admin === 'boolean') {
                return state.user.is_admin;
            }

            if (typeof state.user.role === 'string') {
                return state.user.role === 'admin';
            }

            if (Array.isArray(state.user.roles)) {
                return state.user.roles.includes('admin');
            }

            return false;
        },
    },
    actions: {
        bootstrap() {
            window.addEventListener('auth:unauthorized', () => {
                this.forceLogout('Sua sessão expirou. Faça login novamente.');
            });

            if (this.token && !this.user) {
                this.fetchUser();
            }
        },
        async register(payload) {
            this.loading = true;
            try {
                const { data } = await apiClient.post('/auth/register', payload);
                this.persistSession(data.token, data.user);
                return data;
            } finally {
                this.loading = false;
            }
        },
        async login(payload) {
            this.loading = true;
            try {
                const { data } = await apiClient.post('/auth/login', payload);
                this.persistSession(data.token, data.user);
                return data;
            } finally {
                this.loading = false;
            }
        },
        async fetchUser() {
            if (!this.token) {
                return null;
            }

            try {
                const { data } = await apiClient.get('/auth/me');
                this.user = data;
                return data;
            } catch (error) {
                this.forceLogout();
                throw error;
            }
        },
        async updateProfile(payload) {
            const { data } = await apiClient.patch('/auth/me', payload);
            this.user = data;
            return data;
        },
        async logout() {
            try {
                await apiClient.post('/auth/logout');
            } catch (error) {
                // Ignore network failures on logout
            } finally {
                this.clearSession();
                this.redirectToLogin();
            }
        },
        persistSession(token, user) {
            this.token = token;
            this.user = user;
            localStorage.setItem('auth_token', token);
            useUiStore().notify({
                title: 'Bem-vindo!',
                message: `Olá, ${user.name}.`,
                tone: 'success',
            });
        },
        clearSession() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('auth_token');
        },
        async forceLogout(message = null) {
            this.clearSession();
            if (message) {
                useUiStore().notify({
                    title: 'Sessão encerrada',
                    message,
                    tone: 'warning',
                });
            }
            await this.redirectToLogin();
        },
        async redirectToLogin() {
            const router = (await import('@/router')).default;
            if (router.currentRoute.value.name !== 'auth.login') {
                router.push({ name: 'auth.login' });
            }
        },
    },
});
