<template>
  <div class="pricing-page container">

    <div class="pricing-head">
      <div class="badge">
        <img :src="mcAsset('Emerald_JE3_BE3.png')" class="inline-icon pixel-img" alt="">
        Прозрачные цены
      </div>
      <h1 class="section-title">Тарифы CraftHost</h1>
      <p class="section-subtitle">
        Выделенные ресурсы, NVMe-диски и DDoS-защита на каждом тарифе.
        Платите по дням — без долгих контрактов и скрытых платежей.
      </p>
    </div>

    <div v-if="loading" class="glass loading-box">Загрузка тарифов...</div>

    <template v-else>
      <!-- Карточки тарифов -->
      <div class="plans-grid">
        <div
          v-for="(t, i) in plans"
          :key="t.id"
          class="glass plan-card"
          :class="{ featured: isFeatured(i) }"
        >
          <div v-if="isFeatured(i)" class="plan-ribbon">Популярный</div>

          <div class="plan-top">
            <img :src="meta(t).icon" class="plan-icon pixel-img" :alt="t.name">
            <div>
              <h3 class="plan-name">{{ t.name }}</h3>
              <p class="plan-tag">{{ meta(t).tagline }}</p>
            </div>
          </div>

          <div class="plan-price">
            <span class="pp-value">{{ Math.round(t.price_day) }} ₽</span>
            <span class="pp-period">/ день</span>
          </div>
          <div class="plan-month">≈ {{ Math.round(t.price_day * 30) }} ₽ / месяц</div>

          <ul class="plan-specs">
            <li><span>Память</span><strong>{{ gb(t.ram_mb) }} GB RAM</strong></li>
            <li><span>Процессор</span><strong>{{ t.cpu_percent }}% vCPU</strong></li>
            <li><span>NVMe-диск</span><strong>{{ gb(t.disk_mb) }} GB</strong></li>
            <li><span>Слотов</span><strong>{{ t.slots }} игроков</strong></li>
          </ul>

          <p class="plan-desc">{{ meta(t).desc }}</p>

          <ul class="plan-features">
            <li v-for="f in meta(t).features" :key="f">
              <img :src="mcAsset('Green_Dye_JE2_BE2.png')" class="pixel-img tick" alt="">
              {{ f }}
            </li>
          </ul>

          <router-link to="/order" class="soft-button w-full" :class="isFeatured(i) ? 'primary' : 'secondary'">
            Выбрать тариф
          </router-link>
        </div>
      </div>

      <!-- Что входит во все тарифы -->
      <section class="included-section">
        <h2 class="section-title center">Включено в каждый тариф</h2>
        <div class="included-grid">
          <div v-for="inc in includedAll" :key="inc.title" class="glass included-card">
            <img :src="mcAsset(inc.icon)" class="inc-icon pixel-img" alt="">
            <h4>{{ inc.title }}</h4>
            <p>{{ inc.text }}</p>
          </div>
        </div>
      </section>

      <!-- Таблица сравнения -->
      <section class="compare-section">
        <h2 class="section-title center">Сравнение тарифов</h2>
        <div class="glass compare-wrap">
          <table class="compare-table">
            <thead>
              <tr>
                <th>Характеристика</th>
                <th v-for="t in plans" :key="t.id">{{ t.name }}</th>
              </tr>
            </thead>
            <tbody>
              <tr><td>RAM</td><td v-for="t in plans" :key="t.id">{{ gb(t.ram_mb) }} GB</td></tr>
              <tr><td>vCPU</td><td v-for="t in plans" :key="t.id">{{ t.cpu_percent }}%</td></tr>
              <tr><td>NVMe-диск</td><td v-for="t in plans" :key="t.id">{{ gb(t.disk_mb) }} GB</td></tr>
              <tr><td>Слоты</td><td v-for="t in plans" :key="t.id">{{ t.slots }}</td></tr>
              <tr><td>Цена / день</td><td v-for="t in plans" :key="t.id">{{ Math.round(t.price_day) }} ₽</td></tr>
              <tr><td>DDoS-защита L7</td><td v-for="t in plans" :key="t.id"><span class="yes">✓</span></td></tr>
              <tr><td>Ежедневные бэкапы</td><td v-for="t in plans" :key="t.id"><span class="yes">✓</span></td></tr>
              <tr><td>MySQL база данных</td><td v-for="t in plans" :key="t.id"><span class="yes">✓</span></td></tr>
              <tr><td>Панель Pterodactyl</td><td v-for="t in plans" :key="t.id"><span class="yes">✓</span></td></tr>
              <tr><td>Поддержка 24/7</td><td v-for="t in plans" :key="t.id"><span class="yes">✓</span></td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- CTA -->
      <section class="pricing-cta glass">
        <h2>Не уверены, какой тариф выбрать?</h2>
        <p>Соберите конфигурацию в реальном времени или задайте вопрос поддержке — поможем подобрать.</p>
        <div class="cta-actions">
          <router-link to="/order" class="soft-button primary">Открыть конфигуратор</router-link>
          <router-link to="/faq" class="soft-button secondary">Частые вопросы</router-link>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { mcAsset } from '../utils/assets'
