<template>
  <div class="auth-page container">
    <div class="auth-grid">
      <div class="auth-info">
        <h1 class="section-title">Новый пароль</h1>
        <p class="section-subtitle">
          Придумайте новый пароль для аккаунта <strong style="color:var(--mc-emerald);">{{ email || '...' }}</strong>.
        </p>
        <img :src="mcAsset('Iron_Pickaxe_JE3_BE2.png')" alt="Pickaxe" class="decor-img pixel-img">
      </div>

      <div class="auth-card glass">
        <form @submit.prevent="submit" class="auth-form" v-if="!done">
          <label class="form-label">
            <span>Новый пароль (минимум 8 символов)</span>
            <input v-model="password" type="password" class="input-soft" placeholder="••••••••" required minlength="8">
          </label>
          <label class="form-label">
            <span>Повторите пароль</span>
            <input v-model="passwordConfirm" type="password" class="input-soft" placeholder="••••••••" required minlength="8">
          </label>
          <button type="submit" class="soft-button primary w-full mt-2" :disabled="loading || !canSubmit">
            {{ loading ? 'Сохраняем...' : 'Сохранить пароль' }}
          </button>
          <div v-if="error" class="error-box">{{ error }}</div>
        </form>

        <div v-else class="auth-form" style="text-align:center;">
          <div class="success-icon">✓</div>
          <h3 style="margin:0;">Пароль обновлён</h3>
          <p class="text-muted">Войдите со своим новым паролем.</p>
          <router-link to="/login" class="soft-button primary w-full">Войти</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/axios'
import { mcAsset } from '../utils/assets'

const route = useRoute()
const email = ref('')
const token = ref('')
const password = ref('')
const passwordConfirm = ref('')
const loading = ref(false)
const error = ref('')
const done = ref(false)

const canSubmit = computed(() =>
  password.value.length >= 8 && password.value === passwordConfirm.value
)

onMounted(() => {
  email.value = route.query.email || ''
  token.value = route.query.token || ''
  if (!token.value || !email.value) {
    error.value = 'Некорректная ссылка. Запросите сброс ещё раз.'
  }
})

const submit = async () => {
  if (!canSubmit.value) {
    error.value = 'Пароли не совпадают или короче 8 символов.'
    return
  }
  loading.value = true; error.value = ''
  try {
    await api.post('/reset-password', {
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirm.value,
    })
    done.value = true
  } catch (e) {
    error.value = e.response?.data?.error || e.response?.data?.message || 'Ошибка. Попробуйте ещё раз.'
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
.text-muted { color: var(--text-muted); }
.error-box { padding: 16px; border-radius: var(--radius-md); background: rgba(255, 85, 85, 0.1); color: #ff5555; border: 1px solid rgba(255, 85, 85, 0.2); font-size: 14px; text-align: center; }
.success-icon {
  width: 60px; height: 60px; margin: 0 auto; border-radius: 50%;
  background: rgba(0,230,118,0.12); color: var(--mc-emerald);
  display: flex; align-items: center; justify-content: center;
  font-size: 32px; font-weight: 700;
}
@media (max-width: 768px) { .auth-grid { grid-template-columns: 1fr; gap: 40px; text-align: center; } .decor-img { margin: 20px auto; } }
</style>
