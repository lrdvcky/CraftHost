<template>
  <div class="order-page container">
    <div class="order-head">
      <h1 class="section-title">Конфигуратор Сервера</h1>
      <p class="section-subtitle">Соберите свой идеальный сервер. Цена обновляется в реальном времени.</p>
    </div>

    <div v-if="loadingTariffs" class="glass" style="padding: 40px; text-align: center; color: var(--text-muted);">
      Загрузка тарифов...
    </div>

    <div v-else class="order-grid">
      <div class="order-form glass">

        <!-- Тарифы из БД -->
        <div class="tariff-selector">
          <label
            v-for="tariff in tariffs"
            :key="tariff.id"
            class="tariff-option"
            :class="{ active: form.tariff_id === tariff.id }"
          >
            <input type="radio" :value="tariff.id" v-model="form.tariff_id" @change="calculateQuote">
            <img :src="getTariffIcon(tariff.name)" class="pixel-img" :alt="tariff.name">
            <div class="t-info">
              <strong>{{ tariff.name }} ({{ tariff.ram_mb / 1024 }}GB RAM)</strong>
              <span>{{ tariff.price_day }} ₽ / день</span>
            </div>
            <div class="t-specs">
              <span>CPU: {{ tariff.cpu_percent }}%</span>
              <span>Disk: {{ tariff.disk_mb / 1024 }}GB</span>
              <span>Slots: {{ tariff.slots }}</span>
            </div>
          </label>
        </div>

        <div class="field-row">
          <label class="form-label">
            <span>Срок аренды (дней)</span>
            <input v-model="form.days" @input="calculateQuote" type="number" min="1" class="input-soft" placeholder="30">
          </label>

          <label class="form-label">
            <span>Версия ядра</span>
            <select v-model="form.mc_version" class="select-soft">
              <option v-for="v in mcVersions" :key="v.slug" :value="v.slug">{{ v.label }}</option>
            </select>
          </label>
        </div>

        <!-- ПРОМОКОД -->
        <div class="promo-section">
          <div class="promo-label">Промокод</div>
          <div class="promo-row">
            <input
              v-model="promoCode"
              type="text"
              class="input-soft promo-input"
              placeholder="Введите промокод"
              :disabled="!!appliedPromo"
            >
            <button
              v-if="!appliedPromo"
              @click="applyPromo"
              :disabled="!promoCode.trim() || isApplyingPromo"
              class="soft-button secondary promo-btn"
            >
              {{ isApplyingPromo ? '...' : 'Применить' }}
            </button>
            <button
              v-else
              @click="removePromo"
              class="soft-button secondary promo-btn remove"
            >
              Убрать
            </button>
          </div>
          <div v-if="promoError" class="promo-error">{{ promoError }}</div>
          <div v-if="appliedPromo" class="promo-success">
            Скидка {{ appliedPromo.discount_pct }}% применена!
          </div>
        </div>

        <button @click="submitOrder" :disabled="isSubmitting" class="soft-button primary w-full" style="margin-top: 20px;">
          {{ isSubmitting ? 'Создание...' : 'Оплатить и запустить' }}
        </button>

        <div v-if="errorMsg" class="error-box" style="margin-top: 16px;">{{ errorMsg }}</div>
      </div>

      <!-- Сводка -->
      <div class="summary-panel">
        <div class="summary-card glass">
          <h3 class="summary-label">Итого к оплате</h3>

          <div v-if="appliedPromo" class="price-original">
            {{ isLoadingQuote ? '...' : basePrice }} ₽
          </div>
          <div class="price-val" :style="appliedPromo ? 'color: var(--mc-gold)' : ''">
            {{ isLoadingQuote ? '...' : totalPrice }} ₽
          </div>
          <div class="price-period">за {{ form.days }} дней</div>

          <div v-if="appliedPromo" class="discount-badge">
            Скидка {{ appliedPromo.discount_pct }}%
          </div>

          <div class="divider"></div>
          <ul class="feature-list">
            <li>
              <img :src="mcAsset('Green_Dye_JE2_BE2.png')" class="pixel-img list-icon" alt="check">
              DDoS Защита L7
            </li>
            <li>
              <img :src="mcAsset('Green_Dye_JE2_BE2.png')" class="pixel-img list-icon" alt="check">
              Выделенный IP и порт
            </li>
            <li>
              <img :src="mcAsset('Green_Dye_JE2_BE2.png')" class="pixel-img list-icon" alt="check">
              MySQL база данных
            </li>
            <li>
              <img :src="mcAsset('Green_Dye_JE2_BE2.png')" class="pixel-img list-icon" alt="check">
              Ежедневные бэкапы
            </li>
            <li>
              <img :src="mcAsset('Green_Dye_JE2_BE2.png')" class="pixel-img list-icon" alt="check">
              Поддержка 24/7
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { mcAsset } from '../utils/assets'
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'
import { useToast } from '../utils/toast'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { showToast } = useToast()

