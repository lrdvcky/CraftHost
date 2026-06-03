<template>
  <div class="ticket-page container">

    <!-- ХЛЕБНЫЕ КРОШКИ -->
    <div class="breadcrumbs">
      <router-link to="/dashboard" class="bc-link">← Назад в кабинет</router-link>
      <span class="bc-sep">/</span>
      <span class="bc-cur">Тикет #{{ ticketId }}</span>
      <span v-if="ticket" class="ticket-status-badge" :class="ticket.status">
        {{ ticket.status === 'open' ? 'Открыт' : 'Закрыт' }}
      </span>
    </div>

    <!-- ЗАГОЛОВОК -->
    <div class="ticket-header glass" v-if="ticket">
      <div>
        <h1 class="ticket-subject-title">{{ ticket.subject }}</h1>
        <p class="ticket-meta-line">
          Создан {{ new Date(ticket.created_at).toLocaleString('ru-RU') }}
        </p>
      </div>
      <button
        v-if="ticket.status === 'open'"
        @click="closeTicket"
        :disabled="isClosing"
        class="soft-button secondary close-btn"
      >
        {{ isClosing ? '...' : 'Закрыть тикет' }}
      </button>
    </div>

    <!-- ЧАТ -->
    <div class="chat-wrapper glass">
      <div ref="chatBox" class="chat-box">
        <div v-if="loading" class="chat-empty">
          <div class="loading-dots"><span></span><span></span><span></span></div>
        </div>
        <div v-else-if="!messages.length" class="chat-empty">
          <p>Нет сообщений. Начните диалог с поддержкой.</p>
        </div>
        <div
          v-for="msg in messages"
          :key="msg.id"
          class="message-row"
          :class="{ 'from-me': msg.user_id === authStore.user?.id, 'from-admin': msg.user_id !== authStore.user?.id }"
        >
          <div class="msg-avatar">
            <img
              v-if="msg.user?.role === 'admin'"
              :src="mcAsset('100px-Steve_(classic)_JE6.png')"
              class="pixel-img avatar-img"
              alt="Admin"
            >
            <img
              v-else
              :src="mcAsset('150px-Alex_(slim)_JE2.png')"
              class="pixel-img avatar-img"
              alt="User"
            >
          </div>
          <div class="msg-bubble">
            <div class="msg-meta">
              <span class="msg-author">{{ msg.user?.role === 'admin' ? 'Администратор' : 'Вы' }}</span>
              <span class="msg-time">{{ new Date(msg.created_at).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }) }}</span>
            </div>
            <div class="msg-body">{{ msg.body }}</div>
          </div>
        </div>
      </div>

      <!-- ФОРМА ОТВЕТА -->
      <div class="chat-footer" :class="{ disabled: ticket?.status === 'closed' }">
        <textarea
          v-model="newMessage"
          :disabled="ticket?.status === 'closed'"
          class="chat-input"
          rows="2"
          :placeholder="ticket?.status === 'closed' ? 'Тикет закрыт. Создайте новый обращение.' : 'Напишите сообщение... (Enter — отправить)'"
          @keydown.enter.exact.prevent="sendMessage"
        ></textarea>
        <button
          @click="sendMessage"
          :disabled="!newMessage.trim() || isSending || ticket?.status === 'closed'"
          class="soft-button primary send-btn"
        >
          {{ isSending ? '...' : '▶ Отправить' }}
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { mcAsset } from '../utils/assets'
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToast } from '../utils/toast'
import api from '../api/axios'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { showToast } = useToast()

const ticketId = route.params.id
const ticket = ref(null)
const messages = ref([])
const newMessage = ref('')
const loading = ref(true)
const isSending = ref(false)
const isClosing = ref(false)
const chatBox = ref(null)
let pollingInterval = null

const scrollToBottom = async () => {
  await nextTick()
  if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight
}

const fetchMessages = async () => {
  try {
    const r = await api.get(`/tickets/${ticketId}/messages`)
    messages.value = r.data
    await scrollToBottom()
  } catch (e) {
    console.error('Ошибка загрузки сообщений', e)
  } finally {
    loading.value = false
  }
}

const fetchTicket = async () => {
  try {
    // Получаем список тикетов и ищем нужный
    const r = await api.get('/tickets')
    ticket.value = r.data.find(t => t.id == ticketId) ?? null
  } catch {}
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || isSending.value) return
  isSending.value = true
  const text = newMessage.value
  newMessage.value = ''
  try {
    await api.post(`/tickets/${ticketId}/reply`, { message: text })
    await fetchMessages()
  } catch (e) {
    newMessage.value = text
    showToast(e.response?.data?.message || 'Ошибка отправки', 'error')
  } finally {
    isSending.value = false
  }
}

