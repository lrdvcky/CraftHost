import { defineStore } from 'pinia';
import api from '../api/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAuthenticated: false,
    }),
    actions: {
        async register(email, password, refCode) {
            // Получаем CSRF токен от Laravel
            await axios_csrf();
            const res = await api.post('/register', {
                email,
                password,
                password_confirmation: password,
                ref: refCode || undefined,
            });
            this._setAuth(res.data);
        },

        async login(email, password) {
            await axios_csrf();
            const res = await api.post('/login', { email, password });
            this._setAuth(res.data);
        },

        async fetchUser() {
            try {
                const res = await api.get('/user');
                this.user = res.data;
                this.isAuthenticated = true;
            } catch {
                this._clearAuth();
            }
        },

        async logout() {
            try {
                await api.post('/logout');
            } finally {
                this._clearAuth();
            }
        },

        _setAuth(data) {
            this.user = data.user;
            this.isAuthenticated = true;
            if (data.token) {
                localStorage.setItem('token', data.token);
                api.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;
            }
        },

        _clearAuth() {
            this.user = null;
            this.isAuthenticated = false;
            localStorage.removeItem('token');
            delete api.defaults.headers.common['Authorization'];
        }
    }
});

// Функция для получения CSRF-токена (нужна только для cookie-режима Sanctum)
async function axios_csrf() {
    try {
        await api.get('http://127.0.0.1:8000/sanctum/csrf-cookie');
    } catch {
        // Игнорируем ошибку если уже есть токен в Bearer
    }
}