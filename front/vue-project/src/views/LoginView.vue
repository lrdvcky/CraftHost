<template>
  <div class="auth-page container">
    <div class="auth-grid">
      <div class="auth-info">
        <h1 class="section-title">С возвращением</h1>
        <p class="section-subtitle">
          Войдите в личный кабинет для управления своими серверами, балансом и тикетами.
        </p>
        <img :src="mcAsset('150px-Chest.gif')" alt="Chest" class="decor-img pixel-img">
      </div>

      <div class="auth-card glass">
        <form @submit.prevent="handleLogin" class="auth-form">
          <label class="form-label">
            <span>Электронная почта</span>
            <input v-model="email" type="email" class="input-soft" placeholder="steve@minecraft.net" required>
          </label>

          <label class="form-label">
            <span>Пароль</span>
            <input v-model="password" type="password" class="input-soft" placeholder="••••••••" required>
          </label>

          <button type="submit" class="soft-button primary w-full mt-2">Войти в панель</button>

          <div class="forgot-row">
            <router-link to="/forgot-password" class="link-muted">Забыл пароль?</router-link>
          </div>

          <div class="auth-footer">
            <span class="text-muted">Нет аккаунта?</span>
            <router-link to="/register" class="link-accent">Скрафтить</router-link>
          </div>

          <div v-if="error" class="error-box">{{ error }}</div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { mcAsset } from '../utils/assets'
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'

const email = ref('')
const password = ref('')
const error = ref('')
const authStore = useAuthStore()
const router = useRouter()

const handleLogin = async () => {
  try {
    await authStore.login(email.value, password.value)
    router.push('/dashboard')
  } catch {
    error.value = 'Неверный логин или пароль'
  }
}
</script>

<style scoped>
.auth-page { padding: 80px 0; max-width: 1000px; margin: 0 auto; }
.auth-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 60px; align-items: center; }

.decor-img {
  width: 160px; height: auto; margin-top: 40px;
  filter: drop-shadow(0 20px 30px rgba(0,0,0,0.5));
}

.auth-card { padding: 40px; }
.auth-form { display: flex; flex-direction: column; gap: 20px; }
.form-label { display: flex; flex-direction: column; gap: 8px; font-size: 14px; font-weight: 500; color: var(--text-muted); }
.w-full { width: 100%; }
.mt-2 { margin-top: 10px; }

.auth-footer { display: flex; gap: 8px; justify-content: center; margin-top: 10px; font-size: 14px; }
.text-muted { color: var(--text-muted); }
.link-accent { color: var(--mc-diamond); font-weight: 600; text-decoration: underline; }
.forgot-row { display: flex; justify-content: center; margin-top: -8px; }
.link-muted { color: var(--text-muted); font-size: 13px; text-decoration: underline dotted; }
.link-muted:hover { color: var(--mc-diamond); }

.error-box { padding: 16px; border-radius: var(--radius-md); background: rgba(255, 85, 85, 0.1); color: #ff5555; border: 1px solid rgba(255, 85, 85, 0.2); font-size: 14px; text-align: center; }

@media (max-width: 768px) {
  .auth-grid { grid-template-columns: 1fr; gap: 40px; text-align: center; }
  .decor-img { margin: 20px auto; }
}
</style>