import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const BRAND = 'CraftHost'
const DEFAULT_DESC = 'Хостинг Minecraft-серверов с панелью Pterodactyl, NVMe-дисками и DDoS-защитой.'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/', name: 'home',
      component: () => import('../views/HomeView.vue'),
      meta: {
        title: 'CraftHost — хостинг Minecraft-серверов с панелью Pterodactyl',
        description: 'Запусти свой Minecraft-сервер за 60 секунд. NVMe, DDoS-защита, любое ядро, моды и плагины в один клик.',
      },
    },
    {
      path: '/order', name: 'order',
      component: () => import('../views/OrderView.vue'),
      meta: {
        title: 'Заказ сервера — CraftHost',
        description: 'Соберите Minecraft-сервер под себя: тариф, версия, локация. Запуск за 60 секунд.',
      },
    },
    {
      path: '/pricing', name: 'pricing',
      component: () => import('../views/PricingView.vue'),
      meta: {
        title: 'Тарифы Minecraft-хостинга — CraftHost',
        description: 'Прозрачные цены на аренду Minecraft-серверов. От 5 ₽/день, NVMe, DDoS-защита и Pterodactyl на каждом тарифе.',
      },
    },
    {
      path: '/faq', name: 'faq',
      component: () => import('../views/FaqView.vue'),
      meta: {
        title: 'FAQ — частые вопросы — CraftHost',
        description: 'Ответы на популярные вопросы об аренде Minecraft-сервера: оплата, моды, бэкапы, поддержка.',
      },
    },
    {
      path: '/login', name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { title: 'Вход — CraftHost', description: 'Вход в личный кабинет CraftHost.' },
    },
    {
      path: '/register', name: 'register',
      component: () => import('../views/RegisterView.vue'),
      meta: { title: 'Регистрация — CraftHost', description: 'Создайте аккаунт CraftHost и запустите свой первый Minecraft-сервер.' },
    },
    {
      path: '/forgot-password', name: 'forgot-password',
      component: () => import('../views/ForgotPasswordView.vue'),
      meta: { title: 'Сброс пароля — CraftHost', noindex: true },
    },
    {
      path: '/reset-password', name: 'reset-password',
      component: () => import('../views/ResetPasswordView.vue'),
      meta: { title: 'Новый пароль — CraftHost', noindex: true },
    },
    {
      path: '/verify-email', name: 'verify-email',
      component: () => import('../views/VerifyEmailView.vue'),
      meta: { title: 'Подтверждение email — CraftHost', noindex: true },
    },
    {
      path: '/dashboard', name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true, title: 'Личный кабинет — CraftHost', noindex: true },
    },
    {
      path: '/servers/:id/console', name: 'console',
      component: () => import('../views/ConsoleView.vue'),
      meta: { requiresAuth: true, title: 'Консоль сервера — CraftHost', noindex: true },
    },
    {
      path: '/tickets/:id', name: 'ticket',
      component: () => import('../views/TicketView.vue'),
      meta: { requiresAuth: true, title: 'Тикет поддержки — CraftHost', noindex: true },
    },
    {
      path: '/payment/usdt', name: 'payment-usdt',
      component: () => import('../views/PaymentUsdtView.vue'),
      meta: { requiresAuth: true, title: 'Оплата USDT — CraftHost', noindex: true },
    },
    {
      path: '/payment/result', name: 'payment-result',
      component: () => import('../views/PaymentResultView.vue'),
      meta: { requiresAuth: true, title: 'Результат оплаты — CraftHost', noindex: true },
    },
    {
      path: '/terms', name: 'terms',
      component: () => import('../views/TermsView.vue'),
      meta: {
        title: 'Условия использования — CraftHost',
        description: 'Условия использования сервиса CraftHost: регистрация, оплата, правила и ответственность.',
      },
    },
    {
      path: '/privacy', name: 'privacy',
      component: () => import('../views/PrivacyView.vue'),
      meta: {
        title: 'Политика конфиденциальности — CraftHost',
        description: 'Политика конфиденциальности CraftHost: какие данные мы собираем, как используем и защищаем.',
      },
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
  scrollBehavior(to) {
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  }
})

const setMeta = (name, value, attr = 'name') => {
  if (typeof document === 'undefined') return
  let el = document.querySelector(`meta[${attr}="${name}"]`)
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, name)
    document.head.appendChild(el)
  }
  el.setAttribute('content', value)
}

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  if (!authStore.isAuthenticated && localStorage.getItem('token')) {
    await authStore.fetchUser()
  }
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }
  next()
})

router.afterEach((to) => {
  if (typeof document === 'undefined') return
  document.title = to.meta?.title || `${BRAND} — хостинг Minecraft-серверов`
  setMeta('description', to.meta?.description || DEFAULT_DESC)
  setMeta('robots', to.meta?.noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large')
  // OG tags обновляем под текущую страницу
  setMeta('og:title', to.meta?.title || `${BRAND}`, 'property')
  setMeta('og:description', to.meta?.description || DEFAULT_DESC, 'property')
  setMeta('og:url', `https://crafthost.ru${to.fullPath}`, 'property')
})

export default router
