<template>
  <div class="auth-page container">
    <div class="auth-grid">
      <div class="auth-info">
        <h1 class="section-title">Сброс пароля</h1>
        <p class="section-subtitle">
          Укажите email от аккаунта. Мы пришлём ссылку для смены пароля. Ссылка действительна 60 минут.
        </p>
        <img :src="mcAsset('Knowledge_Book_JE2.png')" alt="Knowledge Book" class="decor-img pixel-img">
      </div>

      <div class="auth-card glass">
        <form @submit.prevent="submit" class="auth-form" v-if="!sent">
          <label class="form-label">
            <span>Email от аккаунта</span>
            <input v-model="email" type="email" class="input-soft" placeholder="steve@minecraft.net" required>
          </label>
          <button type="submit" class="soft-button primary w-full mt-2" :disabled="loading">
            {{ loading ? 'Отправляем...' : 'Отправить ссылку' }}
          </button>
          <div v-if="error" class="error-box">{{ error }}</div>
          <div class="auth-footer">
            <router-link to="/login" class="link-accent">← Вернуться ко входу</router-link>
          </div>
        </form>

        <div v-else class="auth-form" style="text-align:center;">
          <div class="success-icon">✓</div>
          <h3 style="margin: 0;">Письмо отправлено</h3>
          <p class="text-muted">
            Если такой аккаунт существует и email подтверждён — на <strong>{{ email }}</strong>
            пришло письмо со ссылкой для смены пароля. Проверь папку «Спам».
          </p>
          <router-link to="/login" class="soft-button secondary w-full">Вернуться ко входу</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../api/axios'
import { mcAsset } from '../utils/assets'

const email = ref('')
const loading = ref(false)
const error = ref('')
const sent = ref(false)

const submit = async () => {
  loading.value = true; error.value = ''
  try {
    await api.post('/forgot-password', { email: email.value })
    sent.value = true
  } catch (e) {
    error.value = e.response?.data?.error || e.response?.data?.message || 'Ошибка. Попробуйте позже.'
  } finally { loading.value = false }
}
</script>

<style scoped>
.auth-page { padding: 80px 0; max-width: 1000px; margin: 0 auto; }
.auth-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 60px; align-items: center; }
.decor-img { width: 160px; height: auto; margin-top: 40px; filter: drop-shadow(0 20px 30px rgba(0,0,0,0.5)); }
.auth-card { padding: 40px; }
.auth-form { display: flex; flex-direction: column; gap: 20px; }
.form-label { display: flex; flex-direction: column; gap: 8px; font-size: 14px; font-weight: 500; color: var(--text-muted); }
.w-full { width: 100%; }
.mt-2 { margin-top: 10px; }
.auth-footer { display: flex; gap: 8px; justify-content: center; margin-top: 10px; font-size: 14px; }
.text-muted { color: var(--text-muted); }
.link-accent { color: var(--mc-diamond); font-weight: 600; text-decoration: underline; }
.error-box { padding: 16px; border-radius: var(--radius-md); background: rgba(255, 85, 85, 0.1); color: #ff5555; border: 1px solid rgba(255, 85, 85, 0.2); font-size: 14px; text-align: center; }
.success-icon {
  width: 60px; height: 60px; margin: 0 auto; border-radius: 50%;
  background: rgba(0,230,118,0.12); color: var(--mc-emerald);
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; font-weight: 700;
}
@media (max-width: 768px) { .auth-grid { grid-template-columns: 1fr; gap: 40px; text-align: center; } .decor-img { margin: 20px auto; } }
</style>