const closeTicket = async () => {
  isClosing.value = true
  try {
    await api.patch(`/tickets/${ticketId}/close`)
    showToast('Тикет закрыт', 'success')
    ticket.value = { ...ticket.value, status: 'closed' }
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка закрытия тикета', 'error')
  } finally { isClosing.value = false }
}

onMounted(() => {
  fetchTicket()
  fetchMessages()
  // Поллинг каждые 5 секунд
  pollingInterval = setInterval(fetchMessages, 5000)
})

onUnmounted(() => clearInterval(pollingInterval))
</script>

<style scoped>
.ticket-page { padding: 40px 0 80px; max-width: 860px; margin: 0 auto; }

/* Хлебные крошки */
.breadcrumbs { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; font-size: 14px; }
.bc-link { color: var(--mc-diamond); font-weight: 600; transition: 0.2s; }
.bc-link:hover { opacity: 0.8; }
.bc-sep { color: var(--text-muted); }
.bc-cur { color: var(--text-muted); }
.ticket-status-badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-left: 8px; }
.ticket-status-badge.open { background: rgba(0,230,118,0.1); color: var(--mc-emerald); }
.ticket-status-badge.closed { background: rgba(139,155,180,0.15); color: var(--text-muted); }

/* Заголовок тикета */
.ticket-header { display: flex; justify-content: space-between; align-items: center; padding: 24px 30px; margin-bottom: 20px; }
.ticket-subject-title { margin: 0 0 6px; font-size: 22px; font-weight: 700; }
.ticket-meta-line { margin: 0; font-size: 14px; color: var(--text-muted); }
.close-btn { height: 42px; padding: 0 20px; font-size: 13px; }

/* Чат */
.chat-wrapper { overflow: hidden; display: flex; flex-direction: column; }
.chat-box {
  height: 500px; overflow-y: auto; padding: 24px;
  display: flex; flex-direction: column; gap: 20px;
  scroll-behavior: smooth;
}
.chat-box::-webkit-scrollbar { width: 4px; }
.chat-box::-webkit-scrollbar-track { background: transparent; }
.chat-box::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

/* Пустое/загрузка */
.chat-empty { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); font-size: 15px; }
.loading-dots { display: flex; gap: 8px; }
.loading-dots span { width: 8px; height: 8px; background: var(--mc-diamond); border-radius: 50%; animation: dot-pulse 1.2s infinite; }
.loading-dots span:nth-child(2) { animation-delay: 0.2s; }
.loading-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes dot-pulse { 0%,80%,100% { opacity: 0.2; transform: scale(0.8); } 40% { opacity: 1; transform: scale(1); } }

/* Сообщения */
.message-row { display: flex; gap: 14px; align-items: flex-start; }
.message-row.from-me { flex-direction: row-reverse; }

.msg-avatar { flex-shrink: 0; }
.avatar-img { width: 36px; height: 36px; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5)); }

.msg-bubble {
  max-width: 72%;
  padding: 12px 16px;
  border-radius: 16px;
  background: rgba(0,0,0,0.25);
  border: 1px solid rgba(255,255,255,0.07);
}
.from-me .msg-bubble {
  background: rgba(0,230,118,0.1);
  border-color: rgba(0,230,118,0.2);
}
.from-admin .msg-bubble {
  background: rgba(0,229,255,0.07);
  border-color: rgba(0,229,255,0.15);
}

.msg-meta { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 6px; }
.msg-author { font-size: 12px; font-weight: 700; color: var(--mc-diamond); }
.from-me .msg-author { color: var(--mc-emerald); }
.msg-time { font-size: 11px; color: var(--text-muted); }
.msg-body { font-size: 15px; line-height: 1.5; word-break: break-word; }

/* Форма */
.chat-footer {
  display: flex; gap: 12px; padding: 16px 20px;
  border-top: 1px solid rgba(255,255,255,0.08);
  background: rgba(0,0,0,0.15);
}
.chat-footer.disabled { opacity: 0.5; }
.chat-input {
  flex: 1; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-md); color: var(--text-main); font-size: 15px;
  padding: 12px 16px; resize: none; font-family: var(--font-main);
  transition: border-color 0.2s; line-height: 1.5;
}
.chat-input:focus { outline: none; border-color: var(--mc-diamond); }
.chat-input::placeholder { color: var(--text-muted); }
.send-btn { height: auto; padding: 0 24px; align-self: stretch; flex-shrink: 0; }

@media (max-width: 768px) {
  .ticket-header { flex-direction: column; align-items: flex-start; gap: 16px; }
  .chat-box { height: 380px; }
  .msg-bubble { max-width: 85%; }
}
</style>
