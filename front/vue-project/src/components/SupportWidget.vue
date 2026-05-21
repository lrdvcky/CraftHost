<template>
  <div class="support-widget">
    <!-- Кнопка-лаунчер -->
    <button class="support-fab" :class="{ open }" @click="toggle" aria-label="Поддержка">
      <svg v-if="!open" width="26" height="26" viewBox="0 0 24 24" fill="none">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none">
        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span v-if="!open && unreadHint" class="fab-badge"></span>
    </button>

    <!-- Панель -->
    <transition name="sw-pop">
      <div v-if="open" class="support-panel glass">
        <div class="sw-header">
          <div class="sw-head-info">
            <div class="sw-head-title">
              <span v-if="view === 'chat'" class="sw-back" @click="backToList">←</span>
              {{ headerTitle }}
            </div>
            <div class="sw-head-sub">Обычно отвечаем в течение 15 минут</div>
          </div>
          <button class="sw-close" @click="close">✕</button>
        </div>

        <!-- Гость -->
        <div v-if="!authStore.isAuthenticated" class="sw-body sw-center">
          <img :src="mcAsset('100px-Steve_(classic)_JE6.png')" class="pixel-img sw-illu" alt="">
          <p class="sw-muted">Войдите, чтобы написать в поддержку и отслеживать обращения.</p>
          <router-link to="/login" class="soft-button primary w-full" @click="close">Войти</router-link>
          <router-link to="/register" class="soft-button secondary w-full" @click="close" style="margin-top:10px;">Создать аккаунт</router-link>
        </div>

        <!-- Список тикетов -->
        <div v-else-if="view === 'list'" class="sw-body">
          <button class="soft-button primary w-full" @click="view = 'new'">+ Новое обращение</button>
          <div v-if="loadingTickets" class="sw-muted sw-pad">Загрузка...</div>
          <div v-else-if="!tickets.length" class="sw-muted sw-pad">У вас пока нет обращений.</div>
          <div v-else class="sw-ticket-list">
            <button v-for="t in tickets" :key="t.id" class="sw-ticket" @click="openChat(t)">
              <div class="sw-ticket-top">
                <span class="sw-ticket-subj">{{ t.subject }}</span>
                <span class="sw-ticket-status" :class="t.status">{{ statusLabel(t.status) }}</span>
              </div>
              <div class="sw-ticket-meta">#{{ t.id }} · {{ formatDate(t.updated_at || t.created_at) }}</div>
            </button>
          </div>
        </div>

        <!-- Новый тикет -->
        <div v-else-if="view === 'new'" class="sw-body">
          <label class="sw-label">Тема</label>
          <input v-model="newTicket.subject" class="input-soft" placeholder="Кратко опишите проблему">
          <label class="sw-label" style="margin-top:12px;">Сообщение</label>
          <textarea v-model="newTicket.message" class="input-soft sw-textarea" rows="4" placeholder="Подробности..."></textarea>
          <button class="soft-button primary w-full" style="margin-top:14px;"
            :disabled="creating || !newTicket.subject.trim() || !newTicket.message.trim()" @click="createTicket">
            {{ creating ? 'Отправка...' : 'Отправить' }}
          </button>
          <button class="sw-text-btn" @click="view = 'list'">Назад к списку</button>
        </div>

        <!-- Чат -->
        <div v-else-if="view === 'chat'" class="sw-chat">
          <div ref="msgBox" class="sw-messages">
            <div v-if="loadingMessages" class="sw-muted sw-pad">Загрузка...</div>
            <div v-for="m in messages" :key="m.id" class="sw-msg" :class="m.user_id === authStore.user?.id ? 'me' : 'them'">
              <div class="sw-bubble">{{ m.body }}</div>
            </div>
          </div>
          <div v-if="activeTicket?.status === 'closed'" class="sw-closed">Обращение закрыто</div>
          <div v-else class="sw-input-row">
            <textarea v-model="reply" class="input-soft sw-reply" rows="1"
              placeholder="Сообщение..." @keydown.enter.exact.prevent="sendReply"></textarea>
            <button class="sw-send" :disabled="!reply.trim() || sending" @click="sendReply">▶</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue'
