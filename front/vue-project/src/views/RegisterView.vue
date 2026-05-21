<template>
  <div class="auth-page container">
    <div class="auth-grid">
      <div class="auth-info">
        <h1 class="section-title">Создать аккаунт</h1>
        <p class="section-subtitle">
          Зарегистрируйтесь, чтобы получить доступ к премиальному хостингу и реферальной системе.
        </p>
        <img :src="mcAsset('Crafting_Table_JE4_BE3.png')" alt="Crafting Table" class="decor-img pixel-img">
      </div>

      <div class="auth-card glass">
        <form @submit.prevent="handleRegister" class="auth-form">
          <label class="form-label">
            <span>Электронная почта</span>
            <input v-model="form.email" type="email" class="input-soft" placeholder="steve@minecraft.net" required>
          </label>

          <label class="form-label">
            <span>Пароль (минимум 8 символов)</span>
            <input v-model="form.password" type="password" class="input-soft" placeholder="••••••••" required minlength="8">
          </label>

          <label class="form-label">
            <span>Реферальный код (опционально)</span>
            <input v-model="form.ref" type="text" class="input-soft" placeholder="DIAMOND123">
          </label>

          <button type="submit" class="soft-button primary w-full mt-2" :disabled="loading">
            {{ loading ? 'Создание...' : 'Зарегистрироваться' }}
          </button>
          
          <div class="auth-footer">
            <span class="text-muted">Уже есть аккаунт?</span>
            <router-link to="/login" class="link-accent">Войти</router-link>
          </div>

          <div v-if="error" class="error-box">{{ error }}</div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { mcAsset } from '../utils/assets'
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useRouter, useRoute } from 'vue-router'

const form = ref({ email: '', password: '', ref: '' })
const error = ref('')
const loading = ref(false)
const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

onMounted(() => { if (route.query.ref) form.value.ref = route.query.ref })

const handleRegister = async () => {
  loading.value = true; error.value = ''
  try {
    await authStore.register(form.value.email, form.value.password, form.value.ref)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.message || 'Ошибка регистрации. Возможно, email уже занят.'
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

@media (max-width: 768px) { .auth-grid { grid-template-columns: 1fr; gap: 40px; text-align: center; } .decor-img { margin: 20px auto; } }
</style>