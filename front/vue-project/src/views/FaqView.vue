<template>
  <div class="faq-page container">
    <div class="faq-head">
      <div class="badge">
        <img :src="mcAsset('Knowledge_Book_JE2.png')" class="inline-icon pixel-img" alt="">
        База знаний
      </div>
      <h1 class="section-title">Частые вопросы</h1>
      <p class="section-subtitle">Всё о работе CraftHost: оплата, серверы, панель и поддержка.</p>
      <input v-model="search" class="input-soft faq-search" placeholder="Поиск по вопросам...">
    </div>

    <div class="faq-layout">
      <!-- Категории -->
      <aside class="faq-cats">
        <button
          v-for="cat in categories"
          :key="cat.key"
          class="faq-cat-btn"
          :class="{ active: activeCat === cat.key }"
          @click="activeCat = cat.key"
        >
          {{ cat.label }}
        </button>
      </aside>

      <!-- Аккордеон -->
      <div class="faq-list">
        <div v-if="!filtered.length" class="glass faq-empty">
          По запросу «{{ search }}» ничего не найдено.
        </div>
        <div
          v-for="(item, idx) in filtered"
          :key="item.q"
          class="glass faq-item"
          :class="{ open: opened === item.q }"
        >
          <button class="faq-q" @click="toggle(item.q)">
            <span>{{ item.q }}</span>
            <span class="faq-arrow">{{ opened === item.q ? '−' : '+' }}</span>
          </button>
          <transition name="faq-slide">
            <div v-if="opened === item.q" class="faq-a">
              <p>{{ item.a }}</p>
            </div>
          </transition>
        </div>
      </div>
    </div>

    <!-- Не нашли ответ -->
    <section class="faq-contact glass">
      <div>
        <h3>Не нашли ответ?</h3>
        <p>Напишите в поддержку — ответим в среднем за 15 минут.</p>
      </div>
      <button class="soft-button primary" @click="openSupport">Написать в поддержку</button>
    </section>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { mcAsset } from '../utils/assets'
import { openSupport } from '../utils/support'

const search = ref('')
const activeCat = ref('all')
const opened = ref(null)

const categories = [
  { key: 'all',      label: 'Все вопросы' },
  { key: 'start',    label: 'Начало работы' },
  { key: 'billing',  label: 'Оплата и продление' },
  { key: 'server',   label: 'Сервер и панель' },
  { key: 'tech',     label: 'Технические' },
  { key: 'support',  label: 'Поддержка' },
]

const faqs = [
  // Начало работы
  { cat: 'start', q: 'Как создать сервер на CraftHost?', a: 'Зарегистрируйтесь, пополните баланс в личном кабинете, откройте конфигуратор, выберите тариф, версию ядра и срок аренды — сервер поднимется автоматически за 60 секунд.' },
  { cat: 'start', q: 'Нужно ли что-то устанавливать на компьютер?', a: 'Нет. Управление сервером полностью через браузер: панель Pterodactyl, веб-консоль, файловый менеджер и бэкапы доступны с любого устройства.' },
  { cat: 'start', q: 'Какую версию Minecraft выбрать?', a: 'При заказе доступны Vanilla, Paper, Forge и Fabric разных версий. Для плагинов выбирайте Paper/Spigot, для модов — Forge/Fabric. Версию можно сменить позже.' },
  { cat: 'start', q: 'Можно ли пригласить друзей администрировать сервер?', a: 'Да, в панели Pterodactyl можно выдать доступ к серверу другим аккаунтам с настраиваемыми правами.' },

  // Оплата и продление
  { cat: 'billing', q: 'Как работает оплата?', a: 'Вы пополняете внутренний баланс, а аренда сервера списывается с него. Тарификация посуточная — платите только за выбранный срок.' },
  { cat: 'billing', q: 'Как продлить сервер?', a: 'В личном кабинете на карточке сервера нажмите «Продлить», выберите количество дней — сумма спишется с баланса, а срок действия сервера увеличится. Если баланса не хватает, сначала пополните его.' },
  { cat: 'billing', q: 'Что будет, если не продлить вовремя?', a: 'По истечении срока сервер приостанавливается, но данные сохраняются некоторое время. После продления сервер автоматически возобновляет работу.' },
  { cat: 'billing', q: 'Можно ли использовать промокод?', a: 'Да. Введите промокод в конфигураторе при заказе — скидка применится к стоимости аренды автоматически.' },
  { cat: 'billing', q: 'Есть ли реферальная программа?', a: 'Да. Делитесь реферальной ссылкой из кабинета и получайте 10% на баланс с каждой оплаты приглашённых пользователей.' },
  { cat: 'billing', q: 'Возможен ли возврат средств?', a: 'Неизрасходованный баланс и спорные ситуации рассматриваются индивидуально — напишите в поддержку через чат.' },

  // Сервер и панель
  { cat: 'server', q: 'Как управлять сервером с консоли?', a: 'На карточке сервера нажмите «Консоль» — откроется веб-консоль с выводом логов в реальном времени и полем для ввода команд (op, whitelist, say, stop и любых других).' },
  { cat: 'server', q: 'Как запустить, остановить или перезагрузить сервер?', a: 'Кнопки управления питанием (Start / Stop / Restart) есть и на карточке сервера в кабинете, и на странице консоли.' },
  { cat: 'server', q: 'Как сделать бэкап мира?', a: 'На карточке сервера раскройте раздел «Бэкапы» и нажмите «Создать бэкап». Также действуют автоматические ежедневные резервные копии.' },
  { cat: 'server', q: 'Как установить плагины и моды?', a: 'Через файловый менеджер панели Pterodactyl: загрузите файлы в папки plugins или mods и перезапустите сервер. Ядро можно сменить в настройках.' },
  { cat: 'server', q: 'Могу ли я загружать собственные сборки и модпаки?', a: 'Да. Загрузите файлы сборки через файловый менеджер или SFTP и укажите нужный стартовый jar.' },

  // Технические
  { cat: 'tech', q: 'Какое железо используется?', a: 'Процессоры AMD EPYC / Intel Xeon с высокой тактовой частотой, оперативная память DDR4 ECC и NVMe SSD-накопители со скоростью до 3500 МБ/с.' },
  { cat: 'tech', q: 'Защищены ли серверы от DDoS?', a: 'Да, на всех тарифах включена сетевая DDoS-защита уровня L7 без дополнительной платы.' },
  { cat: 'tech', q: 'Какой аптайм вы гарантируете?', a: 'Целевая доступность инфраструктуры — 99.9%. Плановые работы проводятся с предварительным уведомлением.' },
  { cat: 'tech', q: 'Где расположены серверы?', a: 'Дата-центры размещены так, чтобы обеспечить низкий пинг для игроков из России и СНГ.' },
  { cat: 'tech', q: 'Получу ли я выделенный IP и порт?', a: 'Да, каждому серверу выдаётся адрес вида IP:порт, который вы видите на карточке сервера в кабинете.' },

  // Поддержка
  { cat: 'support', q: 'Как связаться с поддержкой?', a: 'Нажмите кнопку чата в правом нижнем углу на любой странице или создайте тикет в разделе «Поддержка» личного кабинета. Поддержка работает 24/7.' },
  { cat: 'support', q: 'Как быстро отвечает поддержка?', a: 'Среднее время ответа — около 15 минут. На приоритетных тарифах обращения обрабатываются в первую очередь.' },
  { cat: 'support', q: 'Что делать при ошибке создания сервера?', a: 'Откройте чат поддержки и опишите проблему — администратор примет ваш тикет в работу и поможет восстановить сервер.' },
]