import { mcAsset } from '../utils/assets'
import { supportOpen, closeSupport, toggleSupport } from '../utils/support'
import { useAuthStore } from '../stores/auth'
import { useToast } from '../utils/toast'
import api from '../api/axios'

const authStore = useAuthStore()
const { showToast } = useToast()

const open = supportOpen
const view = ref('list')               // list | new | chat
const tickets = ref([])
const loadingTickets = ref(false)
const newTicket = ref({ subject: '', message: '' })
const creating = ref(false)

const activeTicket = ref(null)
const messages = ref([])
const loadingMessages = ref(false)
const reply = ref('')
const sending = ref(false)
const msgBox = ref(null)
let pollHandle = null

const unreadHint = computed(() => tickets.value.some(t => t.status === 'answered'))

const headerTitle = computed(() => {
  if (view.value === 'new') return 'Новое обращение'
  if (view.value === 'chat') return activeTicket.value ? `Тикет #${activeTicket.value.id}` : 'Чат'
  return 'Поддержка'
})

const statusLabel = (s) => ({ open: 'Открыт', answered: 'Ответ', closed: 'Закрыт' }[s] ?? s)
const formatDate = (d) => d ? new Date(d).toLocaleDateString('ru-RU') : ''

const toggle = () => toggleSupport()
const close = () => { closeSupport() }

const fetchTickets = async () => {
  loadingTickets.value = true
  try { const r = await api.get('/tickets'); tickets.value = r.data }
  catch {}
  finally { loadingTickets.value = false }
}

const createTicket = async () => {
  creating.value = true
  try {
    const r = await api.post('/tickets', newTicket.value)
    showToast('Обращение создано', 'success')
    newTicket.value = { subject: '', message: '' }
    await fetchTickets()
    const created = tickets.value.find(t => t.id === r.data.id) || r.data
    openChat(created)
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка создания', 'error')
  } finally { creating.value = false }
}

const scrollDown = async () => {
  await nextTick()
  if (msgBox.value) msgBox.value.scrollTop = msgBox.value.scrollHeight
}

const fetchMessages = async () => {
  if (!activeTicket.value) return
  try {
    const r = await api.get(`/tickets/${activeTicket.value.id}/messages`)
    messages.value = r.data
    await scrollDown()
  } catch {}
  finally { loadingMessages.value = false }
}

const openChat = (ticket) => {
  activeTicket.value = ticket
  view.value = 'chat'
  loadingMessages.value = true
  messages.value = []
  fetchMessages()
  startPolling()
}

const backToList = () => {
  stopPolling()
  activeTicket.value = null
  view.value = 'list'
  fetchTickets()
}

const sendReply = async () => {
  const text = reply.value.trim()
  if (!text || sending.value) return
  sending.value = true
  reply.value = ''
  try {
    await api.post(`/tickets/${activeTicket.value.id}/reply`, { message: text })
    await fetchMessages()
  } catch (e) {
    reply.value = text
    showToast(e.response?.data?.message || 'Ошибка отправки', 'error')
  } finally { sending.value = false }
}

const startPolling = () => { stopPolling(); pollHandle = setInterval(fetchMessages, 5000) }
const stopPolling = () => { if (pollHandle) { clearInterval(pollHandle); pollHandle = null } }

// При открытии виджета — подгружаем тикеты.
watch(open, (isOpen) => {
  if (isOpen && authStore.isAuthenticated && view.value === 'list') {
    fetchTickets()
  }
  if (!isOpen) stopPolling()
})

onUnmounted(stopPolling)
</script>

<style scoped>
.support-widget { position: fixed; bottom: 24px; right: 24px; z-index: 9998; }

.support-fab {
  width: 60px; height: 60px; border-radius: 50%; border: none; cursor: pointer;
  background: var(--mc-emerald); color: #003314;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 8px 24px rgba(0,230,118,0.4); transition: transform 0.2s, box-shadow 0.2s;
}
.support-fab:hover { transform: translateY(-3px) scale(1.05); box-shadow: 0 12px 30px rgba(0,230,118,0.55); }
.support-fab.open { background: var(--bg-light); color: var(--text-main); border: 1px solid var(--surface-border); }
.fab-badge { position: absolute; top: 6px; right: 6px; width: 12px; height: 12px; border-radius: 50%; background: var(--mc-gold); border: 2px solid var(--bg-deep); }

