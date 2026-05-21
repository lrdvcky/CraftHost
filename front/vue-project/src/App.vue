<template>
  <div class="layout">
    <Minecraft3DBlocks />

    <header class="site-header">
      <div class="container">
        <div class="header-shell glass">
          <router-link to="/" class="brand">
            <div class="brand-title" style="font-family: 'Press Start 2P', cursive; font-size: 16px;">
              <span style="color: var(--mc-emerald)">Craft</span>Host
            </div>
          </router-link>

          <nav class="desktop-nav">
            <router-link to="/" class="nav-link">Главная</router-link>
            <router-link to="/pricing" class="nav-link">Тарифы</router-link>
            <router-link to="/order" class="nav-link">Конфигуратор</router-link>
            <router-link to="/faq" class="nav-link">FAQ</router-link>
            <a href="#" class="nav-link" @click.prevent="openSupport">Поддержка</a>
            <router-link v-if="authStore.isAuthenticated" to="/dashboard" class="nav-link">Кабинет</router-link>
          </nav>

          <div class="header-actions">
            <template v-if="!authStore.isAuthenticated">
              <router-link to="/login" class="nav-link">Войти</router-link>
              <router-link to="/register" class="soft-button primary" style="height: 40px; font-size: 13px;">Скрафтить аккаунт</router-link>
            </template>
            <template v-else>
              <div class="user-email">{{ authStore.user?.email }}</div>
              <button @click="handleLogout" class="soft-button secondary" style="height: 40px;">Выйти</button>
            </template>
          </div>
        </div>
      </div>
    </header>

    <main class="page-content">
      <router-view />
    </main>

    <AppFooter />

    <!-- ПЛАВАЮЩИЙ ВИДЖЕТ ПОДДЕРЖКИ -->
    <SupportWidget />

    <!-- КРАСИВЫЕ ВСПЛЫВАЮЩИЕ УВЕДОМЛЕНИЯ -->
    <div class="toast-container">
      <transition-group name="toast-anim">
        <div v-for="toast in toasts" :key="toast.id" class="toast-card glass" :class="toast.type">
          <div class="toast-icon">
            <svg v-if="toast.type === 'success'" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div class="toast-message">{{ toast.message }}</div>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import Minecraft3DBlocks from './components/Minecraft3DBlocks.vue'
import AppFooter from './components/AppFooter.vue'
import SupportWidget from './components/SupportWidget.vue'
import { openSupport } from './utils/support'
import { toasts } from './utils/toast' // Импортируем состояние уведомлений

const authStore = useAuthStore()
const router = useRouter()

onMounted(async () => {
  if (localStorage.getItem('token')) {
    await authStore.fetchUser()
  }
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}
</script>

<style scoped>
.layout { min-height: 100vh; position: relative; }
.site-header { position: sticky; top: 0; z-index: 50; padding: 20px 0; }
.header-shell { display: flex; align-items: center; justify-content: space-between; padding: 12px 24px; }
.nav-link { font-weight: 500; color: var(--text-muted); padding: 8px 16px; border-radius: var(--radius-md); transition: 0.2s; }
.nav-link:hover, .nav-link.router-link-active { color: var(--text-main); background: rgba(255,255,255,0.05); }
.desktop-nav { display: flex; gap: 8px; }
.header-actions { display: flex; gap: 16px; align-items: center; }
.user-email { color: var(--mc-emerald); font-weight: 600; font-size: 14px; }
.page-content { position: relative; z-index: 10; }

/* СТИЛИ ДЛЯ УВЕДОМЛЕНИЙ */
.toast-container {
  position: fixed; bottom: 30px; right: 30px;
  display: flex; flex-direction: column; gap: 12px; z-index: 9999;
}
.toast-card {
  display: flex; align-items: center; gap: 16px; padding: 16px 20px;
  min-width: 280px; border-left: 4px solid; /* Цветная полоска слева */
}
.toast-card.success { border-left-color: var(--mc-emerald); }
.toast-card.error { border-left-color: #ff5555; }

.toast-icon { font-size: 18px; }
.toast-card.success .toast-icon { color: var(--mc-emerald); }
.toast-card.error .toast-icon { color: #ff5555; }
.toast-message { font-size: 15px; font-weight: 500; color: var(--text-main); }

/* Анимация выезда уведомлений */
.toast-anim-enter-active, .toast-anim-leave-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.toast-anim-enter-from { opacity: 0; transform: translateX(50px) scale(0.9); }
.toast-anim-leave-to { opacity: 0; transform: scale(0.9); }

@media (max-width: 768px) {
  .desktop-nav { display: none; }
  .user-email { display: none; }
  .toast-container { bottom: 20px; left: 20px; right: 20px; align-items: stretch; }
}
</style>