const toggle = (q) => { opened.value = opened.value === q ? null : q }

const filtered = computed(() => {
  const s = search.value.trim().toLowerCase()
  return faqs.filter(f => {
    const catOk = activeCat.value === 'all' || f.cat === activeCat.value
    const searchOk = !s || f.q.toLowerCase().includes(s) || f.a.toLowerCase().includes(s)
    return catOk && searchOk
  })
})
</script>

<style scoped>
.faq-page { padding: 60px 0 40px; }
.faq-head { text-align: center; max-width: 680px; margin: 0 auto 44px; }
.badge {
  display: inline-flex; align-items: center; gap: 8px; margin-bottom: 18px;
  background: rgba(0,229,255,0.1); color: var(--mc-diamond);
  padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;
  border: 1px solid rgba(0,229,255,0.2);
}
.inline-icon { width: 18px; height: 18px; }
.faq-search { max-width: 460px; margin: 16px auto 0; }

.faq-layout { display: grid; grid-template-columns: 220px 1fr; gap: 32px; align-items: start; }

.faq-cats { display: flex; flex-direction: column; gap: 6px; position: sticky; top: 100px; }
.faq-cat-btn {
  text-align: left; padding: 11px 16px; border-radius: var(--radius-md); border: none;
  background: transparent; color: var(--text-muted); font-size: 14px; font-weight: 600;
  cursor: pointer; transition: 0.2s; font-family: var(--font-main);
}
.faq-cat-btn:hover { background: rgba(255,255,255,0.04); color: var(--text-main); }
.faq-cat-btn.active { background: rgba(0,229,255,0.1); color: var(--mc-diamond); }

.faq-list { display: flex; flex-direction: column; gap: 12px; }
.faq-empty { padding: 32px; text-align: center; color: var(--text-muted); }
.faq-item { padding: 0; overflow: hidden; transition: border-color 0.2s; }
.faq-item.open { border-color: rgba(0,229,255,0.25); }
.faq-q {
  width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 16px;
  padding: 20px 24px; background: none; border: none; cursor: pointer;
  font-size: 16px; font-weight: 600; color: var(--text-main); text-align: left; font-family: var(--font-main);
}
.faq-q:hover { color: var(--mc-diamond); }
.faq-arrow { font-size: 22px; color: var(--mc-diamond); flex-shrink: 0; line-height: 1; }
.faq-a { padding: 0 24px 22px; }
.faq-a p { margin: 0; color: var(--text-muted); font-size: 15px; line-height: 1.7; }

.faq-slide-enter-active, .faq-slide-leave-active { transition: all 0.25s ease; overflow: hidden; }
.faq-slide-enter-from, .faq-slide-leave-to { opacity: 0; max-height: 0; }
.faq-slide-enter-to, .faq-slide-leave-from { opacity: 1; max-height: 240px; }

.faq-contact {
  margin-top: 48px; padding: 32px 40px;
  display: flex; align-items: center; justify-content: space-between; gap: 24px;
}
.faq-contact h3 { margin: 0 0 6px; font-size: 20px; }
.faq-contact p { margin: 0; color: var(--text-muted); }

@media (max-width: 800px) {
  .faq-layout { grid-template-columns: 1fr; }
  .faq-cats { position: static; flex-direction: row; flex-wrap: wrap; }
  .faq-contact { flex-direction: column; text-align: center; }
}
</style>