import api from '../api/axios'
import { useToast } from '../utils/toast'

const { showToast } = useToast()
const plans = ref([])
const loading = ref(true)

const gb = (mb) => (mb / 1024 % 1 === 0 ? mb / 1024 : (mb / 1024).toFixed(1))
const isFeatured = (i) => plans.value.length >= 3 ? i === 1 : i === plans.value.length - 1

// Описания по названию тарифа (в БД нет текстового описания — задаём на фронте).
const metaMap = {
  Dirt: {
    icon: 'Grass_Block_JE4.png',
    tagline: 'Для друзей и старта',
    desc: 'Идеально для ванильной игры небольшой компанией: выживание, креатив, первые плагины.',
    features: ['Vanilla / Paper / Spigot', 'До 10 модов или плагинов', 'Запуск за 60 секунд'],
  },
  Iron: {
    icon: 'Block_of_Iron_JE4_BE3.png',
    tagline: 'Для активных серверов',
    desc: 'Сбалансированный тариф для сборок с модами и десятками игроков онлайн без лагов.',
    features: ['Forge / Fabric сборки', 'Десятки модов и плагинов', 'Приоритетная очередь поддержки'],
  },
  Diamond: {
    icon: 'Block_of_Diamond_JE5_BE3.png',
    tagline: 'Для крупных проектов',
    desc: 'Максимум ресурсов для тяжёлых модпаков и публичных проектов с большим онлайном.',
    features: ['Тяжёлые модпаки (RLCraft и др.)', 'Большой стабильный онлайн', 'Расширенные ресурсы CPU/RAM'],
  },
}
const fallbackMeta = {
  icon: 'Grass_Block_JE4.png',
  tagline: 'Игровой сервер',
  desc: 'Выделенные ресурсы, NVMe-диск и панель управления Pterodactyl.',
  features: ['Любое ядро Minecraft', 'Веб-консоль и файловый менеджер', 'Поддержка 24/7'],
}
const meta = (t) => metaMap[t.name] || fallbackMeta

const includedAll = [
  { icon: 'Amethyst_Shard_JE2_BE1.png', title: 'DDoS-защита L7', text: 'Сетевая фильтрация атак на каждом сервере без доплат.' },
  { icon: '160px-Minecart_with_Chest_JE4_BE2.png', title: 'Ежедневные бэкапы', text: 'Автоматические резервные копии мира на отдельном хранилище.' },
  { icon: 'Knowledge_Book_JE2.png', title: 'Панель Pterodactyl', text: 'Веб-консоль, файлы, расписания и мониторинг ресурсов.' },
  { icon: 'Nether_Star_JE2_BE1.png', title: 'Любое ядро', text: 'Vanilla, Paper, Spigot, Forge, Fabric — установка в один клик.' },
]

const fetchTariffs = async () => {
  try {
    const r = await api.get('/tariffs')
    plans.value = r.data
  } catch {
    showToast('Не удалось загрузить тарифы', 'error')
  } finally { loading.value = false }
}

onMounted(fetchTariffs)
</script>

