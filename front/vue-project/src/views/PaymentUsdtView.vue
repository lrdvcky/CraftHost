<template>
  <div class="usdt-page container">
    <div class="usdt-head">
      <router-link to="/dashboard" class="back-link">← Назад в кабинет</router-link>
      <h1 class="section-title">Оплата USDT</h1>
    </div>

    <div v-if="loading" class="glass status-box">Загрузка платежа…</div>

    <div v-else-if="error" class="glass status-box error">
      {{ error }}
      <router-link to="/dashboard" class="soft-button secondary" style="margin-top:18px;">Вернуться в кабинет</router-link>
    </div>

    <template v-else-if="details">
      <!-- Успех -->
      <div v-if="details.status === 'success'" class="glass status-box success">
        <div class="big-emoji">✓</div>
        <h2>Оплата получена!</h2>
        <p>Баланс пополнен на {{ details.amount_rub }} ₽. Перенаправляем в кабинет…</p>
      </div>

      <!-- Отменён / истёк -->
      <div v-else-if="details.status === 'canceled' || expired" class="glass status-box error">
        <div class="big-emoji">⌛</div>
        <h2>{{ expired ? 'Время оплаты истекло' : 'Платёж отменён' }}</h2>
        <p>Создайте новый платёж в личном кабинете.</p>
        <router-link to="/dashboard" class="soft-button primary" style="margin-top:18px;">В кабинет</router-link>
      </div>

      <!-- Ожидание оплаты -->
      <div v-else class="usdt-grid">
        <div class="glass pay-card">
          <div class="amount-block">
            <span class="amount-label">Переведите ровно</span>
            <div class="amount-row">
              <span class="amount-val">{{ details.amount_usdt }}</span>
              <span class="amount-cur">USDT</span>
              <button class="copy-btn" @click="copy(String(details.amount_usdt))">Копировать</button>
            </div>
            <span class="amount-hint">≈ {{ details.amount_rub }} ₽ · сумму нельзя округлять — точное число определяет ваш платёж</span>
          </div>

          <div class="net-tabs">
            <button
              v-for="(n, key) in details.networks"
              :key="key"
              class="net-tab"
              :class="{ active: activeNet === key }"
              @click="activeNet = key"
            >{{ n.label }}</button>
          </div>

          <div v-if="currentNet" class="net-body">
            <div class="net-name">{{ currentNet.fullLabel }} <span v-if="currentNet.time" class="net-time">· {{ currentNet.time }}</span></div>
            <span class="wallet-label">Адрес кошелька ({{ currentNet.label }})</span>
            <div class="wallet-row">
              <code class="wallet">{{ currentNet.wallet }}</code>
              <button class="copy-btn" @click="copy(currentNet.wallet)">Копировать</button>
            </div>
            <p class="warn">⚠️ Отправляйте только USDT в сети {{ currentNet.label }} на этот адрес. Перевод в другой сети или другой монеты приведёт к потере средств.</p>
          </div>
        </div>

        <div class="glass info-card">
          <div class="timer" :class="{ urgent: secondsLeft < 300 }">
            <span class="timer-label">Платёж активен ещё</span>
            <span class="timer-val">{{ timerText }}</span>
          </div>

          <div class="status-line">
            <span class="dot pending"></span>
            Ожидаем поступление… проверяем блокчейн автоматически
          </div>

          <ol class="steps">
            <li>Скопируйте точную сумму <strong>{{ details.amount_usdt }} USDT</strong>.</li>
            <li>Выберите сеть и скопируйте адрес кошелька.</li>
            <li>Отправьте перевод из вашего кошелька / биржи.</li>
            <li>Баланс пополнится автоматически после подтверждения сети.</li>
          </ol>

          <button class="soft-button secondary w-full" @click="cancelPayment" :disabled="canceling">
            {{ canceling ? '…' : 'Отменить платёж' }}
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/axios'
import { useAuthStore } from '../stores/auth'
import { useToast } from '../utils/toast'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { showToast } = useToast()

const paymentId = route.query.payment_id
const details = ref(null)
const loading = ref(true)
const error = ref('')
const activeNet = ref(null)
const canceling = ref(false)
const now = ref(Math.floor(Date.now() / 1000))

let statusTimer = null
let tickTimer = null

const currentNet = computed(() => details.value?.networks?.[activeNet.value] || null)

const expiresAt = computed(() => {
  if (!details.value) return 0
  return (details.value.created_ts || 0) + (details.value.invoice_ttl || 60) * 60
})
const secondsLeft = computed(() => Math.max(0, expiresAt.value - now.value))
const expired = computed(() => details.value && details.value.status === 'pending' && secondsLeft.value <= 0)

const timerText = computed(() => {
  const s = secondsLeft.value
  const m = Math.floor(s / 60)
  const sec = String(s % 60).padStart(2, '0')
  return `${m}:${sec}`
})

const copy = async (text) => {
  const value = String(text ?? '').trim()
  if (!value) { showToast('Нечего копировать', 'error'); return }

  // Современный Clipboard API доступен только в защищённом контексте (https / localhost)
  if (navigator.clipboard && window.isSecureContext) {
    try {
      await navigator.clipboard.writeText(value)
      showToast('Скопировано', 'success')
      return
    } catch { /* падаем в запасной вариант ниже */ }
  }

  // Запасной вариант для http:// — скрытая textarea + execCommand('copy')
  try {
    const ta = document.createElement('textarea')
    ta.value = value
    ta.setAttribute('readonly', '')
    ta.style.position = 'fixed'
    ta.style.top = '-9999px'
    ta.style.opacity = '0'
    document.body.appendChild(ta)
    ta.focus()
    ta.select()
    ta.setSelectionRange(0, value.length)
    const ok = document.execCommand('copy')
    document.body.removeChild(ta)
    showToast(ok ? 'Скопировано' : 'Не удалось скопировать', ok ? 'success' : 'error')
  } catch {
    showToast('Не удалось скопировать', 'error')
  }
}

