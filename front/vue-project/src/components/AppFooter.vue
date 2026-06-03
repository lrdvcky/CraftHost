<template>
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <!-- Бренд -->
        <div class="footer-brand">
          <router-link to="/" class="footer-logo">
            <span style="color: var(--mc-emerald)">Craft</span>Host
          </router-link>
          <p class="footer-desc">
            Премиальный хостинг Minecraft-серверов. Высокочастотные процессоры,
            NVMe-накопители и панель Pterodactyl. Запуск сервера за 60 секунд.
          </p>
          <div class="footer-stats">
            <span class="footer-chip"><span class="dot"></span> 99.9% Uptime</span>
            <span class="footer-chip">NVMe SSD</span>
            <span class="footer-chip">DDoS-защита</span>
          </div>
        </div>

        <!-- Колонки ссылок -->
        <div class="footer-col">
          <h4 class="footer-title">Навигация</h4>
          <router-link to="/" class="footer-link">Главная</router-link>
          <router-link to="/pricing" class="footer-link">Тарифы</router-link>
          <router-link to="/order" class="footer-link">Конфигуратор</router-link>
          <router-link to="/faq" class="footer-link">Вопросы и ответы</router-link>
        </div>

        <div class="footer-col">
          <h4 class="footer-title">Возможности</h4>
          <a href="/#advantages" class="footer-link" @click.prevent="scrollTo('#advantages')">Преимущества</a>
          <a href="/#how-it-works" class="footer-link" @click.prevent="scrollTo('#how-it-works')">Как это работает</a>
          <router-link to="/dashboard" class="footer-link">Управление сервером</router-link>
          <router-link to="/dashboard" class="footer-link">Личный кабинет</router-link>
        </div>

        <div class="footer-col">
          <h4 class="footer-title">Поддержка</h4>
          <router-link to="/faq" class="footer-link">База знаний</router-link>
          <a href="#" class="footer-link" @click.prevent="openSupport">Чат с поддержкой</a>
          <a href="mailto:support@crafthost.ru" class="footer-link">support@crafthost.ru</a>
          <a href="#" class="footer-link" @click.prevent>Discord-сообщество</a>
        </div>
      </div>

      <div class="footer-bottom">
        <span class="footer-copy">© {{ year }} CraftHost. Все права защищены.</span>
        <div class="footer-legal">
          <router-link to="/terms" class="footer-link sm">Условия использования</router-link>
          <router-link to="/privacy" class="footer-link sm">Политика конфиденциальности</router-link>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { openSupport } from '../utils/support'

const router = useRouter()
const year = new Date().getFullYear()

const scrollTo = async (hash) => {
  if (router.currentRoute.value.path !== '/') {
    // Переходим на главную с хешем — scrollBehavior в роутере подхватит
    await router.push({ path: '/', hash })
    // Подстраховка: если scrollBehavior не сработал (lazy-load компонента),
    // ждём рендер и скроллим вручную
    setTimeout(() => {
      document.querySelector(hash)?.scrollIntoView({ behavior: 'smooth' })
    }, 300)
  } else {
    document.querySelector(hash)?.scrollIntoView({ behavior: 'smooth' })
  }
}
</script>

<style scoped>
.site-footer {
  position: relative; z-index: 10;
  margin-top: 80px; padding: 60px 0 30px;
  border-top: 1px solid rgba(255,255,255,0.07);
  background: linear-gradient(180deg, transparent 0%, rgba(0,0,0,0.25) 100%);
}
.footer-grid {
  display: grid; grid-template-columns: 1.6fr 1fr 1fr 1fr; gap: 40px;
  padding-bottom: 40px; border-bottom: 1px solid rgba(255,255,255,0.07);
}
.footer-logo { font-family: var(--font-pixel); font-size: 16px; display: inline-block; margin-bottom: 16px; }
.footer-desc { color: var(--text-muted); font-size: 14px; line-height: 1.7; margin: 0 0 18px; max-width: 380px; }
.footer-stats { display: flex; flex-wrap: wrap; gap: 8px; }
.footer-chip {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--text-muted);
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
  padding: 5px 12px; border-radius: 20px;
}
.footer-chip .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--mc-emerald); box-shadow: 0 0 6px rgba(0,230,118,0.6); }

.footer-title { font-size: 14px; font-weight: 700; margin: 0 0 16px; color: var(--text-main); text-transform: uppercase; letter-spacing: 0.5px; }
.footer-col { display: flex; flex-direction: column; gap: 10px; }
.footer-link { color: var(--text-muted); font-size: 14px; transition: color 0.2s, transform 0.2s; width: fit-content; }
.footer-link:hover { color: var(--mc-emerald); transform: translateX(3px); }
.footer-link.sm { font-size: 13px; }

.footer-bottom { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding-top: 24px; }
.footer-copy { color: var(--text-muted); font-size: 13px; }
.footer-legal { display: flex; gap: 24px; }

@media (max-width: 900px) {
  .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
}

@media (max-width: 768px) {
  .site-footer { margin-top: 50px; padding: 40px 0 24px; }

  /* Всё в одну колонку и по центру */
  .footer-grid { grid-template-columns: 1fr; gap: 28px; text-align: center; }
  .footer-brand { display: flex; flex-direction: column; align-items: center; }
  .footer-desc { max-width: none; margin-left: auto; margin-right: auto; }
  .footer-stats { justify-content: center; }
  .footer-col { align-items: center; }
  .footer-link { width: auto; }
  .footer-link:hover { transform: none; } /* без сдвига вправо на тач-экранах */

  .footer-bottom {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 14px;
  }
  .footer-legal { flex-direction: column; gap: 8px; align-items: center; }
}
</style>
