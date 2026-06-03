<template>
  <div class="layout" :class="{ 'menu-open': mobileMenuOpen }">
    <Minecraft3DBlocks />

    <header class="site-header">
      <div class="container">
        <div class="header-shell">
          <router-link to="/" class="brand" @click="closeMobileMenu">
            <div class="brand-title">
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
              <router-link to="/login" class="nav-link login-link">Войти</router-link>
              <router-link to="/register" class="soft-button primary register-btn">Скрафтить аккаунт</router-link>
            </template>
            <template v-else>
              <div class="user-email">{{ authStore.user?.email }}</div>
              <button @click="handleLogout" class="soft-button secondary logout-btn">Выйти</button>
            </template>
          </div>

          <!-- Бургер для мобильных -->
          <button
            class="burger"
            :class="{ open: mobileMenuOpen }"
            @click="toggleMobileMenu"
            :aria-expanded="mobileMenuOpen"
            aria-label="Меню"
          >
            <span></span><span></span><span></span>
          </button>
        </div>
      </div>
    </header>

    <!-- Мобильное меню (drawer) -->
    <transition name="mob-menu">
      <div v-if="mobileMenuOpen" class="mob-overlay" @click.self="closeMobileMenu">
        <aside class="mob-drawer">
          <div class="mob-drawer-head">
            <div class="brand-title">
              <span style="color: var(--mc-emerald)">Craft</span>Host
            </div>
            <button class="mob-close" @click="closeMobileMenu" aria-label="Закрыть">✕</button>
          </div>

          <nav class="mob-nav">
            <router-link to="/" class="mob-link" @click="closeMobileMenu">
              <span class="mob-link-icon">🏠</span> Главная
            </router-link>
            <router-link to="/pricing" class="mob-link" @click="closeMobileMenu">
              <span class="mob-link-icon">💎</span> Тарифы
            </router-link>
            <router-link to="/order" class="mob-link" @click="closeMobileMenu">
              <span class="mob-link-icon">⚙️</span> Конфигуратор
            </router-link>
            <router-link to="/faq" class="mob-link" @click="closeMobileMenu">
              <span class="mob-link-icon">❓</span> FAQ
            </router-link>
            <a href="#" class="mob-link" @click.prevent="openSupportFromMenu">
              <span class="mob-link-icon">💬</span> Поддержка
            </a>
            <router-link v-if="authStore.isAuthenticated" to="/dashboard" class="mob-link" @click="closeMobileMenu">
              <span class="mob-link-icon">📦</span> Личный кабинет
            </router-link>
          </nav>

          <div class="mob-drawer-foot">
            <template v-if="!authStore.isAuthenticated">
              <router-link to="/login" class="soft-button secondary w-full" @click="closeMobileMenu">Войти</router-link>
              <router-link to="/register" class="soft-button primary w-full" @click="closeMobileMenu" style="margin-top:10px;">Скрафтить аккаунт</router-link>
            </template>
            <template v-else>
              <div class="mob-user">
                <div class="mob-user-label">Вы вошли как</div>
                <div class="mob-user-email">{{ authStore.user?.email }}</div>
              </div>
              <button @click="handleLogout" class="soft-button secondary w-full">Выйти</button>
            </template>
          </div>
        </aside>
      </div>
    </transition>

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
import { onMounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from './stores/auth'
import Minecraft3DBlocks from './components/Minecraft3DBlocks.vue'
import AppFooter from './components/AppFooter.vue'
import SupportWidget from './components/SupportWidget.vue'
import { openSupport } from './utils/support'
import { toasts } from './utils/toast'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const mobileMenuOpen = ref(false)

const toggleMobileMenu = () => { mobileMenuOpen.value = !mobileMenuOpen.value }
const closeMobileMenu  = () => { mobileMenuOpen.value = false }
const openSupportFromMenu = () => { closeMobileMenu(); openSupport() }

// Закрываем меню при смене маршрута и блокируем скролл body пока меню открыто
watch(route, closeMobileMenu)
watch(mobileMenuOpen, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
})

onMounted(async () => {
  if (localStorage.getItem('token')) {
    await authStore.fetchUser()
  }
})

const handleLogout = async () => {
  closeMobileMenu()
  await authStore.logout()
  router.push('/')
}
</script>

<style scoped>
.layout { min-height: 100vh; position: relative; }
.site-header { position: sticky; top: 0; z-index: 50; padding: 20px 0; }
.header-shell {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 24px; gap: 16px;
  /* Непрозрачный фон */
  background: #0c1117;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: var(--radius-lg);
  box-shadow: 0 8px 24px rgba(0,0,0,0.45);
}
.brand-title { font-family: 'Press Start 2P', cursive; font-size: 16px; white-space: nowrap; }

.nav-link { font-weight: 500; color: var(--text-muted); padding: 8px 16px; border-radius: var(--radius-md); transition: 0.2s; }
.nav-link:hover, .nav-link.router-link-active { color: var(--text-main); background: rgba(255,255,255,0.05); }
.desktop-nav { display: flex; gap: 8px; }
.header-actions { display: flex; gap: 16px; align-items: center; }
.user-email { color: var(--mc-emerald); font-weight: 600; font-size: 14px; }
.register-btn, .logout-btn { height: 40px; font-size: 13px; }
.page-content { position: relative; z-index: 10; }