.support-panel {
  position: absolute; bottom: 76px; right: 0;
  width: 360px; max-width: calc(100vw - 32px); height: 520px; max-height: calc(100vh - 120px);
  display: flex; flex-direction: column; overflow: hidden; padding: 0;
}

.sw-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.08);
  background: rgba(0,230,118,0.06);
}
.sw-head-title { font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.sw-back { cursor: pointer; color: var(--mc-emerald); font-size: 18px; }
.sw-head-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.sw-close { background: none; border: none; color: var(--text-muted); font-size: 16px; cursor: pointer; padding: 4px; }
.sw-close:hover { color: var(--text-main); }

.sw-body { flex: 1; overflow-y: auto; padding: 18px; }
.sw-center { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 4px; }
.sw-illu { width: 64px; height: 64px; margin-bottom: 8px; }
.sw-muted { color: var(--text-muted); font-size: 14px; }
.sw-pad { padding: 16px 0; text-align: center; }
.w-full { width: 100%; }

.sw-ticket-list { display: flex; flex-direction: column; gap: 10px; margin-top: 14px; }
.sw-ticket {
  text-align: left; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.08);
  border-radius: var(--radius-md); padding: 12px 14px; cursor: pointer; transition: 0.2s; width: 100%;
}
.sw-ticket:hover { border-color: var(--mc-diamond); background: rgba(0,229,255,0.05); }
.sw-ticket-top { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
.sw-ticket-subj { font-size: 14px; font-weight: 600; color: var(--text-main); }
.sw-ticket-status { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 12px; text-transform: uppercase; flex-shrink: 0; }
.sw-ticket-status.open { background: rgba(0,230,118,0.12); color: var(--mc-emerald); }
.sw-ticket-status.answered { background: rgba(0,229,255,0.12); color: var(--mc-diamond); }
.sw-ticket-status.closed { background: rgba(139,155,180,0.15); color: var(--text-muted); }
.sw-ticket-meta { font-size: 12px; color: var(--text-muted); margin-top: 6px; }

.sw-label { display: block; font-size: 13px; color: var(--text-muted); margin-bottom: 6px; }
.sw-textarea { height: auto; padding: 12px 16px; resize: vertical; line-height: 1.5; }
.sw-text-btn { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 13px; margin-top: 12px; width: 100%; }
.sw-text-btn:hover { color: var(--text-main); }

.sw-chat { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
.sw-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 8px; }
.sw-msg { display: flex; }
.sw-msg.me { justify-content: flex-end; }
.sw-bubble { max-width: 78%; padding: 9px 13px; border-radius: 14px; font-size: 14px; line-height: 1.45; word-break: break-word; }
.sw-msg.me .sw-bubble { background: rgba(0,230,118,0.14); border: 1px solid rgba(0,230,118,0.2); }
.sw-msg.them .sw-bubble { background: rgba(0,229,255,0.08); border: 1px solid rgba(0,229,255,0.15); }
.sw-closed { padding: 14px; text-align: center; color: var(--text-muted); font-size: 13px; border-top: 1px solid rgba(255,255,255,0.08); }

.sw-input-row { display: flex; gap: 8px; padding: 12px; border-top: 1px solid rgba(255,255,255,0.08); background: rgba(0,0,0,0.15); }
.sw-reply { height: 44px; min-height: 44px; padding: 11px 14px; resize: none; line-height: 1.4; }
.sw-send {
  width: 44px; height: 44px; flex-shrink: 0; border: none; border-radius: var(--radius-md);
  background: var(--mc-emerald); color: #003314; cursor: pointer; font-size: 14px; transition: 0.2s;
}
.sw-send:hover:not(:disabled) { background: #00ff84; }
.sw-send:disabled { opacity: 0.4; cursor: default; }

.sw-pop-enter-active, .sw-pop-leave-active { transition: opacity 0.2s, transform 0.2s; transform-origin: bottom right; }
.sw-pop-enter-from, .sw-pop-leave-to { opacity: 0; transform: scale(0.9) translateY(10px); }

@media (max-width: 480px) {
  .support-widget { bottom: 16px; right: 16px; }
}
</style>