const tariffs = ref([])
const mcVersions = ref([
  { slug: 'vanilla_1.20.4', label: 'Vanilla 1.20.4' },
  { slug: 'paper_1.20.4',   label: 'Paper 1.20.4' },
  { slug: 'forge_1.20.1',   label: 'Forge 1.20.1' },
])
const loadingTariffs = ref(true)
const form = ref({ tariff_id: null, days: 30, mc_version: 'vanilla_1.20.4' })
const basePrice = ref(0)
const totalPrice = ref(0)
const isLoadingQuote = ref(false)
const isSubmitting = ref(false)
const errorMsg = ref('')

// Промокод
const promoCode = ref('')
const appliedPromo = ref(null)
const isApplyingPromo = ref(false)
const promoError = ref('')

const tariffIcons = {
  'Dirt':    mcAsset('Grass_Block_JE4.png'),
  'Iron':    mcAsset('Block_of_Iron_JE4_BE3.png'),
  'Diamond': mcAsset('Block_of_Diamond_JE5_BE3.png'),
}
const getTariffIcon = (name) => tariffIcons[name] || mcAsset('Grass_Block_JE4.png')

const fetchTariffs = async () => {
  try {
    const res = await api.get('/tariffs')
    tariffs.value = res.data
    if (tariffs.value.length > 0) {
      // Предвыбор тарифа из ?tariff=ID (переход со страницы Тарифы)
      const wanted = Number(route.query.tariff)
      const preselected = tariffs.value.find(t => t.id === wanted)
      form.value.tariff_id = preselected ? preselected.id : tariffs.value[0].id
      await calculateQuote()
    }
  } catch {
    showToast('Ошибка загрузки тарифов', 'error')
  } finally {
    loadingTariffs.value = false
  }
}

const calculateQuote = async () => {
  if (!form.value.tariff_id) return
  isLoadingQuote.value = true
  try {
    const res = await api.post('/orders/quote', {
      tariff_id: form.value.tariff_id,
      days: form.value.days
    })
    basePrice.value = res.data.total_price
    // Применяем скидку если есть промокод
    if (appliedPromo.value) {
      const disc = appliedPromo.value.discount_pct
      totalPrice.value = (basePrice.value * (1 - disc / 100)).toFixed(2)
    } else {
      totalPrice.value = res.data.total_price
    }
  } finally {
    isLoadingQuote.value = false
  }
}

const applyPromo = async () => {
  if (!promoCode.value.trim()) return
  isApplyingPromo.value = true
  promoError.value = ''
  try {
    const res = await api.post('/promo/apply', { code: promoCode.value.trim() })
    appliedPromo.value = res.data.promo_code ?? res.data
    // Пересчитываем цену со скидкой
    const disc = appliedPromo.value.discount_pct
    totalPrice.value = (basePrice.value * (1 - disc / 100)).toFixed(2)
    showToast(`Промокод применён! Скидка ${disc}%`, 'success')
  } catch (e) {
    promoError.value = e.response?.data?.message || 'Неверный или истёкший промокод'
  } finally {
    isApplyingPromo.value = false
  }
}

const removePromo = () => {
  appliedPromo.value = null
  promoCode.value = ''
  promoError.value = ''
  totalPrice.value = basePrice.value
}