const fetchDetails = async () => {
  try {
    const r = await api.get(`/payments/${paymentId}/crypto-details`)
    details.value = r.data
    if (!activeNet.value) {
      const keys = Object.keys(r.data.networks || {})
      activeNet.value = keys[0] || null
    }
  } catch (e) {
    error.value = e.response?.data?.error || 'Платёж не найден или недоступен.'
  } finally {
    loading.value = false
  }
}

const pollStatus = async () => {
  if (!paymentId) return
  try {
    const r = await api.get(`/payments/${paymentId}/status`)
    if (details.value) details.value.status = r.data.status
    if (r.data.status === 'success') {
      stopTimers()
      await authStore.fetchUser()
      showToast('Оплата получена! Баланс пополнен.', 'success')
      setTimeout(() => router.push('/dashboard'), 2500)
    } else if (r.data.status === 'canceled') {
      stopTimers()
    }
  } catch { /* тихо повторяем */ }
}

const cancelPayment = async () => {
  canceling.value = true
  try {
    await api.post(`/payments/${paymentId}/cancel`)
    showToast('Платёж отменён', 'success')
    router.push('/dashboard')
  } catch (e) {
    showToast(e.response?.data?.message || 'Не удалось отменить', 'error')
  } finally { canceling.value = false }
}

const stopTimers = () => {
  if (statusTimer) { clearInterval(statusTimer); statusTimer = null }
  if (tickTimer) { clearInterval(tickTimer); tickTimer = null }
}

onMounted(async () => {
  if (!paymentId) { error.value = 'Не указан платёж.'; loading.value = false; return }
  await fetchDetails()
  if (!error.value) {
    tickTimer = setInterval(() => { now.value = Math.floor(Date.now() / 1000) }, 1000)
    statusTimer = setInterval(pollStatus, 10000)
    pollStatus()
  }
})
onUnmounted(stopTimers)
</script>

<style scoped>
.usdt-page { padding: 50px 0 60px; max-width: 980px; }
.usdt-head { margin-bottom: 28px; }
.back-link { color: var(--mc-diamond); text-decoration: none; font-size: 14px; display: inline-block; margin-bottom: 14px; }
.back-link:hover { text-decoration: underline; }

.status-box { padding: 48px 32px; text-align: center; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; }
.status-box.error { color: #ff7676; }
.status-box.success { color: var(--mc-emerald); }
.status-box h2 { color: var(--text-main); margin: 8px 0; }
.big-emoji { font-size: 48px; margin-bottom: 8px; }

.usdt-grid { display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
.pay-card, .info-card { padding: 28px; }

.amount-block { display: flex; flex-direction: column; gap: 6px; margin-bottom: 24px; }
.amount-label { font-size: 13px; color: var(--text-muted); }
.amount-row { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
.amount-val { font-size: 38px; font-weight: 800; color: var(--mc-emerald); line-height: 1; }
.amount-cur { font-size: 18px; font-weight: 700; color: var(--text-main); }
.amount-hint { font-size: 12px; color: var(--text-muted); }

.net-tabs { display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; }
.net-tab { padding: 8px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: var(--text-muted); cursor: pointer; font-weight: 600; font-size: 13px; transition: 0.2s; }
.net-tab:hover { border-color: rgba(255,255,255,0.25); }
.net-tab.active { border-color: var(--mc-emerald); color: var(--mc-emerald); background: rgba(0,230,118,0.08); }

.net-name { font-size: 14px; color: var(--text-main); margin-bottom: 14px; font-weight: 600; }
.net-time { color: var(--text-muted); font-weight: 400; }
.wallet-label { font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 8px; }
.wallet-row { display: flex; gap: 10px; align-items: stretch; }
.wallet { flex: 1; padding: 12px 14px; background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; font-family: monospace; font-size: 13px; word-break: break-all; color: var(--text-main); }
.copy-btn { padding: 8px 14px; border-radius: 10px; border: 1px solid rgba(0,230,118,0.3); background: rgba(0,230,118,0.08); color: var(--mc-emerald); cursor: pointer; font-size: 12px; font-weight: 700; white-space: nowrap; }
.copy-btn:hover { background: rgba(0,230,118,0.16); }
.warn { margin-top: 14px; font-size: 12px; color: var(--mc-gold); line-height: 1.5; }

.timer { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 16px; border-radius: 12px; background: rgba(0,0,0,0.25); margin-bottom: 18px; }
.timer-label { font-size: 12px; color: var(--text-muted); }
.timer-val { font-size: 30px; font-weight: 800; color: var(--text-main); font-variant-numeric: tabular-nums; }
.timer.urgent .timer-val { color: #ff7676; }

.status-line { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-muted); margin-bottom: 18px; }
.dot { width: 10px; height: 10px; border-radius: 50%; background: var(--mc-gold); box-shadow: 0 0 0 0 rgba(255,196,0,0.6); animation: pulse 1.6s infinite; }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255,196,0,0.5); } 70% { box-shadow: 0 0 0 8px rgba(255,196,0,0); } 100% { box-shadow: 0 0 0 0 rgba(255,196,0,0); } }

.steps { margin: 0 0 20px; padding-left: 20px; font-size: 13px; color: var(--text-muted); line-height: 1.7; }
.steps strong { color: var(--text-main); }
.w-full { width: 100%; }

@media (max-width: 820px) {
  .usdt-grid { grid-template-columns: 1fr; }
}
</style>
