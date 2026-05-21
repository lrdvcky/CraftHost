<template>
  <div class="console-page container">

    <!-- Хлебные крошки -->
    <div class="breadcrumbs">
      <router-link to="/dashboard" class="bc-link">← Назад в кабинет</router-link>
      <span class="bc-sep">/</span>
      <span class="bc-cur">Консоль сервера #{{ serverId }}</span>
    </div>

    <!-- Загрузка / ошибка -->
    <div v-if="loading" class="glass loading-box">Загрузка...</div>
    <div v-else-if="error" class="glass loading-box error-text">{{ error }}</div>

    <template v-else>
      <!-- Инфо-шапка -->
      <div class="console-header glass">
        <div class="ch-left">
          <h1 class="ch-name">{{ server.tariff?.name || 'Сервер' }} <span class="ch-id">#{{ server.id }}</span></h1>
          <div class="ch-meta">
            <span class="ch-status" :class="serverState">{{ stateLabel }}</span>
            <span v-if="server.address" class="ch-addr">{{ server.address }}</span>
            <span class="ch-version">{{ server.mc_version }}</span>
          </div>
        </div>
        <div class="ch-right">
          <button @click="power('start')" :disabled="powering" class="soft-button primary small">▶ Start</button>
          <button @click="power('restart')" :disabled="powering" class="soft-button secondary small">↺ Restart</button>
          <button @click="power('stop')" :disabled="powering" class="soft-button secondary small stop-btn">■ Stop</button>
        </div>
      </div>

      <div class="console-grid">
        <!-- Терминал -->
        <div class="glass terminal-card">
          <div class="term-bar">
            <div class="win-dot red"></div>
            <div class="win-dot yellow"></div>
            <div class="win-dot green"></div>
            <span class="win-title">Console — {{ server.address || 'server' }}</span>
          </div>
          <div ref="termBox" class="term-body">
            <div v-for="(line, i) in logLines" :key="i" class="term-line">
              <span class="tl-time">{{ line.time }}</span>
              <span class="tl-level" :class="levelClass(line.level)">{{ line.level }}</span>
              <span class="tl-text">{{ line.text }}</span>
            </div>
            <div v-if="!logLines.length" class="term-empty">Ожидание логов сервера...</div>
            <div class="term-cursor">_</div>
          </div>
          <div class="term-input-row">
            <span class="term-prompt">&gt;</span>
            <input
              v-model="commandInput"
              @keydown.enter="sendCommand"
              class="term-input"
              placeholder="Введите команду... (op, whitelist, say, stop и т.д.)"
              :disabled="server.status !== 'active'"
            >
            <button
              @click="sendCommand"
              :disabled="!commandInput.trim() || sendingCmd || server.status !== 'active'"
              class="term-send"
            >
              {{ sendingCmd ? '...' : 'Enter' }}
            </button>
          </div>
        </div>

        <!-- Правая панель: ресурсы -->
        <div class="console-sidebar">
          <!-- Мониторинг ресурсов -->
          <div class="glass resource-card">
            <h3 class="rc-title">Ресурсы</h3>

            <div class="rc-item">
              <div class="rc-top">
                <span class="rc-label">CPU</span>
                <span class="rc-val">{{ resources.cpu }}%</span>
              </div>
              <div class="rc-bar"><div class="rc-fill cpu" :style="{ width: resources.cpu + '%' }"></div></div>
            </div>

            <div class="rc-item">
              <div class="rc-top">
                <span class="rc-label">RAM</span>
                <span class="rc-val">{{ resources.ramUsed }} / {{ resources.ramTotal }} MB</span>
              </div>
              <div class="rc-bar"><div class="rc-fill ram" :style="{ width: resources.ramPct + '%' }"></div></div>
            </div>

            <div class="rc-item">
              <div class="rc-top">
                <span class="rc-label">Диск</span>
                <span class="rc-val">{{ resources.diskUsed }} MB</span>
              </div>
              <div class="rc-bar"><div class="rc-fill disk" :style="{ width: Math.min(resources.diskPct, 100) + '%' }"></div></div>
            </div>
          </div>

          <!-- Информация о сервере -->
          <div class="glass resource-card">
            <h3 class="rc-title">Сервер</h3>
            <div class="rc-info-row"><span>Тариф</span><strong>{{ server.tariff?.name }}</strong></div>
            <div class="rc-info-row"><span>Ядро</span><strong>{{ server.mc_version }}</strong></div>
            <div class="rc-info-row"><span>RAM</span><strong>{{ server.tariff ? (server.tariff.ram_mb / 1024) : '?' }} GB</strong></div>
            <div class="rc-info-row"><span>CPU</span><strong>{{ server.tariff?.cpu_percent }}%</strong></div>
            <div class="rc-info-row"><span>Диск</span><strong>{{ server.tariff ? (server.tariff.disk_mb / 1024) : '?' }} GB</strong></div>
            <div class="rc-info-row">
              <span>Оплачен до</span>
              <strong>{{ server.expires_at ? new Date(server.expires_at).toLocaleDateString('ru-RU') : '—' }}</strong>
            </div>
          </div>

          <!-- Быстрые команды -->
          <div class="glass resource-card">
            <h3 class="rc-title">Быстрые команды</h3>
            <div class="quick-btns">
              <button v-for="cmd in quickCmds" :key="cmd" class="quick-btn" @click="runQuick(cmd)" :disabled="server.status !== 'active'">
                {{ cmd }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/axios'
import { useToast } from '../utils/toast'

const route = useRoute()
const { showToast } = useToast()

const serverId = route.params.id
const server = ref({})
const loading = ref(true)
const error = ref('')
const logLines = ref([])
const commandInput = ref('')
const sendingCmd = ref(false)
const powering = ref(false)
const termBox = ref(null)
let pollHandle = null

const serverState = ref('unknown')

const resources = ref({
  cpu: 0,
  ramUsed: 0,
  ramTotal: 0,
  ramPct: 0,
  diskUsed: 0,
  diskPct: 0,
})

const stateLabel = ref('—')
const stateLabelMap = {
  running: 'Запущен',
  starting: 'Запускается',
  stopping: 'Останавливается',
  offline: 'Выключен',
  unknown: 'Неизвестно',
}

const quickCmds = ['list', 'tps', 'say Привет!', 'whitelist list', 'save-all', 'gc']

const scrollDown = async () => {
  await nextTick()
  if (termBox.value) termBox.value.scrollTop = termBox.value.scrollHeight
}

const levelClass = (level) => ({
  INFO: 'info',
  DONE: 'done',
  WARN: 'warn',
  ERROR: 'err',
}[level] || 'info')

const fetchServer = async () => {
  try {
    const r = await api.get(`/servers/${serverId}`)
    server.value = r.data
  } catch (e) {
    error.value = e.response?.status === 404 ? 'Сервер не найден' : 'Ошибка загрузки'
  } finally { loading.value = false }
}

const fetchConsole = async () => {
  if (!server.value.id || server.value.status !== 'active') return
  try {
    const r = await api.get(`/servers/${serverId}/console`)
    serverState.value = r.data.state || 'unknown'
    stateLabel.value = stateLabelMap[serverState.value] || serverState.value

    if (r.data.resources) {
      const res = r.data.resources
      const ramMB = Math.round((res.memory_bytes || 0) / 1024 / 1024)
      const diskMB = Math.round((res.disk_bytes || 0) / 1024 / 1024)
      const ramTotal = server.value.tariff?.ram_mb || 4096
      resources.value = {
        cpu: Math.round(res.cpu_absolute || 0),
        ramUsed: ramMB,
        ramTotal,
        ramPct: Math.round((ramMB / ramTotal) * 100),
        diskUsed: diskMB,
        diskPct: Math.round((diskMB / (server.value.tariff?.disk_mb || 20480)) * 100),
      }
    }

    // Подмешиваем новые строки лога (stub генерирует заново — берём все).
    if (r.data.logs?.length && logLines.value.length === 0) {
      logLines.value = r.data.logs
      await scrollDown()
    }
  } catch {}
}

const sendCommand = async () => {
  const cmd = commandInput.value.trim()
  if (!cmd || sendingCmd.value) return
  sendingCmd.value = true
  commandInput.value = ''

  // Эхо команды в консоль.
  const now = new Date()
  const timeStr = now.toTimeString().slice(0, 8)
  logLines.value.push({ time: timeStr, level: 'CMD', text: `> ${cmd}` })
  await scrollDown()

  try {
    await api.post(`/servers/${serverId}/command`, { command: cmd })
    logLines.value.push({ time: timeStr, level: 'INFO', text: `Команда "${cmd}" отправлена` })
  } catch (e) {
    logLines.value.push({ time: timeStr, level: 'ERROR', text: e.response?.data?.error || 'Ошибка отправки' })
  } finally {
    sendingCmd.value = false
    await scrollDown()
  }
}

const runQuick = (cmd) => {
  commandInput.value = cmd
  sendCommand()
}

const power = async (signal) => {
  powering.value = true
  try {
    await api.post(`/servers/${serverId}/power`, { signal })
    showToast(`Команда ${signal} отправлена`, 'success')
    const now = new Date().toTimeString().slice(0, 8)
    logLines.value.push({ time: now, level: 'INFO', text: `Power signal: ${signal}` })
    await scrollDown()
  } catch (e) {
    showToast(e.response?.data?.error || 'Ошибка', 'error')
  } finally { powering.value = false }
}

onMounted(async () => {
  await fetchServer()
  if (!error.value) {
    await fetchConsole()
    pollHandle = setInterval(fetchConsole, 8000)
  }
})

onUnmounted(() => { if (pollHandle) clearInterval(pollHandle) })
</script>

<style scoped>
.console-page { padding: 40px 0 80px; }

/* Хлебные крошки */
.breadcrumbs { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; font-size: 14px; }
.bc-link { color: var(--mc-diamond); font-weight: 600; transition: 0.2s; }
.bc-link:hover { opacity: 0.8; }
.bc-sep { color: var(--text-muted); }
.bc-cur { color: var(--text-muted); }

.loading-box { padding: 48px; text-align: center; color: var(--text-muted); }
.error-text { color: #ff5555; }

/* Header */
.console-header { display: flex; justify-content: space-between; align-items: center; padding: 24px 28px; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
.ch-name { margin: 0; font-size: 22px; font-weight: 700; }
.ch-id { color: var(--text-muted); font-weight: 500; font-size: 16px; }
.ch-meta { display: flex; align-items: center; gap: 12px; margin-top: 8px; flex-wrap: wrap; }
.ch-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
.ch-status.running { background: rgba(0,230,118,0.1); color: var(--mc-emerald); }
.ch-status.offline, .ch-status.unknown { background: rgba(139,155,180,0.15); color: var(--text-muted); }
.ch-status.starting, .ch-status.stopping { background: rgba(255,196,0,0.1); color: var(--mc-gold); }
.ch-addr { font-size: 14px; color: var(--mc-diamond); font-family: monospace; }
.ch-version { font-size: 13px; color: var(--text-muted); }
.ch-right { display: flex; gap: 10px; }

/* Grid */
.console-grid { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }

/* Терминал */
.terminal-card { overflow: hidden; display: flex; flex-direction: column; }
.term-bar {
  display: flex; align-items: center; gap: 6px;
  padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.07);
}
.win-dot { width: 12px; height: 12px; border-radius: 50%; }
.win-dot.red { background: #ff5f57; }
.win-dot.yellow { background: #ffbd2e; }
.win-dot.green { background: #28c840; }
.win-title { font-size: 12px; color: var(--text-muted); margin-left: 8px; }

.term-body {
  min-height: 420px; max-height: 520px; overflow-y: auto; padding: 16px 18px;
  background: rgba(0,0,0,0.2); font-family: 'Courier New', monospace; font-size: 13px;
  display: flex; flex-direction: column; gap: 4px; scroll-behavior: smooth;
}
.term-body::-webkit-scrollbar { width: 4px; }
.term-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

.term-line { display: flex; gap: 8px; line-height: 1.6; }
.tl-time { color: #555; flex-shrink: 0; }
.tl-level { font-weight: 700; flex-shrink: 0; min-width: 42px; }
.tl-level.info { color: var(--mc-diamond); }
.tl-level.done { color: var(--mc-emerald); }
.tl-level.warn { color: var(--mc-gold); }
.tl-level.err { color: #ff5555; }
.tl-text { color: var(--text-main); word-break: break-all; }
.term-empty { color: var(--text-muted); padding: 40px 0; text-align: center; }
.term-cursor { color: var(--mc-emerald); animation: blink 1s step-end infinite; margin-top: 4px; }
@keyframes blink { 0%,100% { opacity:1 } 50% { opacity:0 } }

.term-input-row {
  display: flex; align-items: center; gap: 8px;
  padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.07);
  background: rgba(0,0,0,0.15);
}
.term-prompt { color: var(--mc-emerald); font-family: 'Courier New', monospace; font-size: 16px; font-weight: 700; }
.term-input {
  flex: 1; background: transparent; border: none; color: var(--text-main);
  font-family: 'Courier New', monospace; font-size: 14px; padding: 6px 0;
}
.term-input:focus { outline: none; }
.term-input::placeholder { color: rgba(139,155,180,0.5); }
.term-send {
  padding: 6px 16px; border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-md);
  background: rgba(255,255,255,0.05); color: var(--mc-emerald); font-size: 13px; font-weight: 700;
  cursor: pointer; transition: 0.2s; font-family: var(--font-main);
}
.term-send:hover:not(:disabled) { background: rgba(0,230,118,0.12); border-color: var(--mc-emerald); }
.term-send:disabled { opacity: 0.35; cursor: default; }

/* Sidebar */
.console-sidebar { display: flex; flex-direction: column; gap: 20px; }
.resource-card { padding: 22px 20px; }
.rc-title { margin: 0 0 18px; font-size: 16px; font-weight: 700; }

.rc-item { margin-bottom: 16px; }
.rc-item:last-child { margin-bottom: 0; }
.rc-top { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
.rc-label { color: var(--text-muted); }
.rc-val { color: var(--text-main); font-weight: 600; }
.rc-bar { height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden; }
.rc-fill { height: 100%; border-radius: 3px; transition: width 0.8s ease; }
.rc-fill.cpu { background: var(--mc-diamond); }
.rc-fill.ram { background: var(--mc-emerald); }
.rc-fill.disk { background: var(--mc-gold); }

.rc-info-row { display: flex; justify-content: space-between; font-size: 14px; padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.rc-info-row:last-child { border-bottom: none; }
.rc-info-row span { color: var(--text-muted); }
.rc-info-row strong { color: var(--text-main); font-weight: 600; }

.quick-btns { display: flex; flex-wrap: wrap; gap: 8px; }
.quick-btn {
  padding: 7px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);
  background: rgba(0,0,0,0.2); color: var(--text-muted); font-size: 13px; font-family: 'Courier New', monospace;
  cursor: pointer; transition: 0.2s;
}
.quick-btn:hover:not(:disabled) { color: var(--mc-emerald); border-color: var(--mc-emerald); background: rgba(0,230,118,0.08); }
.quick-btn:disabled { opacity: 0.35; cursor: default; }

/* Кнопки из dashboard */
.small { height: 38px; padding: 0 16px; font-size: 13px; }
.stop-btn { color: #ff5555; }
.stop-btn:hover { border-color: #ff5555; color: #ff5555; }

@media (max-width: 960px) {
  .console-grid { grid-template-columns: 1fr; }
  .console-header { flex-direction: column; align-items: flex-start; }
}
</style>
