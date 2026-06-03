<template>
  <div class="result-page container">
    <div class="glass result-box" :class="stateClass">
      <div class="emoji">{{ emoji }}</div>
      <h1>{{ title }}</h1>
      <p>{{ message }}</p>
      <router-link to="/dashboard" class="soft-button primary" style="margin-top:22px;">В личный кабинет</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/axios'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const authStore = useAuthStore()
const paymentId = route.query.payment_id
const status = ref('checking')   // checking | success | pending | canceled | error
let timer = null
let attempts = 0

const stateClass = computed(() => ({ success: status.value === 'success', error: status.value === 'canceled' || status.value === 'error' }))
const emoji = computed(() => ({ checking: '⏳', success: '✓', pending: '⏳', canceled: '✕', error: '✕' }[status.value] || '⏳'))
const title = computed(() => ({
  checking: 'Проверяем оплату…',
  success: 'Оплата прошла успешно!',
  pending: 'Платёж обрабатывается',
  canceled: 'Платёж отменён',
  error: 'Не удалось проверить платёж',
}[status.value] || 'Проверяем оплату…'))
const message = computed(() => ({
  checking: 'Это займёт несколько секунд.',
  success: 'Баланс пополнен. Можно создавать и продлевать серверы.',
  pending: 'Оплата ещё не подтверждена. Баланс обновится автоматически, как только платёж пройдёт.',
  canceled: 'Платёж был отменён. Вы можете попробовать снова из личного кабинета.',
  error: 'Попробуйте обновить страницу позже или проверьте статус в кабинете.',
}[status.value] || ''))

const check = async () => {
  if (!paymentId) { status.value = 'error'; return }
  attempts++
  try {
    const r = await api.get(`/payments/${paymentId}/status`)
    status.value = r.data.status === 'success' ? 'success'
      : r.data.status === 'canceled' ? 'canceled' : 'pending'
    if (status.value === 'success') { await authStore.fetchUser(); stop() }
    else if (status.value === 'canceled') stop()
    else if (attempts >= 20) stop()   // ~1 мин ожидания pending
  } catch {
    status.value = 'error'; stop()
  }
}
const stop = () => { if (timer) { clearInterval(timer); timer = null } }

onMounted(() => { check(); timer = setInterval(check, 3000) })
onUnmounted(stop)
</script>

<style scoped>
.result-page { padding: 80px 0; max-width: 560px; }
.result-box { padding: 56px 40px; text-align: center; color: var(--text-muted); }
.result-box.success { border-color: rgba(0,230,118,0.4); }
.result-box.error { border-color: rgba(255,118,118,0.4); }
.emoji { font-size: 52px; margin-bottom: 12px; }
.result-box h1 { font-size: 26px; color: var(--text-main); margin: 0 0 10px; }
.result-box p { margin: 0; line-height: 1.6; }
</style>