/* ---- Бургер ---- */
.burger {
  display: none;
  width: 44px; height: 44px;
  flex-direction: column; justify-content: center; align-items: center;
  gap: 5px; background: transparent; border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-md); cursor: pointer; padding: 0; flex-shrink: 0;
}
.burger span {
  display: block; width: 22px; height: 2px; background: var(--text-main);
  border-radius: 2px; transition: transform 0.25s, opacity 0.25s;
}
.burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.burger.open span:nth-child(2) { opacity: 0; }
.burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ---- Мобильное меню ---- */
.mob-overlay {
  position: fixed; inset: 0;
  background: rgba(11,15,25,0.75); backdrop-filter: blur(4px);
  z-index: 60; display: flex; justify-content: flex-end;
}
.mob-drawer {
  width: min(320px, 88vw); height: 100vh;
  background: #0c1117;
  border-left: 1px solid rgba(255,255,255,0.08);
  box-shadow: -8px 0 30px rgba(0,0,0,0.6);
  display: flex; flex-direction: column;
  overflow-y: auto;
}
.mob-drawer-head {
  display: flex; justify-content: space-between; align-items: center;
  padding: 20px 22px; border-bottom: 1px solid rgba(255,255,255,0.06);
}
.mob-close {
  background: none; border: none; color: var(--text-muted);
  font-size: 22px; cursor: pointer; padding: 4px 10px;
}
.mob-close:hover { color: var(--text-main); }

.mob-nav { padding: 14px 12px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
.mob-link {
  display: flex; align-items: center; gap: 14px;
  padding: 14px 16px; border-radius: var(--radius-md);
  color: var(--text-main); font-size: 16px; font-weight: 500;
  transition: background 0.15s;
}
.mob-link:hover { background: rgba(255,255,255,0.05); }
.mob-link.router-link-active {
  background: rgba(0,230,118,0.08);
  color: var(--mc-emerald);
}
.mob-link-icon {
  width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
}

.mob-drawer-foot {
  padding: 18px 18px 26px;
  border-top: 1px solid rgba(255,255,255,0.06);
}
.w-full { width: 100%; }
.mob-user { margin-bottom: 14px; }
.mob-user-label { font-size: 12px; color: var(--text-muted); }
.mob-user-email { color: var(--mc-emerald); font-weight: 600; font-size: 14px; margin-top: 2px; word-break: break-all; }

.mob-menu-enter-active, .mob-menu-leave-active { transition: opacity 0.2s; }
.mob-menu-enter-active .mob-drawer, .mob-menu-leave-active .mob-drawer { transition: transform 0.28s ease; }
.mob-menu-enter-from, .mob-menu-leave-to { opacity: 0; }
.mob-menu-enter-from .mob-drawer, .mob-menu-leave-to .mob-drawer { transform: translateX(100%); }

/* СТИЛИ ДЛЯ УВЕДОМЛЕНИЙ */
.toast-container {
  position: fixed; bottom: 30px; right: 30px;
  display: flex; flex-direction: column; gap: 12px; z-index: 9999;
}
.toast-card {
  display: flex; align-items: center; gap: 16px; padding: 16px 20px;
  min-width: 280px; border-left: 4px solid;
}
.toast-card.success { border-left-color: var(--mc-emerald); }
.toast-card.error { border-left-color: #ff5555; }
.toast-icon { font-size: 18px; }
.toast-card.success .toast-icon { color: var(--mc-emerald); }
.toast-card.error .toast-icon { color: #ff5555; }
.toast-message { font-size: 15px; font-weight: 500; color: var(--text-main); }
.toast-anim-enter-active, .toast-anim-leave-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.toast-anim-enter-from { opacity: 0; transform: translateX(50px) scale(0.9); }
.toast-anim-leave-to { opacity: 0; transform: scale(0.9); }

/* ============== ПЛАНШЕТ И МЕНЬШЕ (≤ 1024px) ============== */
/* На планшете тоже бургер: иначе хедер с 5 nav + email + кнопкой не помещается */
@media (max-width: 1024px) {
  .site-header { padding: 16px 0; }
  .header-shell { padding: 10px 18px; }
  .desktop-nav { display: none; }
  .header-actions { display: none; }
  .burger { display: flex; }
}

/* ============== ТЕЛЕФОН (≤ 768px) ============== */
@media (max-width: 768px) {
  .site-header { padding: 14px 0; }
  .header-shell { padding: 10px 16px; }
  .brand-title { font-size: 13px; }
  .toast-container { bottom: 16px; left: 16px; right: 16px; align-items: stretch; }
  .toast-card { min-width: 0; }
}

@media (max-width: 480px) {
  .site-header { padding: 10px 0; }
  .header-shell { padding: 8px 12px; }
  .brand-title { font-size: 12px; }
}
</style>