const submitOrder = async () => {
  if (!authStore.isAuthenticated) { router.push('/login'); return }
  isSubmitting.value = true; errorMsg.value = ''
  try {
    const payload = {
      ...form.value,
      promo_code: appliedPromo.value ? promoCode.value : undefined
    }
    await api.post('/orders', payload)
    await authStore.fetchUser()
    showToast('Сервер успешно создан!', 'success')
    router.push('/dashboard')
  } catch (err) {
    const data = err.response?.data
    errorMsg.value = data?.error || data?.message || 'Не удалось создать сервер'
    showToast('Не удалось создать сервер', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const fetchMcVersions = async () => {
  try {
    const res = await api.get('/mc-versions')
    if (res.data?.length) {
      mcVersions.value = res.data
      // Если текущая версия не в списке — выбираем первую
      const slugs = res.data.map(v => v.slug)
      if (!slugs.includes(form.value.mc_version)) {
        form.value.mc_version = res.data[0].slug
      }
    }
  } catch { /* остаёмся с fallback-списком */ }
}

onMounted(() => { fetchTariffs(); fetchMcVersions() })
</script>

<style scoped>
.order-page { padding: 60px 0; max-width: 1100px; margin: 0 auto; }
.order-head { text-align: center; margin-bottom: 40px; }
.order-grid { display: grid; grid-template-columns: 1fr 340px; gap: 30px; align-items: start; }
.order-form { padding: 36px; }

.tariff-selector { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
.tariff-option {
  display: flex; align-items: center; gap: 16px; padding: 16px 20px;
  background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.08);
  border-radius: var(--radius-md); cursor: pointer; transition: 0.2s;
}
.tariff-option:hover { background: rgba(0,0,0,0.35); }
.tariff-option.active { border-color: var(--mc-diamond); background: rgba(0,229,255,0.07); }
.tariff-option input { display: none; }
.tariff-option img { width: 40px; height: 40px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5)); flex-shrink: 0; }
.t-info { flex: 1; }
.t-info strong { display: block; font-size: 16px; color: var(--text-main); }
.t-info span { font-size: 13px; color: var(--text-muted); }
.t-specs { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; font-size: 12px; color: var(--text-muted); }

.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.form-label { display: flex; flex-direction: column; gap: 8px; font-size: 14px; color: var(--text-muted); font-weight: 500; }

/* Промокод */
.promo-section { margin-bottom: 4px; }
.promo-label { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-bottom: 10px; }
.promo-row { display: flex; gap: 10px; }
.promo-input { flex: 1; text-transform: uppercase; letter-spacing: 2px; }
.promo-btn { height: 50px; padding: 0 18px; font-size: 13px; white-space: nowrap; flex-shrink: 0; }
.promo-btn.remove { color: #ff5555; border-color: rgba(255,85,85,0.3); }
.promo-btn.remove:hover { border-color: #ff5555; }
.promo-error { margin-top: 8px; font-size: 13px; color: #ff5555; }
.promo-success { margin-top: 8px; font-size: 13px; color: var(--mc-emerald); font-weight: 600; }

.w-full { width: 100%; }

/* Сводка */
.summary-panel { position: sticky; top: 100px; }
.summary-card { padding: 30px; }
.summary-label { font-size: 14px; color: var(--text-muted); margin: 0 0 8px; }
.price-original { font-size: 20px; font-weight: 600; color: var(--text-muted); text-decoration: line-through; margin-bottom: 4px; }
.price-val { font-size: 52px; font-weight: 800; color: var(--mc-diamond); line-height: 1; margin-bottom: 4px; }
.price-period { font-size: 14px; color: var(--text-muted); margin-bottom: 16px; }
.discount-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; background: rgba(255,196,0,0.15); color: var(--mc-gold); font-size: 13px; font-weight: 700; margin-bottom: 20px; }
.divider { height: 1px; background: rgba(255,255,255,0.07); margin-bottom: 24px; }
.feature-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 14px; }
.feature-list li { display: flex; align-items: center; gap: 12px; font-size: 15px; }
.list-icon { width: 16px; height: 16px; flex-shrink: 0; filter: drop-shadow(0 0 4px rgba(0,230,118,0.5)); }

.error-box { padding: 16px; border-radius: var(--radius-md); background: rgba(255,85,85,0.1); color: #ff5555; border: 1px solid rgba(255,85,85,0.2); font-size: 14px; text-align: center; }

@media (max-width: 900px) {
  .order-grid { grid-template-columns: 1fr; }
  .field-row { grid-template-columns: 1fr; }
  .order-form { padding: 24px; }
  .summary-panel { position: static; }
}

@media (max-width: 768px) {
  .order-page { padding: 30px 0; }
  .order-form { padding: 20px; }
  .summary-panel { padding: 20px; }
  .feature-list li { font-size: 14px; }
}
</style>