<style scoped>
.pricing-page { padding: 60px 0 40px; }
.pricing-head { text-align: center; max-width: 720px; margin: 0 auto 48px; }
.badge {
  display: inline-flex; align-items: center; gap: 8px; margin-bottom: 18px;
  background: rgba(0,230,118,0.1); color: var(--mc-emerald);
  padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;
  border: 1px solid rgba(0,230,118,0.2);
}
.inline-icon { width: 18px; height: 18px; }
.loading-box { padding: 48px; text-align: center; color: var(--text-muted); }
.center { text-align: center; }

/* Карточки */
.plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 80px; }
.plan-card { padding: 32px 28px; display: flex; flex-direction: column; position: relative; transition: transform 0.3s, border-color 0.3s; }
.plan-card:hover { transform: translateY(-6px); }
.plan-card.featured { border-color: var(--mc-emerald); box-shadow: 0 20px 50px rgba(0,230,118,0.18); }
.plan-ribbon {
  position: absolute; top: 18px; right: 18px; font-size: 11px; font-weight: 800; text-transform: uppercase;
  background: var(--mc-emerald); color: #003314; padding: 4px 12px; border-radius: 20px; letter-spacing: 0.5px;
}
.plan-top { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
.plan-icon { width: 48px; height: 48px; filter: drop-shadow(0 6px 12px rgba(0,0,0,0.5)); }
.plan-name { margin: 0; font-size: 22px; font-weight: 800; }
.plan-tag { margin: 2px 0 0; font-size: 13px; color: var(--text-muted); }

.plan-price { display: flex; align-items: baseline; gap: 6px; }
.pp-value { font-size: 40px; font-weight: 800; color: var(--mc-emerald); line-height: 1; }
.pp-period { font-size: 15px; color: var(--text-muted); }
.plan-month { font-size: 13px; color: var(--text-muted); margin: 6px 0 20px; }

.plan-specs { list-style: none; padding: 0; margin: 0 0 18px; display: flex; flex-direction: column; gap: 10px; border-top: 1px solid rgba(255,255,255,0.07); padding-top: 18px; }
.plan-specs li { display: flex; justify-content: space-between; font-size: 14px; }
.plan-specs span { color: var(--text-muted); }
.plan-specs strong { color: var(--text-main); }

.plan-desc { font-size: 14px; color: var(--text-muted); line-height: 1.6; margin: 0 0 16px; }
.plan-features { list-style: none; padding: 0; margin: 0 0 24px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.plan-features li { display: flex; align-items: center; gap: 10px; font-size: 14px; }
.tick { width: 15px; height: 15px; flex-shrink: 0; filter: drop-shadow(0 0 4px rgba(0,230,118,0.5)); }
.w-full { width: 100%; }

/* Включено во все */
.included-section { margin-bottom: 80px; }
.included-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 36px; }
.included-card { padding: 26px 22px; }
.inc-icon { width: 40px; height: 40px; margin-bottom: 14px; }
.included-card h4 { margin: 0 0 8px; font-size: 16px; }
.included-card p { margin: 0; font-size: 14px; color: var(--text-muted); line-height: 1.6; }

/* Таблица */
.compare-section { margin-bottom: 80px; }
.compare-wrap { padding: 8px; margin-top: 36px; overflow-x: auto; }
.compare-table { width: 100%; border-collapse: collapse; min-width: 480px; }
.compare-table th, .compare-table td { padding: 14px 18px; text-align: center; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.06); }
.compare-table th { font-weight: 700; color: var(--text-main); }
.compare-table th:first-child, .compare-table td:first-child { text-align: left; color: var(--text-muted); }
.compare-table tbody tr:last-child td { border-bottom: none; }
.yes { color: var(--mc-emerald); font-weight: 800; }

/* CTA */
.pricing-cta { padding: 48px 40px; text-align: center; }
.pricing-cta h2 { font-size: 28px; margin: 0 0 12px; }
.pricing-cta p { color: var(--text-muted); margin: 0 0 28px; }
.cta-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

@media (max-width: 600px) {
  .cta-actions { flex-direction: column; }
}
</style>
