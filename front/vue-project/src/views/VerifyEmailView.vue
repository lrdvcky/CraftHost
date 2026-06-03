<template>
  <div class="verify-page container">
    <div class="verify-card glass">
      <div v-if="state === 'loading'">
        <div class="spinner"></div>
        <h2>Подтверждаем email...</h2>
      </div>

      <div v-else-if="state === 'success'">
        <div class="success-icon">✓</div>
        <h2>Email подтверждён</h2>
        <p class="text-muted">
          Спасибо! Теперь у вас полный доступ ко всем функциям CraftHost.
        </p>
        <router-link :to="authStore.isAuthenticated ? '/dashboard' : '/login'" class="soft-button primary">
          {{ authStore.isAuthenticated ? 'В кабинет' : 'Войти' }}
        </router-link>
      </div>

      <div v-else>
        <div class="error-icon">✕</div>
        <h2>Не удалось подтвердить email</h2>
        <p class="text-muted">{{ error || 'Ссылка некорректна или истекла. Запросите новое письмо в личном кабинете.' }}</p>
        <router-link to="/login" class="soft-button secondary">К входу</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/axios'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const authStore = useAuthStore()
const state = ref('loading') // loading | success | error
const error = ref('')

onMounted(async () => {
  const email = route.query.email
  const token = route.query.token

  if (!email || !token) {
    state.value = 'error'
    error.value = 'В ссылке нет email или токена.'
    return
  }

  try {
    await api.post('/email/verify', { email, token })
    state.value = 'success'
    // если пользователь залогинен — обновим его в store
    if (authStore.isAuthenticated) await authStore.fetchUser()
  } catch (e) {
    state.value = 'error'
    error.value = e.response?.data?.error || e.response?.data?.message || ''
  }
})
</script>

<style scoped>
.verify-page { padding: 100px 0; display: flex; justify-content: center; }
.verify-card { padding: 50px 40px; max-width: 500px; width: 100%; text-align: center; }
.verify-card h2 { margin: 20px 0 12px; }
.text-muted { color: var(--text-muted); margin-bottom: 28px; }

.success-icon, .error-icon {
  width: 80px; height: 80px; margin: 0 auto; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 40px; font-weight: 700;
}
.success-icon { background: rgba(0,230,118,0.12); color: var(--mc-emerald); }
.error-icon { background: rgba(255,85,85,0.12); color: #ff5555; }

.spinner {
  width: 50px; height: 50px; margin: 20px auto;
  border: 4px solid rgba(255,255,255,0.08);
  border-top-color: var(--mc-emerald);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
