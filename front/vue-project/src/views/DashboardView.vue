<template>
  <div class="dashboard-page container">

    <!-- ШАПКА -->
    <div class="dash-head">
      <div>
        <h1 class="section-title">Личный кабинет</h1>
        <p class="section-subtitle">Управляйте серверами, тикетами и балансом</p>
      </div>

      <div class="balance-section">
        <div class="balance-card glass">
          <div class="b-label">Баланс аккаунта</div>
          <div class="b-val">
            <img :src="mcAsset('Emerald_JE3_BE3.png')" class="pixel-img b-icon" alt="Emerald">
            {{ authStore.user?.balance ?? '0.00' }} ₽
          </div>
        </div>
        <button @click="showTopupModal = true" class="soft-button primary topup-btn">
          Пополнить
        </button>
      </div>
    </div>

    <!-- ВКЛАДКИ -->
    <div class="tabs glass">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="tab-btn"
        :class="{ active: activeTab === tab.key }"
        @click="activeTab = tab.key"
      >
        <span class="tab-icon">{{ tab.icon }}</span>
        {{ tab.label }}
        <span v-if="tab.key === 'tickets' && openTicketsCount" class="tab-badge">{{ openTicketsCount }}</span>
      </button>
    </div>

    <!-- ============ ВКЛАДКА: СЕРВЕРЫ ============ -->
    <div v-if="activeTab === 'servers'">
      <div v-if="loadingServers" class="soft-block" style="text-align:center;color:var(--text-muted);">
        Загрузка серверов...
      </div>
      <div v-else-if="servers.length" class="servers-grid">
        <div v-for="server in servers" :key="server.id" class="glass server-card">
          <div class="s-top">
            <div>
              <div class="s-id">ID: #{{ server.id }}</div>
              <h3 class="s-name">{{ server.tariff?.name }}</h3>
            </div>
            <div class="s-status" :class="server.status">{{ statusLabel(server.status) }}</div>
          </div>
          <div class="s-info">
            <div><span>Ядро:</span> {{ server.mc_version }}</div>
            <div><span>Оплачен до:</span> {{ new Date(server.expires_at).toLocaleDateString('ru-RU') }}</div>
            <div><span>RAM:</span> {{ server.tariff?.ram_mb / 1024 }}GB</div>
            <div v-if="server.address">
              <span>Адрес:</span>
              <code class="s-address">{{ server.address }}</code>
            </div>
            <div v-else-if="isPending(server.status)" class="s-provisioning">
              <span class="spinner-dot"></span>
              Создаём сервер... обычно занимает 1-2 минуты
            </div>
            <div v-else-if="server.status === 'error'" class="s-error">
              Не удалось создать сервер. Обратитесь в поддержку.
            </div>
          </div>
          <div class="s-actions" v-if="server.status === 'active'">
            <button @click="powerSignal(server.id, 'start')" class="soft-button primary small">▶ Start</button>
            <button @click="powerSignal(server.id, 'stop')" class="soft-button secondary small stop-btn">■ Stop</button>
            <button @click="powerSignal(server.id, 'restart')" class="soft-button secondary small">↺ Restart</button>
          </div>
          <!-- Консоль + Продлить -->
          <div class="s-extra-actions">
            <router-link v-if="server.status === 'active'" :to="`/servers/${server.id}/console`" class="soft-button secondary small console-link">
              ⌨ Консоль
            </router-link>
            <button v-if="server.status !== 'deleted'" class="soft-button secondary small renew-link" @click="openRenewModal(server)">
              ↻ Продлить
            </button>
          </div>

          <!-- Модалка продления (инлайн внутри карточки) -->
          <div v-if="renewTarget?.id === server.id" class="renew-panel">
            <div class="renew-row">
              <label class="form-label" style="flex:1;">
                <span>Продлить на (дней)</span>
                <input v-model.number="renewDays" type="number" min="1" max="365" class="input-soft" style="height:42px;">
              </label>
              <div class="renew-cost">
                <span class="rc-price">{{ renewCost }} ₽</span>
                <span class="rc-hint">{{ server.tariff?.price_day }} ₽/день</span>
              </div>
            </div>
            <div class="renew-btns">
              <button @click="submitRenew(server)" :disabled="isRenewing" class="soft-button primary small" style="flex:1;">
                {{ isRenewing ? 'Продление...' : 'Оплатить и продлить' }}
              </button>
              <button @click="renewTarget = null" class="soft-button secondary small">Отмена</button>
            </div>
            <div v-if="renewError" class="renew-error">{{ renewError }}</div>
          </div>
          <!-- Бэкапы -->
          <div class="backup-section">
            <div class="backup-header" @click="toggleBackups(server.id)">
              <span>Бэкапы</span>
              <span class="expand-arrow">{{ expandedBackups[server.id] ? '▲' : '▼' }}</span>
            </div>
            <div v-if="expandedBackups[server.id]" class="backup-body">
              <div v-if="backupsLoading[server.id]" class="backup-empty">Загрузка...</div>
              <div v-else-if="!backups[server.id]?.length" class="backup-empty">Бэкапов нет</div>
              <div v-else class="backup-list">
                <div v-for="b in backups[server.id]" :key="b.id" class="backup-item">
                  <span>{{ new Date(b.created_at).toLocaleString('ru-RU') }}</span>
                  <span class="backup-size">{{ (b.size_bytes / 1024 / 1024).toFixed(1) }} MB</span>
                </div>
              </div>
              <button @click="createBackup(server.id)" class="soft-button secondary small" style="margin-top:12px;width:100%;">
                + Создать бэкап
              </button>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="glass empty-state">
        <img :src="mcAsset('150px-Structure_Void_(item)_JE2.png')" class="pixel-img empty-img" alt="Void">
        <h2>У вас пока нет серверов</h2>
        <p>Скрафтите свой первый сервер прямо сейчас.</p>
        <router-link to="/order" class="soft-button primary mt-4">Арендовать сервер</router-link>
      </div>
    </div>

    <!-- ============ ВКЛАДКА: ТИКЕТЫ ============ -->
    <div v-if="activeTab === 'tickets'">
      <div class="tickets-layout">
        <!-- Форма создания тикета -->
        <div class="glass ticket-create-form">
          <h3 class="form-title">Новый тикет</h3>
          <div class="field-col">
            <label class="form-label">
              <span>Тема обращения</span>
              <input v-model="newTicket.subject" type="text" class="input-soft" placeholder="Опишите проблему кратко">
            </label>
            <label class="form-label">
              <span>Сообщение</span>
              <textarea v-model="newTicket.message" class="input-soft textarea-soft" rows="4" placeholder="Подробно опишите вашу проблему..."></textarea>
            </label>
            <button @click="createTicket" :disabled="isCreatingTicket || !newTicket.subject || !newTicket.message" class="soft-button primary w-full">
              {{ isCreatingTicket ? 'Отправка...' : 'Создать тикет' }}
            </button>
          </div>
        </div>

        <!-- Список тикетов -->
        <div class="tickets-list-section">
          <div v-if="loadingTickets" style="text-align:center;color:var(--text-muted);padding:40px;">Загрузка...</div>
          <div v-else-if="!tickets.length" class="glass empty-state" style="padding:40px;">
            <p style="color:var(--text-muted);">Тикетов нет. Создайте первый обращение слева.</p>
          </div>
          <div v-else class="tickets-list">
            <div
              v-for="ticket in tickets"
              :key="ticket.id"
              class="glass ticket-item"
              @click="goToTicket(ticket.id)"
            >
              <div class="ticket-meta">
                <span class="ticket-id">#{{ ticket.id }}</span>
                <span class="ticket-status" :class="ticket.status">{{ ticket.status === 'open' ? 'Открыт' : 'Закрыт' }}</span>
              </div>
              <div class="ticket-subject">{{ ticket.subject }}</div>
              <div class="ticket-date">{{ new Date(ticket.created_at).toLocaleDateString('ru-RU') }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ ВКЛАДКА: РЕФЕРАЛЬНАЯ ПРОГРАММА ============ -->
    <div v-if="activeTab === 'referral'">
      <div class="referral-layout">
        <!-- Реферальная ссылка -->
        <div class="glass referral-card">
          <h3 class="form-title">Ваша реферальная ссылка</h3>
          <p style="color:var(--text-muted);font-size:14px;margin-bottom:20px;">
            Приглашайте друзей и получайте <strong style="color:var(--mc-emerald);">10%</strong> от их пополнений на баланс.
          </p>
          <div v-if="loadingReferral" style="color:var(--text-muted);">Загрузка...</div>
          <div v-else>
            <div v-if="referralData?.code" class="ref-link-box">
              <code class="ref-link-text">{{ refLink }}</code>
              <button @click="copyRefLink" class="soft-button secondary copy-btn">
                {{ copied ? 'Скопировано' : 'Копировать' }}
              </button>
            </div>
            <div v-else>
              <button @click="generateRefCode" :disabled="isGenerating" class="soft-button primary">
                {{ isGenerating ? 'Генерация...' : 'Получить реферальный код' }}
              </button>
            </div>

            <!-- Статистика -->
            <div v-if="referralData" class="ref-stats">
              <div class="ref-stat-card glass">
                <div class="rs-label">Приглашено друзей</div>
                <div class="rs-val emerald">{{ referralData.referrals_count ?? 0 }}</div>
              </div>
              <div class="ref-stat-card glass">
                <div class="rs-label">Заработано всего</div>
                <div class="rs-val gold">{{ referralData.total_earned ?? '0.00' }} ₽</div>
              </div>
            </div>

            <!-- История комиссий -->
            <div v-if="referralData?.commissions?.length" class="commissions-list">
              <h4 style="font-size:16px;margin:24px 0 12px;color:var(--text-muted);">История начислений</h4>
              <div v-for="c in referralData.commissions" :key="c.id" class="commission-item">
                <span>Начисление от реферала</span>
                <span style="color:var(--mc-emerald);font-weight:700;">+{{ c.amount }} ₽</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Инструкция -->
        <div class="glass ref-howto">
          <h3 class="form-title">Как это работает</h3>
          <div class="howto-steps">
            <div class="howto-step">
              <div class="step-num">1</div>
              <div>
                <strong>Получите код</strong>
                <p>Нажмите кнопку генерации реферального кода</p>
              </div>
            </div>
            <div class="howto-step">
              <div class="step-num">2</div>
              <div>
                <strong>Поделитесь ссылкой</strong>
                <p>Отправьте ссылку другу или в Discord сообществе</p>
              </div>
            </div>
            <div class="howto-step">
              <div class="step-num">3</div>
              <div>
                <strong>Получайте ₽</strong>
                <p>10% от каждого пополнения вашего реферала начисляется вам автоматически</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ ВКЛАДКА: ИСТОРИЯ ПЛАТЕЖЕЙ ============ -->
    <div v-if="activeTab === 'payments'">
      <div class="glass" style="padding:32px;">
        <h3 class="form-title">История платежей</h3>
        <div v-if="loadingPayments" style="color:var(--text-muted);text-align:center;padding:40px;">Загрузка...</div>
        <div v-else-if="!payments.length" style="color:var(--text-muted);text-align:center;padding:40px;">
          История платежей пуста
        </div>
        <div v-else class="payments-table">
          <div class="pt-header">
            <span>Дата</span>
            <span>Тип</span>
            <span>Сумма</span>
            <span>Статус</span>
          </div>
          <div v-for="p in payments" :key="p.id" class="pt-row">
            <span>{{ new Date(p.created_at).toLocaleDateString('ru-RU') }}</span>
            <span style="color:var(--text-muted);font-size:14px;">{{ p.provider === 'manual' ? 'Ручное пополнение' : p.provider }}</span>
            <span class="pt-amount" :class="p.amount > 0 ? 'positive' : 'negative'">
              +{{ p.amount }} ₽
            </span>
            <span class="payment-status" :class="p.status">{{ paymentStatusLabel(p.status) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ МОДАЛ: ПОПОЛНЕНИЕ ============ -->
    <div v-if="showTopupModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content glass">
        <h3 class="modal-title">Пополнение баланса</h3>
        <p class="modal-desc">Введите сумму пополнения. Минимум 50 ₽.</p>
        <div class="topup-input-wrapper">
          <img :src="mcAsset('Emerald_JE3_BE3.png')" class="pixel-img input-icon" alt="Emerald">
          <input v-model="topupAmount" type="number" min="50" class="input-soft topup-input" placeholder="100">
          <span class="currency-badge">₽</span>
        </div>

        <div v-if="payMethods.length > 1" class="pay-methods">
          <label v-for="m in payMethods" :key="m.name" class="pay-method" :class="{ active: payMethod === m.name }">
            <input type="radio" :value="m.name" v-model="payMethod">
            <span>{{ m.label }}</span>
          </label>
        </div>

        <div v-if="topupError" class="error-box mt-4">{{ topupError }}</div>
        <div class="modal-actions">
          <button @click="closeModal" class="soft-button secondary">Отмена</button>
          <button @click="handleTopup" :disabled="isToppingUp" class="soft-button primary">
            {{ isToppingUp ? 'Обработка...' : 'Оплатить' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ============ ВКЛАДКА: АДМИНИСТРАТОР ============ -->
    <div v-if="activeTab === 'admin' && isAdmin">

      <!-- Под-вкладки админки -->
      <div class="tabs glass" style="margin-bottom: 20px;">
        <button
          v-for="t in adminSubTabs"
          :key="t.key"
          class="tab-btn"
          :class="{ active: adminTab === t.key }"
          @click="switchAdminTab(t.key)"
        >
          {{ t.label }}
        </button>
      </div>

      <!-- --- Обзор: статистика + пользователи --- -->
      <div v-if="adminTab === 'overview'">
        <div class="admin-stats-grid" v-if="adminStats">
          <div class="glass admin-stat-card">
            <div class="as-label">Пользователей</div>
            <div class="as-val diamond">{{ adminStats.total_users }}</div>
          </div>
          <div class="glass admin-stat-card">
            <div class="as-label">Активных серверов</div>
            <div class="as-val emerald">{{ adminStats.active_servers }}</div>
          </div>
          <div class="glass admin-stat-card">
            <div class="as-label">Выручка</div>
            <div class="as-val gold">{{ adminStats.total_revenue }} ₽</div>
          </div>
          <div class="glass admin-stat-card">
            <div class="as-label">Открытых тикетов</div>
            <div class="as-val">{{ adminStats.open_tickets }}</div>
          </div>
          <div class="glass admin-stat-card" v-if="adminStats.pending_servers !== undefined">
            <div class="as-label">В очереди</div>
            <div class="as-val" style="color:#55aaff;">{{ adminStats.pending_servers }}</div>
          </div>
          <div class="glass admin-stat-card" v-if="adminStats.error_servers !== undefined">
            <div class="as-label">С ошибкой</div>
            <div class="as-val" style="color:#ff5555;">{{ adminStats.error_servers }}</div>
          </div>
        </div>
        <div v-else-if="loadingAdminStats" style="color:var(--text-muted);padding:20px 0;">Загрузка статистики...</div>

        <!-- Список пользователей -->
        <div class="glass" style="padding:32px;margin-top:24px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h3 class="form-title" style="margin:0;">Пользователи</h3>
            <button @click="fetchAdminUsers()" class="soft-button secondary" style="height:38px;padding:0 16px;font-size:13px;">Обновить</button>
          </div>

          <div v-if="loadingAdminUsers" style="color:var(--text-muted);text-align:center;padding:40px;">Загрузка...</div>
          <div v-else-if="adminUsers.length">
            <div class="admin-users-header">
              <span>ID</span><span>Email</span><span>Роль</span><span>Баланс</span><span>Серверов</span><span>Действия</span>
            </div>
            <div v-for="u in adminUsers" :key="u.id" class="admin-user-row">
              <span class="au-id">#{{ u.id }}</span>
              <span class="au-email">{{ u.email }}</span>
              <span><span class="au-role" :class="u.role">{{ u.role === 'admin' ? 'Админ' : 'Клиент' }}</span></span>
              <span class="au-balance">{{ u.balance }} ₽</span>
              <span class="au-servers">{{ u.servers_count ?? 0 }}</span>
              <span>
                <button @click="openEditUser(u)" class="soft-button secondary" style="height:34px;padding:0 14px;font-size:12px;">Изменить</button>
              </span>
            </div>
            <div v-if="adminUsersMeta" class="admin-pagination">
              <button :disabled="adminUsersMeta.current_page <= 1" @click="fetchAdminUsers(adminUsersMeta.current_page - 1)" class="soft-button secondary" style="height:36px;padding:0 16px;font-size:13px;">Назад</button>
              <span style="color:var(--text-muted);font-size:14px;">Стр. {{ adminUsersMeta.current_page }} / {{ adminUsersMeta.last_page }}</span>
              <button :disabled="adminUsersMeta.current_page >= adminUsersMeta.last_page" @click="fetchAdminUsers(adminUsersMeta.current_page + 1)" class="soft-button secondary" style="height:36px;padding:0 16px;font-size:13px;">Вперёд</button>
            </div>
          </div>
        </div>
      </div>

      <!-- --- Все серверы --- -->
      <div v-if="adminTab === 'servers'" class="glass" style="padding:32px;">
        <h3 class="form-title" style="margin-top:0;">Все серверы</h3>
        <div v-if="loadingAdminServers" style="color:var(--text-muted);padding:20px 0;">Загрузка...</div>
        <div v-else>
          <div class="admin-table">
            <div class="admin-table-header" style="grid-template-columns: 60px 1fr 1fr 1fr 100px 180px;">
              <span>ID</span><span>Юзер</span><span>Тариф</span><span>Адрес</span><span>Статус</span><span>Действия</span>
            </div>
            <div v-for="s in adminServers" :key="s.id" class="admin-table-row" style="grid-template-columns: 60px 1fr 1fr 1fr 100px 180px;">
              <span>#{{ s.id }}</span>
              <span>{{ s.user?.email }}</span>
              <span>{{ s.tariff?.name }}</span>
              <span style="font-family:monospace;font-size:12px;">{{ s.server_ip ? (s.server_ip + ':' + s.server_port) : '—' }}</span>
              <span><span class="s-status" :class="s.status" style="font-size:10px;padding:3px 8px;">{{ statusLabel(s.status) }}</span></span>
              <span style="display:flex;gap:4px;flex-wrap:wrap;">
                <button @click="openChangeTariff(s)" class="soft-button secondary" style="height:28px;padding:0 10px;font-size:11px;">Тариф</button>
                <button v-if="s.status === 'active'" @click="adminSuspendServer(s.id)" class="soft-button secondary" style="height:28px;padding:0 10px;font-size:11px;">Suspend</button>
                <button v-else-if="s.status === 'suspended'" @click="adminUnsuspendServer(s.id)" class="soft-button primary" style="height:28px;padding:0 10px;font-size:11px;">Unsuspend</button>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- --- Чат поддержки (админ) --- -->
      <div v-if="adminTab === 'tickets'">
        <div class="admin-tickets-layout">
          <!-- Список тикетов -->
          <div class="glass" style="padding:24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
              <h3 class="form-title" style="margin:0;font-size:18px;">Чат поддержки</h3>
              <select v-model="ticketFilter" @change="fetchAdminTickets()" class="input-soft select-soft" style="width:140px;height:34px;font-size:13px;">
                <option value="">Все</option>
                <option value="open">Открытые</option>
                <option value="answered">Отвечены</option>
                <option value="closed">Закрытые</option>
              </select>
            </div>
            <div v-if="loadingAdminTickets" style="color:var(--text-muted);padding:20px 0;">Загрузка...</div>
            <div v-else-if="!adminTickets.length" style="color:var(--text-muted);padding:20px 0;">Нет тикетов</div>
            <div v-else class="ticket-list">
              <div
                v-for="t in adminTickets"
                :key="t.id"
                class="ticket-list-item"
                :class="{ active: activeTicket?.id === t.id }"
                @click="openAdminTicket(t.id)"
              >
                <div style="display:flex;justify-content:space-between;gap:8px;">
                  <strong style="font-size:14px;">#{{ t.id }} {{ t.subject }}</strong>
                  <span class="s-status" :class="ticketStatusClass(t.status)" style="font-size:9px;padding:2px 7px;flex-shrink:0;">{{ ticketStatusLabel(t.status) }}</span>
                </div>
                <div style="color:var(--text-muted);font-size:12px;margin-top:4px;">
                  {{ t.user?.email }} · {{ t.messages_count }} сообщ.
                  <span v-if="t.assigned_admin" class="at-assigned-chip">⚡ {{ t.assigned_admin.email }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Переписка -->
          <div class="glass" style="padding:24px;display:flex;flex-direction:column;">
            <div v-if="!activeTicket" style="color:var(--text-muted);text-align:center;padding:60px 0;">
              Выберите тикет слева, чтобы открыть чат
            </div>
            <template v-else>
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid rgba(255,255,255,0.08);padding-bottom:14px;">
                <div>
                  <h3 style="margin:0;font-size:17px;">#{{ activeTicket.id }} {{ activeTicket.subject }}</h3>
                  <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
                    <span style="color:var(--text-muted);font-size:13px;">{{ activeTicket.user?.email }}</span>
                    <span v-if="activeTicket.assigned_admin" class="at-assigned-badge">
                      Ведёт: {{ activeTicket.assigned_admin.email }}
                    </span>
                  </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                  <!-- Принять / Снять -->
                  <button
                    v-if="!activeTicket.assigned_admin_id"
                    @click="assignTicket(activeTicket.id)"
                    :disabled="isAssigning"
                    class="soft-button primary" style="height:32px;padding:0 12px;font-size:12px;"
                  >
                    {{ isAssigning ? '...' : '⚡ Принять' }}
                  </button>
                  <button
                    v-else
                    @click="releaseTicket(activeTicket.id)"
                    :disabled="isAssigning"
                    class="soft-button secondary" style="height:32px;padding:0 12px;font-size:12px;"
                  >
                    Снять
                  </button>

                  <button v-if="activeTicket.status !== 'closed'" @click="setTicketStatus('closed')" class="soft-button secondary" style="height:32px;padding:0 12px;font-size:12px;">Закрыть</button>
                  <button v-else @click="setTicketStatus('open')" class="soft-button secondary" style="height:32px;padding:0 12px;font-size:12px;">Переоткрыть</button>
                </div>
              </div>

              <div class="ticket-messages" ref="adminMsgBox">
                <div
                  v-for="m in activeTicketMessages"
                  :key="m.id"
                  class="ticket-msg"
                  :class="{ 'from-admin': m.user?.role === 'admin' }"
                >
                  <div class="ticket-msg-author">{{ m.user?.role === 'admin' ? 'Администратор' : m.user?.email }}</div>
                  <div class="ticket-msg-body">{{ m.body }}</div>
                </div>
              </div>

              <div v-if="activeTicket.status !== 'closed'" class="ticket-reply-box">
                <textarea v-model="adminReplyText" class="input-soft" rows="2" placeholder="Ваш ответ..." style="resize:vertical;"></textarea>
                <button @click="sendAdminReply" :disabled="isAdminReplying || !adminReplyText.trim()" class="soft-button primary" style="height:auto;">
                  {{ isAdminReplying ? '...' : 'Ответить' }}
                </button>
              </div>
              <div v-else style="color:var(--text-muted);font-size:13px;text-align:center;padding:12px 0;">Тикет закрыт</div>
            </template>
          </div>
        </div>
      </div>

      <!-- --- Тарифы --- -->
      <div v-if="adminTab === 'tariffs'" class="glass" style="padding:32px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
          <h3 class="form-title" style="margin:0;">Тарифы</h3>
          <button @click="openTariffEdit(null)" class="soft-button primary" style="height:38px;padding:0 16px;font-size:13px;">+ Новый</button>
        </div>
        <div class="admin-table">
          <div class="admin-table-header" style="grid-template-columns: 60px 1fr 90px 90px 90px 90px 100px 100px;">
            <span>ID</span><span>Название</span><span>RAM</span><span>CPU</span><span>Disk</span><span>Slots</span><span>Цена/день</span><span></span>
          </div>
          <div v-for="t in adminTariffs" :key="t.id" class="admin-table-row" style="grid-template-columns: 60px 1fr 90px 90px 90px 90px 100px 100px;">
            <span>#{{ t.id }}</span>
            <span><strong>{{ t.name }}</strong></span>
            <span>{{ (t.ram_mb / 1024).toFixed(1) }}GB</span>
            <span>{{ t.cpu_percent }}%</span>
            <span>{{ (t.disk_mb / 1024).toFixed(1) }}GB</span>
            <span>{{ t.slots }}</span>
            <span style="color:var(--mc-gold);font-weight:700;">{{ t.price_day }} ₽</span>
            <span style="display:flex;gap:6px;">
              <button @click="openTariffEdit(t)" class="soft-button secondary" style="height:28px;padding:0 10px;font-size:11px;">Изм.</button>
              <button @click="deleteTariff(t)" class="soft-button secondary" style="height:28px;padding:0 10px;font-size:11px;color:#ff5555;">Удал.</button>
            </span>
          </div>
        </div>
      </div>

      <!-- --- Промокоды --- -->
      <div v-if="adminTab === 'promo'" class="glass" style="padding:32px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
          <h3 class="form-title" style="margin:0;">Промокоды</h3>
          <button @click="openPromoEdit(null)" class="soft-button primary" style="height:38px;padding:0 16px;font-size:13px;">+ Новый</button>
        </div>
        <div class="admin-table">
          <div class="admin-table-header" style="grid-template-columns: 60px 1fr 100px 100px 100px 1fr 100px;">
            <span>ID</span><span>Код</span><span>Скидка</span><span>Использ.</span><span>Лимит</span><span>Истекает</span><span></span>
          </div>
          <div v-for="p in adminPromos" :key="p.id" class="admin-table-row" style="grid-template-columns: 60px 1fr 100px 100px 100px 1fr 100px;">
            <span>#{{ p.id }}</span>
            <span><code style="background:rgba(0,0,0,0.25);padding:2px 8px;border-radius:6px;">{{ p.code }}</code></span>
            <span>{{ p.discount_pct }}%</span>
            <span>{{ p.used_count }}</span>
            <span>{{ p.max_uses || '∞' }}</span>
            <span style="font-size:13px;color:var(--text-muted);">{{ p.expires_at ? new Date(p.expires_at).toLocaleDateString('ru-RU') : 'без срока' }}</span>
            <span>
              <button @click="deletePromo(p)" class="soft-button secondary" style="height:28px;padding:0 10px;font-size:11px;color:#ff5555;">Удал.</button>
            </span>
          </div>
        </div>
      </div>

      <!-- --- Ноды --- -->
      <div v-if="adminTab === 'nodes'" class="glass" style="padding:32px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
          <h3 class="form-title" style="margin:0;">Ноды (хосты)</h3>
          <button @click="openNodeEdit(null)" class="soft-button primary" style="height:38px;padding:0 16px;font-size:13px;">+ Новая</button>
        </div>
        <div class="admin-table">
          <div class="admin-table-header" style="grid-template-columns: 60px 1fr 1fr 100px 100px 100px 100px;">
            <span>ID</span><span>Имя</span><span>FQDN</span><span>Ptero ID</span><span>Серверов</span><span>Статус</span><span></span>
          </div>
          <div v-for="n in adminNodes" :key="n.id" class="admin-table-row" style="grid-template-columns: 60px 1fr 1fr 100px 100px 100px 100px;">
            <span>#{{ n.id }}</span>
            <span><strong>{{ n.name }}</strong></span>
            <span style="font-family:monospace;font-size:12px;">{{ n.fqdn || '—' }}</span>
            <span>{{ n.ptero_node_id || '—' }}</span>
            <span>{{ n.servers_count ?? 0 }}{{ n.max_servers ? ' / ' + n.max_servers : '' }}</span>
            <span><span class="s-status" :class="n.is_active ? 'active' : 'suspended'" style="font-size:10px;padding:3px 8px;">{{ n.is_active ? 'on' : 'off' }}</span></span>
            <span>
              <button @click="deleteNode(n)" class="soft-button secondary" style="height:28px;padding:0 10px;font-size:11px;color:#ff5555;">Удал.</button>
            </span>
          </div>
        </div>
      </div>

      <!-- --- Версии MC --- -->
      <div v-if="adminTab === 'versions'" class="glass" style="padding:32px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
          <h3 class="form-title" style="margin:0;">Версии Minecraft</h3>
          <button @click="openVersionEdit(null)" class="soft-button primary" style="height:38px;padding:0 16px;font-size:13px;">+ Новая</button>
        </div>
        <div class="admin-table">
          <div class="admin-table-header" style="grid-template-columns: 60px 1fr 1fr 90px 70px 70px 120px;">
            <span>ID</span><span>Slug</span><span>Label</span><span>Type</span><span>Egg</span><span>Активна</span><span></span>
          </div>
          <div v-for="v in adminVersions" :key="v.id" class="admin-table-row" style="grid-template-columns: 60px 1fr 1fr 90px 70px 70px 120px;">
            <span>#{{ v.id }}</span>
            <span><code style="font-size:12px;">{{ v.slug }}</code></span>
            <span>{{ v.label }}</span>
            <span>{{ v.type }}</span>
            <span>{{ v.ptero_egg_id || '—' }}</span>
            <span><span class="s-status" :class="v.is_active ? 'active' : 'suspended'" style="font-size:10px;padding:3px 8px;">{{ v.is_active ? 'on' : 'off' }}</span></span>
            <span style="display:flex;gap:6px;">
              <button @click="openVersionEdit(v)" class="soft-button secondary" style="height:28px;padding:0 10px;font-size:11px;">Изм.</button>
              <button @click="deleteVersion(v)" class="soft-button secondary" style="height:28px;padding:0 10px;font-size:11px;color:#ff5555;">Удал.</button>
            </span>
          </div>
        </div>
      </div>

      <!-- --- Настройки --- -->
      <div v-if="adminTab === 'settings'" class="glass" style="padding:32px;">
        <h3 class="form-title" style="margin-top:0;">Настройки сайта</h3>
        <div v-if="loadingAdminSettings" style="color:var(--text-muted);padding:20px 0;">Загрузка...</div>
        <div v-else style="display:flex;flex-direction:column;gap:16px;">
          <div v-for="s in adminSettings" :key="s.key" class="setting-row">
            <div style="flex:1;">
              <div style="font-weight:600;">{{ s.key }}</div>
              <div style="color:var(--text-muted);font-size:12px;">{{ s.description }}</div>
            </div>
            <input v-if="s.type !== 'bool'" v-model="s.value" :type="s.type === 'int' || s.type === 'float' ? 'number' : 'text'" class="input-soft" style="width:240px;height:40px;">
            <select v-else v-model="s.value" class="input-soft select-soft" style="width:120px;height:40px;">
              <option value="1">true</option>
              <option value="0">false</option>
            </select>
            <button @click="saveSetting(s)" class="soft-button primary" style="height:40px;padding:0 16px;font-size:13px;">Сохранить</button>
          </div>
        </div>
      </div>

      <!-- --- Журнал аудита --- -->
      <div v-if="adminTab === 'audit'" class="glass" style="padding:32px;">
        <h3 class="form-title" style="margin-top:0;">Журнал действий администраторов</h3>
        <div v-if="loadingAuditLog" style="color:var(--text-muted);padding:20px 0;">Загрузка...</div>
        <div v-else>
          <div class="audit-list">
            <div v-for="a in auditLog" :key="a.id" class="audit-entry glass" style="padding:16px 20px;margin-bottom:10px;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div style="display:flex;align-items:center;gap:10px;">
                  <span class="audit-action-badge" :class="auditActionColor(a.action)">{{ auditActionLabel(a.action) }}</span>
                  <span style="font-size:13px;color:var(--text-muted);">{{ a.target_type }} {{ a.target_id ? '#' + a.target_id : '' }}</span>
                </div>
                <span style="font-size:12px;color:var(--text-muted);">{{ new Date(a.created_at).toLocaleString('ru-RU') }}</span>
              </div>
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:13px;">{{ a.admin?.email || 'Система' }}</span>
                <span style="font-size:12px;color:var(--text-muted);">#{{ a.id }}</span>
              </div>
              <!-- Детали (meta) -->
              <div v-if="a.meta && Object.keys(a.meta).length" class="audit-meta">
                <template v-if="a.action === 'user.balance_changed'">
                  <span>{{ a.meta.old_balance }} &#8381;</span>
                  <span style="color:var(--text-muted);margin:0 6px;">&#8594;</span>
                  <span style="font-weight:700;" :style="{ color: a.meta.delta > 0 ? 'var(--mc-emerald)' : '#ff5555' }">{{ a.meta.new_balance }} &#8381;</span>
                  <span style="margin-left:8px;font-size:12px;" :style="{ color: a.meta.delta > 0 ? 'var(--mc-emerald)' : '#ff5555' }">
                    ({{ a.meta.delta > 0 ? '+' : '' }}{{ a.meta.delta }} &#8381;)
                  </span>
                </template>
                <template v-else-if="a.action === 'server.tariff_changed'">
                  <span>{{ a.meta.old_tariff?.name || '—' }}</span>
                  <span style="color:var(--text-muted);margin:0 6px;">&#8594;</span>
                  <span style="color:var(--mc-diamond);font-weight:700;">{{ a.meta.new_tariff?.name }}</span>
                  <span v-if="a.meta.user_id" style="margin-left:10px;font-size:12px;color:var(--text-muted);">user #{{ a.meta.user_id }}</span>
                </template>
                <template v-else-if="a.meta.admin_id">
                  <span style="font-size:12px;color:var(--text-muted);">admin #{{ a.meta.admin_id }}</span>
                </template>
                <template v-else-if="a.meta.status">
                  <span class="s-status" :class="a.meta.status" style="font-size:10px;padding:2px 8px;">{{ a.meta.status }}</span>
                </template>
                <template v-else>
                  <span style="font-size:12px;color:var(--text-muted);font-family:monospace;">{{ JSON.stringify(a.meta) }}</span>
                </template>
              </div>
            </div>
          </div>
          <div v-if="auditLogMeta" class="admin-pagination">
            <button :disabled="auditLogMeta.current_page <= 1" @click="fetchAuditLog(auditLogMeta.current_page - 1)" class="soft-button secondary" style="height:36px;padding:0 16px;font-size:13px;">Назад</button>
            <span style="color:var(--text-muted);font-size:14px;">Стр. {{ auditLogMeta.current_page }} / {{ auditLogMeta.last_page }}</span>
            <button :disabled="auditLogMeta.current_page >= auditLogMeta.last_page" @click="fetchAuditLog(auditLogMeta.current_page + 1)" class="soft-button secondary" style="height:36px;padding:0 16px;font-size:13px;">Вперёд</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ МОДАЛ: РЕДАКТИРОВАНИЕ ТАРИФА ============ -->
    <div v-if="showTariffModal" class="modal-overlay" @click.self="showTariffModal = false">
      <div class="modal-content glass">
        <h3 class="modal-title">{{ tariffForm.id ? 'Тариф #' + tariffForm.id : 'Новый тариф' }}</h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <label class="form-label"><span>Название</span><input v-model="tariffForm.name" class="input-soft" placeholder="Diamond"></label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <label class="form-label"><span>RAM (MB)</span><input v-model.number="tariffForm.ram_mb" type="number" class="input-soft"></label>
            <label class="form-label"><span>CPU (%)</span><input v-model.number="tariffForm.cpu_percent" type="number" class="input-soft"></label>
            <label class="form-label"><span>Disk (MB)</span><input v-model.number="tariffForm.disk_mb" type="number" class="input-soft"></label>
            <label class="form-label"><span>Slots</span><input v-model.number="tariffForm.slots" type="number" class="input-soft"></label>
          </div>
          <label class="form-label"><span>Цена / день, ₽</span><input v-model.number="tariffForm.price_day" type="number" step="0.01" class="input-soft"></label>
        </div>
        <div class="modal-actions" style="margin-top:20px;">
          <button @click="showTariffModal = false" class="soft-button secondary">Отмена</button>
          <button @click="saveTariff" class="soft-button primary">Сохранить</button>
        </div>
      </div>
    </div>

    <!-- ============ МОДАЛ: ПРОМОКОД ============ -->
    <div v-if="showPromoModal" class="modal-overlay" @click.self="showPromoModal = false">
      <div class="modal-content glass">
        <h3 class="modal-title">Новый промокод</h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <label class="form-label"><span>Код</span><input v-model="promoForm.code" class="input-soft" placeholder="SUMMER25"></label>
          <label class="form-label"><span>Скидка (%)</span><input v-model.number="promoForm.discount_pct" type="number" min="1" max="100" class="input-soft"></label>
          <label class="form-label"><span>Максимум использований (0 = безлимит)</span><input v-model.number="promoForm.max_uses" type="number" min="0" class="input-soft"></label>
          <label class="form-label"><span>Истекает (необязательно)</span><input v-model="promoForm.expires_at" type="date" class="input-soft"></label>
        </div>
        <div class="modal-actions" style="margin-top:20px;">
          <button @click="showPromoModal = false" class="soft-button secondary">Отмена</button>
          <button @click="savePromo" class="soft-button primary">Создать</button>
        </div>
      </div>
    </div>

    <!-- ============ МОДАЛ: НОДА ============ -->
    <div v-if="showNodeModal" class="modal-overlay" @click.self="showNodeModal = false">
      <div class="modal-content glass">
        <h3 class="modal-title">Новая нода</h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <label class="form-label"><span>Имя</span><input v-model="nodeForm.name" class="input-soft" placeholder="DE Frankfurt 1"></label>
          <label class="form-label"><span>FQDN</span><input v-model="nodeForm.fqdn" class="input-soft" placeholder="node1.example.com"></label>
          <label class="form-label"><span>Location</span><input v-model="nodeForm.location" class="input-soft" placeholder="de-fra1"></label>
          <label class="form-label"><span>Pterodactyl Node ID</span><input v-model.number="nodeForm.ptero_node_id" type="number" class="input-soft"></label>
          <label class="form-label"><span>Лимит серверов (0 = безлимит)</span><input v-model.number="nodeForm.max_servers" type="number" min="0" class="input-soft"></label>
        </div>
        <div class="modal-actions" style="margin-top:20px;">
          <button @click="showNodeModal = false" class="soft-button secondary">Отмена</button>
          <button @click="saveNode" class="soft-button primary">Создать</button>
        </div>
      </div>
    </div>

    <!-- ============ МОДАЛ: ВЕРСИЯ MINECRAFT ============ -->
    <div v-if="showVersionModal" class="modal-overlay" @click.self="showVersionModal = false">
      <div class="modal-content glass">
        <h3 class="modal-title">{{ versionForm.id ? 'Версия #' + versionForm.id : 'Новая версия' }}</h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
          <label class="form-label">
            <span>Slug (значение для заказа, напр. paper_1.20.6)</span>
            <input v-model="versionForm.slug" class="input-soft" placeholder="paper_1.20.6" :disabled="!!versionForm.id">
          </label>
          <label class="form-label"><span>Название (видит пользователь)</span><input v-model="versionForm.label" class="input-soft" placeholder="Paper 1.20.6"></label>
          <label class="form-label">
            <span>Тип ядра</span>
            <select v-model="versionForm.type" class="input-soft select-soft">
              <option value="vanilla">vanilla</option>
              <option value="paper">paper</option>
              <option value="forge">forge</option>
              <option value="fabric">fabric</option>
              <option value="spigot">spigot</option>
            </select>
          </label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <label class="form-label"><span>Pterodactyl Egg ID</span><input v-model.number="versionForm.ptero_egg_id" type="number" class="input-soft"></label>
            <label class="form-label"><span>Порядок сортировки</span><input v-model.number="versionForm.sort_order" type="number" class="input-soft"></label>
          </div>
          <label class="form-label" style="flex-direction:row;align-items:center;gap:10px;">
            <input v-model="versionForm.is_active" type="checkbox" style="width:auto;">
            <span>Активна (показывать при заказе)</span>
          </label>
        </div>
        <div class="modal-actions" style="margin-top:20px;">
          <button @click="showVersionModal = false" class="soft-button secondary">Отмена</button>
          <button @click="saveVersion" class="soft-button primary">Сохранить</button>
        </div>
      </div>
    </div>

    <!-- ============ МОДАЛ: РЕДАКТИРОВАНИЕ ПОЛЬЗОВАТЕЛЯ ============ -->
    <div v-if="showEditUserModal" class="modal-overlay" @click.self="closeEditUser">
      <div class="modal-content glass">
        <h3 class="modal-title">Пользователь #{{ editingUser?.id }}</h3>
        <p class="modal-desc">{{ editingUser?.email }}</p>
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div class="balance-info-block">
            <span class="bi-label">Текущий баланс</span>
            <span class="bi-value">{{ editingUser?.balance ?? 0 }} &#8381;</span>
          </div>
          <label class="form-label">
            <span>Изменить баланс (&#8381;)</span>
            <div class="balance-delta-row">
              <button type="button" class="delta-sign-btn" :class="{ minus: balanceDeltaSign === '-' }" @click="balanceDeltaSign = balanceDeltaSign === '+' ? '-' : '+'">
                {{ balanceDeltaSign }}
              </button>
              <input v-model.number="editUserForm.balance_delta" type="number" min="0" step="0.01" class="input-soft" placeholder="0.00" style="flex:1;">
            </div>
          </label>
          <div v-if="editUserForm.balance_delta > 0" class="balance-preview">
            Новый баланс: <strong>{{ previewBalance }} &#8381;</strong>
          </div>
        </div>
        <div class="modal-actions" style="margin-top:24px;">
          <button @click="closeEditUser" class="soft-button secondary">Отмена</button>
          <button @click="saveEditUser" :disabled="isSavingUser" class="soft-button primary">
            {{ isSavingUser ? 'Сохранение...' : 'Применить' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ============ МОДАЛ: СМЕНА ТАРИФА СЕРВЕРА ============ -->
    <div v-if="showChangeTariffModal" class="modal-overlay" @click.self="showChangeTariffModal = false">
      <div class="modal-content glass">
        <h3 class="modal-title">Смена тарифа сервера #{{ changeTariffServer?.id }}</h3>
        <p class="modal-desc">Владелец: {{ changeTariffServer?.user?.email }}</p>
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div class="balance-info-block">
            <span class="bi-label">Текущий тариф</span>
            <span class="bi-value">{{ changeTariffServer?.tariff?.name || '—' }}</span>
          </div>
          <label class="form-label">
            <span>Новый тариф</span>
            <select v-model.number="changeTariffForm.tariff_id" class="input-soft select-soft">
              <option v-for="t in adminTariffs" :key="t.id" :value="t.id">
                {{ t.name }} — {{ (t.ram_mb / 1024).toFixed(1) }}GB / {{ t.cpu_percent }}% CPU / {{ t.price_day }} &#8381;/день
              </option>
            </select>
          </label>
        </div>
        <div class="modal-actions" style="margin-top:24px;">
          <button @click="showChangeTariffModal = false" class="soft-button secondary">Отмена</button>
          <button @click="saveChangeTariff" :disabled="isChangingTariff || !changeTariffForm.tariff_id" class="soft-button primary">
            {{ isChangingTariff ? 'Сохранение...' : 'Сменить тариф' }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { mcAsset } from '../utils/assets'
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import { useAuthStore } from '../stores/auth'
import { useToast } from '../utils/toast'

const authStore = useAuthStore()
const router = useRouter()
const { showToast } = useToast()

// ---- Вкладки ----
const activeTab = ref('servers')
const isAdmin = computed(() => authStore.user?.role === 'admin')

const tabs = computed(() => {
  const base = [
    { key: 'servers',  label: 'Серверы',     icon: '' },
    { key: 'tickets',  label: 'Поддержка',   icon: '' },
    { key: 'referral', label: 'Рефералы',    icon: '' },
    { key: 'payments', label: 'Платежи',     icon: '' },
  ]
  if (isAdmin.value) base.push({ key: 'admin', label: 'Администратор', icon: '' })
  return base
})

// ---- СЕРВЕРЫ ----
const servers = ref([])
const loadingServers = ref(true)
const backups = ref({})
const backupsLoading = ref({})
const expandedBackups = ref({})

const statusLabel = (s) => ({
  pending:      'В очереди',
  provisioning: 'Создаётся',
  active:       'Активен',
  suspended:    'Приостановлен',
  deleted:      'Удалён',
  error:        'Ошибка',
}[s] ?? s)

const PENDING_STATUSES = ['pending', 'provisioning']
const isPending = (s) => PENDING_STATUSES.includes(s)

const fetchServers = async () => {
  try { const r = await api.get('/servers'); servers.value = r.data }
  catch { showToast('Ошибка загрузки серверов', 'error') }
  finally { loadingServers.value = false }
}

// Polling пока есть сервера в pending/provisioning
const hasPendingServer = computed(() => servers.value.some(s => isPending(s.status)))
let pollHandle = null
watch(hasPendingServer, (active) => {
  if (active && !pollHandle) {
    pollHandle = setInterval(fetchServers, 5000)
  } else if (!active && pollHandle) {
    clearInterval(pollHandle); pollHandle = null
  }
}, { immediate: true })
onUnmounted(() => {
  if (pollHandle) clearInterval(pollHandle)
  stopAdminTicketPolling()
})

const powerSignal = async (id, signal) => {
  try {
    await api.post(`/servers/${id}/power`, { signal })
    showToast(`Команда ${signal} отправлена`, 'success')
  } catch (e) {
    showToast(e.response?.data?.error || 'Ошибка управления сервером', 'error')
  }
}

const toggleBackups = async (serverId) => {
  expandedBackups.value[serverId] = !expandedBackups.value[serverId]
  if (expandedBackups.value[serverId] && !backups.value[serverId]) {
    backupsLoading.value[serverId] = true
    try {
      const r = await api.get(`/servers/${serverId}/backups`)
      backups.value[serverId] = r.data
    } catch { backups.value[serverId] = [] }
    finally { backupsLoading.value[serverId] = false }
  }
}

const createBackup = async (serverId) => {
  try {
    await api.post(`/servers/${serverId}/backups`)
    showToast('Бэкап создаётся, это займёт несколько минут', 'success')
    backups.value[serverId] = null // сброс кэша
    await toggleBackups(serverId); await toggleBackups(serverId) // refresh
  } catch (e) {
    showToast(e.response?.data?.error || 'Ошибка создания бэкапа', 'error')
  }
}

// ---- ПРОДЛЕНИЕ ----
const renewTarget = ref(null)
const renewDays = ref(30)
const isRenewing = ref(false)
const renewError = ref('')

const renewCost = computed(() => {
  if (!renewTarget.value?.tariff?.price_day) return 0
  return Math.round(renewTarget.value.tariff.price_day * renewDays.value)
})

const openRenewModal = (server) => {
  renewTarget.value = server
  renewDays.value = 30
  renewError.value = ''
}

const submitRenew = async (server) => {
  isRenewing.value = true
  renewError.value = ''
  try {
    const r = await api.post(`/servers/${server.id}/renew`, { days: renewDays.value })
    showToast(r.data.message || 'Сервер продлён!', 'success')
    renewTarget.value = null
    await fetchServers()
    await authStore.fetchUser() // Обновить баланс
  } catch (e) {
    renewError.value = e.response?.data?.error || 'Ошибка продления'
  } finally { isRenewing.value = false }
}

// ---- ТИКЕТЫ ----
const tickets = ref([])
const loadingTickets = ref(true)
const newTicket = ref({ subject: '', message: '' })
const isCreatingTicket = ref(false)
const openTicketsCount = computed(() => tickets.value.filter(t => t.status === 'open').length)

const fetchTickets = async () => {
  try { const r = await api.get('/tickets'); tickets.value = r.data }
  catch { showToast('Ошибка загрузки тикетов', 'error') }
  finally { loadingTickets.value = false }
}

const createTicket = async () => {
  isCreatingTicket.value = true
  try {
    const r = await api.post('/tickets', newTicket.value)
    showToast('Тикет создан!', 'success')
    newTicket.value = { subject: '', message: '' }
    await fetchTickets()
    router.push(`/tickets/${r.data.id}`)
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка создания тикета', 'error')
  } finally { isCreatingTicket.value = false }
}

const goToTicket = (id) => router.push(`/tickets/${id}`)

// ---- РЕФЕРАЛЫ ----
const referralData = ref(null)
const loadingReferral = ref(true)
const isGenerating = ref(false)
const copied = ref(false)
const refLink = computed(() => referralData.value?.code
  ? `${window.location.origin}/register?ref=${referralData.value.code}`
  : '')

const fetchReferral = async () => {
  try { const r = await api.get('/referral'); referralData.value = r.data }
  catch { /* нет кода ещё — это нормально */ }
  finally { loadingReferral.value = false }
}

const generateRefCode = async () => {
  isGenerating.value = true
  try {
    const r = await api.post('/referral/generate')
    referralData.value = r.data
    showToast('Реферальный код создан!', 'success')
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка генерации кода', 'error')
  } finally { isGenerating.value = false }
}

const copyRefLink = () => {
  navigator.clipboard.writeText(refLink.value)
  copied.value = true
  setTimeout(() => (copied.value = false), 2000)
}

// ---- ПЛАТЕЖИ ----
const payments = ref([])
const loadingPayments = ref(true)
const paymentStatusLabel = (s) => ({ pending: 'Ожидание', success: 'Успешно', canceled: 'Отменён' }[s] ?? s)

const fetchPayments = async () => {
  try { const r = await api.get('/payments/history'); payments.value = r.data }
  catch { showToast('Ошибка загрузки платежей', 'error') }
  finally { loadingPayments.value = false }
}

// ---- ПОПОЛНЕНИЕ ----
const showTopupModal = ref(false)
const topupAmount = ref(100)
const isToppingUp = ref(false)
const topupError = ref('')
const payMethods = ref([])
const payMethod = ref('stub')

const fetchPayMethods = async () => {
  try {
    const r = await api.get('/payments/methods')
    payMethods.value = r.data || []
    if (payMethods.value.length) payMethod.value = payMethods.value[0].name
  } catch { payMethods.value = [] }
}

const closeModal = () => { showTopupModal.value = false; topupError.value = ''; topupAmount.value = 100 }

const handleTopup = async () => {
  if (topupAmount.value < 50) { topupError.value = 'Минимальная сумма — 50 ₽'; return }
  isToppingUp.value = true; topupError.value = ''
  try {
    // Тестовая оплата (stub) — мгновенно зачисляем через старый endpoint.
    if (!payMethod.value || payMethod.value === 'stub') {
      await api.post('/payments/topup', { amount: topupAmount.value })
      await authStore.fetchUser()
      await fetchPayments()
      showToast(`Баланс пополнен на ${topupAmount.value} ₽`, 'success')
      closeModal()
      return
    }

    // Боевой провайдер — инициируем платёж и редиректим на оплату.
    const r = await api.post('/payments/initiate', {
      amount: topupAmount.value,
      provider: payMethod.value,
    })
    if (r.data.confirmation_url) {
      window.location.href = r.data.confirmation_url
    } else {
      topupError.value = 'Платёжная система не вернула ссылку на оплату'
    }
  } catch (e) {
    topupError.value = e.response?.data?.error || e.response?.data?.message || 'Ошибка пополнения'
  } finally { isToppingUp.value = false }
}

// ---- АДМИНИСТРАТОР ----
const adminTab = ref('overview')
const adminSubTabs = [
  { key: 'overview',  label: 'Обзор' },
  { key: 'servers',   label: 'Серверы' },
  { key: 'tickets',   label: 'Чат' },
  { key: 'tariffs',   label: 'Тарифы' },
  { key: 'promo',     label: 'Промокоды' },
  { key: 'nodes',     label: 'Ноды' },
  { key: 'versions',  label: 'Версии MC' },
  { key: 'settings',  label: 'Настройки' },
  { key: 'audit',     label: 'Аудит' },
]

const adminStats = ref(null)
const loadingAdminStats = ref(false)
const adminUsers = ref([])
const adminUsersMeta = ref(null)
const loadingAdminUsers = ref(false)
const showEditUserModal = ref(false)
const editingUser = ref(null)
const editUserForm = ref({ balance_delta: 0 })
const balanceDeltaSign = ref('+')
const isSavingUser = ref(false)

// Серверы
const adminServers = ref([])
const loadingAdminServers = ref(false)

// Тарифы (админ)
const adminTariffs = ref([])
const showTariffModal = ref(false)
const tariffForm = ref({ id: null, name: '', ram_mb: 1024, cpu_percent: 100, disk_mb: 10240, slots: 10, price_day: 10 })

// Промокоды
const adminPromos = ref([])
const showPromoModal = ref(false)
const promoForm = ref({ code: '', discount_pct: 10, max_uses: 0, expires_at: null })

// Ноды
const adminNodes = ref([])
const showNodeModal = ref(false)
const nodeForm = ref({ name: '', fqdn: '', location: '', ptero_node_id: null, max_servers: 0 })

// Версии MC
const adminVersions = ref([])

// Настройки
const adminSettings = ref([])
const loadingAdminSettings = ref(false)

// Audit log
const auditLog = ref([])
const auditLogMeta = ref(null)
const loadingAuditLog = ref(false)

const fetchAdminStats = async () => {
  loadingAdminStats.value = true
  try { const r = await api.get('/admin/dashboard'); adminStats.value = r.data }
  catch { showToast('Ошибка загрузки статистики', 'error') }
  finally { loadingAdminStats.value = false }
}

const fetchAdminUsers = async (page = 1) => {
  loadingAdminUsers.value = true
  try {
    const r = await api.get(`/admin/users?page=${page}`)
    adminUsers.value = r.data.data
    adminUsersMeta.value = r.data
  } catch { showToast('Ошибка загрузки пользователей', 'error') }
  finally { loadingAdminUsers.value = false }
}

const fetchAdminServers = async () => {
  loadingAdminServers.value = true
  try { const r = await api.get('/admin/servers'); adminServers.value = r.data.data || r.data }
  catch { showToast('Ошибка загрузки серверов', 'error') }
  finally { loadingAdminServers.value = false }
}

const adminSuspendServer = async (id) => {
  try { await api.post(`/admin/servers/${id}/suspend`); showToast('Suspended', 'success'); fetchAdminServers() }
  catch (e) { showToast(e.response?.data?.error || 'Ошибка', 'error') }
}
const adminUnsuspendServer = async (id) => {
  try { await api.post(`/admin/servers/${id}/unsuspend`); showToast('Unsuspended', 'success'); fetchAdminServers() }
  catch (e) { showToast(e.response?.data?.error || 'Ошибка', 'error') }
}

// ---- Тикеты (админ) ----
const adminTickets = ref([])
const loadingAdminTickets = ref(false)
const ticketFilter = ref('')
const activeTicket = ref(null)
const activeTicketMessages = ref([])
const adminReplyText = ref('')
const isAdminReplying = ref(false)
const adminMsgBox = ref(null)

const ticketStatusLabel = (s) => ({ open: 'Открыт', answered: 'Отвечен', closed: 'Закрыт' }[s] ?? s)
const ticketStatusClass = (s) => ({ open: 'error', answered: 'active', closed: 'suspended' }[s] ?? 'suspended')

const isAssigning = ref(false)
let adminTicketPollHandle = null

const assignTicket = async (id) => {
  isAssigning.value = true
  try {
    const r = await api.post(`/admin/tickets/${id}/assign`)
    showToast('Тикет принят в работу', 'success')
    if (activeTicket.value?.id === id) {
      activeTicket.value.assigned_admin_id = authStore.user?.id
      activeTicket.value.assigned_admin = r.data.assigned_admin
    }
    fetchAdminTickets()
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка назначения', 'error')
  } finally { isAssigning.value = false }
}

const releaseTicket = async (id) => {
  isAssigning.value = true
  try {
    await api.post(`/admin/tickets/${id}/assign`, { release: true })
    showToast('Тикет снят с работы', 'success')
    if (activeTicket.value?.id === id) {
      activeTicket.value.assigned_admin_id = null
      activeTicket.value.assigned_admin = null
    }
    fetchAdminTickets()
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка', 'error')
  } finally { isAssigning.value = false }
}

// Поллинг сообщений открытого тикета (каждые 5 сек).
const startAdminTicketPolling = () => {
  stopAdminTicketPolling()
  adminTicketPollHandle = setInterval(async () => {
    if (!activeTicket.value) return
    try {
      const r = await api.get(`/admin/tickets/${activeTicket.value.id}`)
      activeTicketMessages.value = r.data.messages
      activeTicket.value.status = r.data.ticket.status
      activeTicket.value.assigned_admin = r.data.ticket.assigned_admin ?? activeTicket.value.assigned_admin
      activeTicket.value.assigned_admin_id = r.data.ticket.assigned_admin_id ?? activeTicket.value.assigned_admin_id
      await nextTick()
      if (adminMsgBox.value) adminMsgBox.value.scrollTop = adminMsgBox.value.scrollHeight
    } catch {}
  }, 5000)
}

const stopAdminTicketPolling = () => {
  if (adminTicketPollHandle) { clearInterval(adminTicketPollHandle); adminTicketPollHandle = null }
}

const fetchAdminTickets = async () => {
  loadingAdminTickets.value = true
  try {
    const q = ticketFilter.value ? `?status=${ticketFilter.value}` : ''
    const r = await api.get(`/admin/tickets${q}`)
    adminTickets.value = r.data.data || r.data
  } catch { showToast('Ошибка загрузки тикетов', 'error') }
  finally { loadingAdminTickets.value = false }
}

const openAdminTicket = async (id) => {
  try {
    const r = await api.get(`/admin/tickets/${id}`)
    activeTicket.value = r.data.ticket
    activeTicketMessages.value = r.data.messages
    await nextTick()
    if (adminMsgBox.value) adminMsgBox.value.scrollTop = adminMsgBox.value.scrollHeight
    startAdminTicketPolling()
  } catch { showToast('Ошибка загрузки тикета', 'error') }
}

const sendAdminReply = async () => {
  const text = adminReplyText.value.trim()
  if (!text) return
  isAdminReplying.value = true
  try {
    const r = await api.post(`/admin/tickets/${activeTicket.value.id}/reply`, { message: text })
    activeTicketMessages.value.push(r.data.data)
    adminReplyText.value = ''
    activeTicket.value.status = 'answered'
    await nextTick()
    if (adminMsgBox.value) adminMsgBox.value.scrollTop = adminMsgBox.value.scrollHeight
    fetchAdminTickets()
  } catch (e) {
    showToast(e.response?.data?.message || 'Ошибка отправки', 'error')
  } finally { isAdminReplying.value = false }
}

const setTicketStatus = async (status) => {
  try {
    await api.patch(`/admin/tickets/${activeTicket.value.id}/status`, { status })
    activeTicket.value.status = status
    fetchAdminTickets()
    showToast('Статус обновлён', 'success')
  } catch { showToast('Ошибка', 'error') }
}

const fetchAdminTariffs = async () => {
  try { const r = await api.get('/admin/tariffs'); adminTariffs.value = r.data }
  catch { showToast('Ошибка загрузки тарифов', 'error') }
}

const openTariffEdit = (t) => {
  tariffForm.value = t
    ? { ...t }
    : { id: null, name: '', ram_mb: 1024, cpu_percent: 100, disk_mb: 10240, slots: 10, price_day: 10 }
  showTariffModal.value = true
}

const saveTariff = async () => {
  try {
    if (tariffForm.value.id) await api.put(`/admin/tariffs/${tariffForm.value.id}`, tariffForm.value)
    else await api.post('/admin/tariffs', tariffForm.value)
    showToast('Сохранено', 'success'); showTariffModal.value = false; fetchAdminTariffs()
  } catch (e) { showToast(e.response?.data?.message || 'Ошибка', 'error') }
}

const deleteTariff = async (t) => {
  if (!confirm(`Удалить тариф "${t.name}"?`)) return
  try { await api.delete(`/admin/tariffs/${t.id}`); showToast('Удалён', 'success'); fetchAdminTariffs() }
  catch (e) { showToast(e.response?.data?.error || 'Ошибка', 'error') }
}

const fetchAdminPromos = async () => {
  try { const r = await api.get('/admin/promo'); adminPromos.value = r.data }
  catch { showToast('Ошибка загрузки промокодов', 'error') }
}

const openPromoEdit = () => {
  promoForm.value = { code: '', discount_pct: 10, max_uses: 0, expires_at: null }
  showPromoModal.value = true
}

const savePromo = async () => {
  try {
    await api.post('/admin/promo', promoForm.value)
    showToast('Промокод создан', 'success'); showPromoModal.value = false; fetchAdminPromos()
  } catch (e) { showToast(e.response?.data?.message || 'Ошибка', 'error') }
}

const deletePromo = async (p) => {
  if (!confirm(`Удалить промокод "${p.code}"?`)) return
  try { await api.delete(`/admin/promo/${p.id}`); showToast('Удалён', 'success'); fetchAdminPromos() }
  catch { showToast('Ошибка', 'error') }
}

const fetchAdminNodes = async () => {
  try { const r = await api.get('/admin/nodes'); adminNodes.value = r.data }
  catch { showToast('Ошибка загрузки нод', 'error') }
}

const openNodeEdit = () => {
  nodeForm.value = { name: '', fqdn: '', location: '', ptero_node_id: null, max_servers: 0 }
  showNodeModal.value = true
}

const saveNode = async () => {
  try {
    await api.post('/admin/nodes', { ...nodeForm.value, is_active: true })
    showToast('Нода создана', 'success'); showNodeModal.value = false; fetchAdminNodes()
  } catch (e) { showToast(e.response?.data?.message || 'Ошибка', 'error') }
}

const deleteNode = async (n) => {
  if (!confirm(`Удалить ноду "${n.name}"?`)) return
  try { await api.delete(`/admin/nodes/${n.id}`); showToast('Удалена', 'success'); fetchAdminNodes() }
  catch (e) { showToast(e.response?.data?.error || 'Ошибка', 'error') }
}

const fetchAdminVersions = async () => {
  try { const r = await api.get('/admin/mc-versions'); adminVersions.value = r.data }
  catch { showToast('Ошибка загрузки версий', 'error') }
}

const showVersionModal = ref(false)
const versionForm = ref({ id: null, slug: '', label: '', type: 'vanilla', ptero_egg_id: null, sort_order: 0, is_active: true })

const openVersionEdit = (v) => {
  versionForm.value = v
    ? { ...v, is_active: !!v.is_active }
    : { id: null, slug: '', label: '', type: 'vanilla', ptero_egg_id: null, sort_order: 0, is_active: true }
  showVersionModal.value = true
}

const saveVersion = async () => {
  try {
    const payload = { ...versionForm.value }
    if (payload.id) {
      // slug при обновлении не отправляем (он disabled и не меняется)
      delete payload.slug
      await api.put(`/admin/mc-versions/${payload.id}`, payload)
    } else {
      await api.post('/admin/mc-versions', payload)
    }
    showToast('Сохранено', 'success'); showVersionModal.value = false; fetchAdminVersions()
  } catch (e) {
    const msg = e.response?.data?.message
      || Object.values(e.response?.data?.errors || {})[0]?.[0]
      || 'Ошибка сохранения'
    showToast(msg, 'error')
  }
}

const deleteVersion = async (v) => {
  if (!confirm(`Удалить версию "${v.label}"?`)) return
  try { await api.delete(`/admin/mc-versions/${v.id}`); showToast('Удалена', 'success'); fetchAdminVersions() }
  catch (e) { showToast(e.response?.data?.error || 'Ошибка', 'error') }
}

const fetchAdminSettings = async () => {
  loadingAdminSettings.value = true
  try { const r = await api.get('/admin/settings'); adminSettings.value = r.data }
  catch { showToast('Ошибка загрузки настроек', 'error') }
  finally { loadingAdminSettings.value = false }
}

const saveSetting = async (s) => {
  try { await api.put(`/admin/settings/${s.key}`, { value: String(s.value ?? '') }); showToast('Сохранено', 'success') }
  catch { showToast('Ошибка сохранения', 'error') }
}

const fetchAuditLog = async (page = 1) => {
  loadingAuditLog.value = true
  try {
    const r = await api.get(`/admin/audit?page=${page}`)
    auditLog.value = r.data.data; auditLogMeta.value = r.data
  } catch { showToast('Ошибка загрузки журнала', 'error') }
  finally { loadingAuditLog.value = false }
}

const auditActionLabel = (action) => ({
  'user.balance_changed':  'Баланс изменён',
  'server.tariff_changed': 'Тариф изменён',
  'server.suspended':      'Сервер приостановлен',
  'server.unsuspended':    'Сервер разблокирован',
  'ticket.replied':        'Ответ в тикете',
  'ticket.assigned':       'Тикет принят',
  'ticket.released':       'Тикет снят',
  'ticket.status':         'Статус тикета',
  'tariff.created':        'Тариф создан',
  'tariff.updated':        'Тариф обновлён',
  'tariff.deleted':        'Тариф удалён',
  'promo.created':         'Промокод создан',
  'promo.deleted':         'Промокод удалён',
  'node.created':          'Нода создана',
  'node.deleted':          'Нода удалена',
  'setting.updated':       'Настройка изменена',
}[action] ?? action)

const auditActionColor = (action) => {
  if (action.includes('balance'))   return 'gold'
  if (action.includes('tariff'))    return 'diamond'
  if (action.includes('suspend'))   return 'warn'
  if (action.includes('unsuspend')) return 'emerald'
  if (action.includes('ticket'))    return 'emerald'
  if (action.includes('deleted'))   return 'danger'
  return ''
}

const switchAdminTab = (key) => {
  adminTab.value = key
  // Обзор обновляем всегда при заходе — данные актуальные.
  if (key === 'overview') {
    fetchAdminStats()
    fetchAdminUsers(adminUsersMeta.value?.current_page || 1)
  }
  if (key === 'servers')   fetchAdminServers()
  if (key === 'tickets')   fetchAdminTickets()
  if (key === 'tariffs')   fetchAdminTariffs()
  if (key === 'promo')     fetchAdminPromos()
  if (key === 'nodes')     fetchAdminNodes()
  if (key === 'versions')  fetchAdminVersions()
  if (key === 'settings')  fetchAdminSettings()
  if (key === 'audit')     fetchAuditLog(auditLogMeta.value?.current_page || 1)
}

const previewBalance = computed(() => {
  if (!editingUser.value) return 0
  const cur = parseFloat(editingUser.value.balance) || 0
  const delta = parseFloat(editUserForm.value.balance_delta) || 0
  const signed = balanceDeltaSign.value === '-' ? -delta : delta
  return Math.max(0, Math.round((cur + signed) * 100) / 100)
})

const openEditUser = (u) => {
  editingUser.value = u
  editUserForm.value = { balance_delta: 0 }
  balanceDeltaSign.value = '+'
  showEditUserModal.value = true
}

const closeEditUser = () => { showEditUserModal.value = false; editingUser.value = null }

const saveEditUser = async () => {
  const delta = parseFloat(editUserForm.value.balance_delta) || 0
  if (delta === 0) { closeEditUser(); return }
  const signedDelta = balanceDeltaSign.value === '-' ? -delta : delta
  isSavingUser.value = true
  try {
    await api.put(`/admin/users/${editingUser.value.id}`, { balance_delta: signedDelta })
    showToast(`Баланс ${signedDelta > 0 ? 'пополнен' : 'списан'} на ${Math.abs(signedDelta)} ₽`, 'success')
    closeEditUser()
    await fetchAdminUsers(adminUsersMeta.value?.current_page || 1)
  } catch (e) {
    showToast(e.response?.data?.error || e.response?.data?.message || 'Ошибка сохранения', 'error')
  } finally { isSavingUser.value = false }
}

// ---- СМЕНА ТАРИФА СЕРВЕРА (АДМИН) ----
const showChangeTariffModal = ref(false)
const changeTariffServer = ref(null)
const changeTariffForm = ref({ tariff_id: null })
const isChangingTariff = ref(false)

const openChangeTariff = (server) => {
  changeTariffServer.value = server
  changeTariffForm.value = { tariff_id: server.tariff?.id || null }
  // Загрузим тарифы если ещё не загружены
  if (!adminTariffs.value.length) fetchAdminTariffs()
  showChangeTariffModal.value = true
}

const saveChangeTariff = async () => {
  if (!changeTariffForm.value.tariff_id) return
  isChangingTariff.value = true
  try {
    const r = await api.put(`/admin/servers/${changeTariffServer.value.id}/tariff`, changeTariffForm.value)
    showToast(r.data.message || 'Тариф изменён', 'success')
    showChangeTariffModal.value = false
    await fetchAdminServers()
  } catch (e) {
    showToast(e.response?.data?.error || e.response?.data?.message || 'Ошибка', 'error')
  } finally { isChangingTariff.value = false }
}

watch(activeTab, (tab) => {
  if (tab === 'admin' && isAdmin.value) {
    switchAdminTab(adminTab.value)
  }
})

// ---- MOUNT ----
onMounted(() => {
  fetchServers()
  fetchTickets()
  fetchReferral()
  fetchPayments()
  fetchPayMethods()
  if (activeTab.value === 'admin' && isAdmin.value) {
    switchAdminTab(adminTab.value)
  }
})
</script>

<style scoped>
.dashboard-page { padding: 60px 0; }

/* Шапка */
.dash-head { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; }
.balance-section { display: flex; flex-direction: column; gap: 12px; align-items: flex-end; }
.balance-card { padding: 20px 30px; display: inline-flex; flex-direction: column; align-items: flex-end; min-width: 220px; }
.b-label { font-size: 14px; color: var(--text-muted); margin-bottom: 8px; }
.b-val { display: flex; align-items: center; gap: 10px; font-size: 36px; font-weight: 800; color: var(--mc-emerald); line-height: 1; }
.b-icon { width: 32px; height: 32px; filter: drop-shadow(0 4px 6px rgba(0,230,118,0.3)); }
.topup-btn { width: 100%; height: 46px; font-size: 14px; }

/* Вкладки */
.tabs { display: flex; gap: 4px; padding: 6px; margin-bottom: 28px; width: fit-content; }
.tab-btn {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 20px; border-radius: 10px; border: none;
  background: transparent; color: var(--text-muted);
  font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s;
  font-family: var(--font-main);
}
.tab-btn:hover { color: var(--text-main); background: rgba(255,255,255,0.04); }
.tab-btn.active { background: rgba(0,230,118,0.12); color: var(--mc-emerald); }
.tab-icon { font-size: 16px; }
.tab-badge {
  background: var(--mc-emerald); color: #003314;
  border-radius: 20px; padding: 1px 7px; font-size: 11px; font-weight: 800;
}

/* Серверы */
.servers-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 24px; }
.server-card { padding: 24px; }
.s-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
.s-id { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
.s-name { margin: 0; font-size: 24px; font-weight: 700; }
.s-status { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; border: 1px solid transparent; }
.s-status.active { background: rgba(0,230,118,0.1); color: var(--mc-emerald); border-color: rgba(0,230,118,0.2); }
.s-status.suspended { background: rgba(255,196,0,0.1); color: var(--mc-gold); border-color: rgba(255,196,0,0.2); }
.s-status.deleted { background: rgba(255,85,85,0.1); color: #ff5555; border-color: rgba(255,85,85,0.2); }
.s-status.pending,
.s-status.provisioning { background: rgba(85,170,255,0.1); color: #55aaff; border-color: rgba(85,170,255,0.2); }
.s-status.error { background: rgba(255,85,85,0.1); color: #ff5555; border-color: rgba(255,85,85,0.2); }
.s-info { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; font-size: 15px; }
.s-info span { color: var(--text-muted); display: inline-block; width: 110px; }
.s-address { color: var(--mc-diamond); font-family: monospace; font-size: 14px; background: rgba(0,0,0,0.25); padding: 2px 8px; border-radius: 6px; }
.s-provisioning { display: flex; align-items: center; gap: 10px; color: var(--mc-gold); font-size: 14px; padding: 8px 0; }
.s-error { color: #ff5555; font-size: 14px; padding: 8px 0; }
.spinner-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--mc-gold); animation: pulse 1.2s ease-in-out infinite; flex-shrink: 0; }
@keyframes pulse {
  0%, 100% { opacity: 0.3; transform: scale(0.85); }
  50%      { opacity: 1;   transform: scale(1.1);  }
}
.s-actions { display: flex; gap: 10px; margin-bottom: 16px; }
.small { height: 40px; padding: 0 14px; font-size: 12px; flex: 1; }
.stop-btn { color: #ff5555 !important; border-color: rgba(255,85,85,0.3) !important; }
.stop-btn:hover { border-color: #ff5555 !important; }

/* Бэкапы */
.backup-section { border-top: 1px solid rgba(255,255,255,0.07); padding-top: 16px; }
.backup-header { display: flex; justify-content: space-between; cursor: pointer; font-size: 14px; color: var(--text-muted); padding: 4px 0; user-select: none; }
.backup-header:hover { color: var(--text-main); }
.expand-arrow { font-size: 10px; }
.backup-body { margin-top: 12px; }
.backup-empty { font-size: 14px; color: var(--text-muted); text-align: center; padding: 8px; }
.backup-list { display: flex; flex-direction: column; gap: 6px; }
.backup-item { display: flex; justify-content: space-between; font-size: 13px; color: var(--text-muted); padding: 6px 8px; background: rgba(0,0,0,0.2); border-radius: 8px; }
.backup-size { color: var(--mc-diamond); }

/* Консоль + Продлить */
.s-extra-actions { display: flex; gap: 10px; margin-bottom: 16px; }
.console-link, .renew-link { flex: 1; text-align: center; }
.console-link { color: var(--mc-diamond) !important; border-color: rgba(0,229,255,0.25) !important; }
.console-link:hover { background: rgba(0,229,255,0.1) !important; }
.renew-link { color: var(--mc-gold) !important; border-color: rgba(255,196,0,0.25) !important; }
.renew-link:hover { background: rgba(255,196,0,0.1) !important; }

.renew-panel { margin-bottom: 16px; padding: 16px; background: rgba(0,0,0,0.2); border-radius: var(--radius-md); border: 1px solid rgba(255,196,0,0.15); }
.renew-row { display: flex; gap: 16px; align-items: flex-end; margin-bottom: 14px; }
.renew-cost { text-align: right; }
.rc-price { display: block; font-size: 24px; font-weight: 800; color: var(--mc-gold); line-height: 1; }
.rc-hint { display: block; font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.renew-btns { display: flex; gap: 10px; }
.renew-error { margin-top: 10px; font-size: 13px; color: #ff5555; text-align: center; }

/* Тикеты */
.tickets-layout { display: grid; grid-template-columns: 340px 1fr; gap: 24px; align-items: start; }
.ticket-create-form { padding: 28px; }
.form-title { font-size: 18px; font-weight: 700; margin: 0 0 20px; }
.field-col { display: flex; flex-direction: column; gap: 16px; }
.form-label { display: flex; flex-direction: column; gap: 8px; font-size: 14px; color: var(--text-muted); font-weight: 500; }
.textarea-soft { height: auto !important; padding: 14px 16px !important; resize: vertical; }
.w-full { width: 100%; }
.tickets-list { display: flex; flex-direction: column; gap: 12px; }
.ticket-item { padding: 18px 22px; cursor: pointer; transition: 0.2s; }
.ticket-item:hover { border-color: var(--mc-diamond); transform: translateX(4px); }
.ticket-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.ticket-id { font-size: 13px; color: var(--text-muted); }
.ticket-status { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.ticket-status.open { background: rgba(0,230,118,0.1); color: var(--mc-emerald); }
.ticket-status.closed { background: rgba(139,155,180,0.15); color: var(--text-muted); }
.ticket-subject { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
.ticket-date { font-size: 13px; color: var(--text-muted); }

/* Рефералы */
.referral-layout { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
.referral-card { padding: 32px; }
.ref-link-box { display: flex; align-items: center; gap: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-md); padding: 14px 20px; margin-bottom: 24px; }
.ref-link-text { flex: 1; font-family: monospace; font-size: 14px; color: var(--mc-diamond); word-break: break-all; }
.copy-btn { height: 38px; padding: 0 16px; font-size: 13px; white-space: nowrap; flex-shrink: 0; }
.ref-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
.ref-stat-card { padding: 20px; text-align: center; }
.rs-label { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
.rs-val { font-size: 32px; font-weight: 800; }
.rs-val.emerald { color: var(--mc-emerald); }
.rs-val.gold { color: var(--mc-gold); }
.commissions-list { border-top: 1px solid rgba(255,255,255,0.07); padding-top: 16px; }
.commission-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
.ref-howto { padding: 32px; }
.howto-steps { display: flex; flex-direction: column; gap: 20px; margin-top: 16px; }
.howto-step { display: flex; align-items: flex-start; gap: 16px; }
.step-num { width: 36px; height: 36px; border-radius: 50%; background: rgba(0,230,118,0.15); color: var(--mc-emerald); display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0; font-size: 16px; }
.howto-step strong { display: block; margin-bottom: 4px; }
.howto-step p { margin: 0; font-size: 14px; color: var(--text-muted); }

/* Платежи */
.payments-table { margin-top: 20px; }
.pt-header { display: grid; grid-template-columns: 1.5fr 2fr 1fr 1fr; gap: 16px; padding: 10px 16px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
.pt-row { display: grid; grid-template-columns: 1.5fr 2fr 1fr 1fr; gap: 16px; padding: 14px 16px; border-top: 1px solid rgba(255,255,255,0.06); font-size: 14px; align-items: center; }
.pt-row:hover { background: rgba(255,255,255,0.02); }
.pt-amount { font-weight: 700; }
.pt-amount.positive { color: var(--mc-emerald); }
.payment-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; width: fit-content; }
.payment-status.success { background: rgba(0,230,118,0.1); color: var(--mc-emerald); }
.payment-status.pending { background: rgba(255,196,0,0.1); color: var(--mc-gold); }
.payment-status.canceled { background: rgba(255,85,85,0.1); color: #ff5555; }

/* Пустые состояния */
.empty-state { padding: 60px 20px; text-align: center; }
.empty-img { width: 80px; height: 80px; margin-bottom: 20px; opacity: 0.8; }
.empty-state h2 { margin: 0 0 10px; font-size: 24px; }
.empty-state p { color: var(--text-muted); margin: 0; }
.mt-4 { margin-top: 24px; display: inline-flex; }

/* Модал */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 999; backdrop-filter: blur(5px); }
.modal-content { width: 100%; max-width: 400px; padding: 30px; animation: modalIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.modal-title { font-size: 24px; font-weight: 700; margin: 0 0 10px; }
.modal-desc { color: var(--text-muted); font-size: 15px; margin-bottom: 24px; }
.topup-input-wrapper { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 16px; width: 24px; height: 24px; }
.topup-input { padding-left: 50px !important; padding-right: 40px !important; font-size: 20px; font-weight: 700; height: 60px; }
.currency-badge { position: absolute; right: 16px; font-size: 20px; font-weight: 700; color: var(--text-muted); }
.modal-actions { display: flex; gap: 12px; margin-top: 24px; }
.modal-actions button { flex: 1; }
.error-box { padding: 12px; border-radius: var(--radius-md); background: rgba(255,85,85,0.1); color: #ff5555; border: 1px solid rgba(255,85,85,0.2); font-size: 14px; text-align: center; }
.mt-4 { margin-top: 16px; }

@keyframes modalIn {
  0% { transform: scale(0.9) translateY(20px); opacity: 0; }
  100% { transform: scale(1) translateY(0); opacity: 1; }
}

/* Администратор */
.admin-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 0; }
.admin-stat-card { padding: 28px 24px; }
.as-label { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.as-val { font-size: 36px; font-weight: 800; }
.as-val.diamond { color: var(--mc-diamond); }
.as-val.emerald { color: var(--mc-emerald); }
.as-val.gold { color: var(--mc-gold); }
.admin-users-header {
  display: grid; grid-template-columns: 60px 1fr 100px 100px 90px 120px;
  gap: 12px; padding: 10px 16px;
  font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;
}
.admin-user-row {
  display: grid; grid-template-columns: 60px 1fr 100px 100px 90px 120px;
  gap: 12px; padding: 14px 16px; border-top: 1px solid rgba(255,255,255,0.06);
  font-size: 14px; align-items: center;
}
.admin-user-row:hover { background: rgba(255,255,255,0.02); }
.au-id { color: var(--text-muted); font-size: 13px; }
.au-email { color: var(--text-main); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.au-role { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.au-role.admin { background: rgba(255,196,0,0.1); color: var(--mc-gold); }
.au-role.client { background: rgba(0,229,255,0.1); color: var(--mc-diamond); }
.au-balance { color: var(--mc-emerald); font-weight: 700; }
.au-servers { color: var(--text-muted); }
.admin-pagination { display: flex; align-items: center; justify-content: center; gap: 20px; margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.06); }

  .dash-head { flex-direction: column; align-items: stretch; gap: 20px; }
  .balance-section { align-items: stretch; }
  .balance-card { align-items: flex-start; }
  .tabs { width: 100%; overflow-x: auto; }
  .tab-btn { white-space: nowrap; }
  .tickets-layout, .referral-layout { grid-template-columns: 1fr; }
  .servers-grid { grid-template-columns: 1fr; }
  .pt-header, .pt-row { grid-template-columns: 1fr 1fr; }
  .pt-header span:nth-child(2), .pt-row span:nth-child(2) { display: none; }

/* Общие таблицы админки (servers/tariffs/promo/nodes/audit) */
.admin-table { margin-top: 8px; }
.admin-table-header {
  display: grid;
  padding: 10px 14px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 0.5px;
  gap: 10px;
}
.admin-table-row {
  display: grid;
  padding: 12px 14px;
  border-top: 1px solid rgba(255,255,255,0.06);
  font-size: 14px;
  align-items: center;
  gap: 10px;
}
.admin-table-row:hover { background: rgba(255,255,255,0.02); }

/* Строка настроек */
.setting-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 0;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.setting-row:last-child { border-bottom: none; }

.pay-methods { display: flex; flex-direction: column; gap: 8px; margin-top: 16px; }
.pay-method {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.08); cursor: pointer;
  transition: all 0.15s;
}
.pay-method.active { border-color: var(--mc-emerald); background: rgba(0,230,118,0.06); }
.pay-method input { width: auto; }

/* Админ-тикеты */
.admin-tickets-layout { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; }
.ticket-list { display: flex; flex-direction: column; gap: 8px; max-height: 600px; overflow-y: auto; }
.ticket-list-item { padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06); cursor: pointer; transition: all 0.15s; }
.ticket-list-item:hover { background: rgba(255,255,255,0.03); }
.ticket-list-item.active { border-color: var(--mc-emerald); background: rgba(0,230,118,0.06); }
.ticket-messages { flex: 1; display: flex; flex-direction: column; gap: 12px; max-height: 460px; overflow-y: auto; padding: 4px; margin-bottom: 16px; }
.ticket-msg { max-width: 75%; padding: 10px 14px; border-radius: 12px; background: rgba(255,255,255,0.05); align-self: flex-start; }
.ticket-msg.from-admin { align-self: flex-end; background: rgba(0,230,118,0.12); }
.ticket-msg-author { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; }
.ticket-msg-body { font-size: 14px; white-space: pre-wrap; word-break: break-word; }

/* Чат — метки назначения */
.at-assigned-chip { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; background: rgba(0,230,118,0.12); color: var(--mc-emerald); margin-left: 6px; }
.at-assigned-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background: rgba(0,230,118,0.1); color: var(--mc-emerald); border: 1px solid rgba(0,230,118,0.2); }
.ticket-reply-box { display: flex; gap: 10px; align-items: stretch; }
.ticket-reply-box textarea { flex: 1; }
@media (max-width: 900px) { .admin-tickets-layout { grid-template-columns: 1fr; } }

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* Баланс — дельта */
.balance-info-block {
  display: flex; justify-content: space-between; align-items: center;
  padding: 14px 18px; border-radius: var(--radius-md);
  background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08);
}
.bi-label { font-size: 14px; color: var(--text-muted); }
.bi-value { font-size: 20px; font-weight: 800; color: var(--mc-emerald); }
.balance-delta-row { display: flex; gap: 8px; align-items: center; }
.delta-sign-btn {
  width: 44px; height: 44px; flex-shrink: 0;
  border-radius: var(--radius-md); border: 1px solid rgba(0,230,118,0.3);
  background: rgba(0,230,118,0.1); color: var(--mc-emerald);
  font-size: 22px; font-weight: 800; cursor: pointer; transition: 0.2s;
  font-family: var(--font-main); display: flex; align-items: center; justify-content: center;
}
.delta-sign-btn.minus { border-color: rgba(255,85,85,0.3); background: rgba(255,85,85,0.1); color: #ff5555; }
.balance-preview {
  padding: 10px 16px; border-radius: var(--radius-md);
  background: rgba(0,229,255,0.06); border: 1px solid rgba(0,229,255,0.15);
  font-size: 14px; color: var(--text-muted); text-align: center;
}
.balance-preview strong { color: var(--mc-diamond); }

/* Аудит */
.audit-entry { transition: border-color 0.2s; }
.audit-entry:hover { border-color: rgba(255,255,255,0.12); }
.audit-action-badge {
  display: inline-block; padding: 3px 10px; border-radius: 10px;
  font-size: 12px; font-weight: 700;
  background: rgba(255,255,255,0.06); color: var(--text-main);
}
.audit-action-badge.gold { background: rgba(255,196,0,0.12); color: var(--mc-gold); }
.audit-action-badge.diamond { background: rgba(0,229,255,0.12); color: var(--mc-diamond); }
.audit-action-badge.emerald { background: rgba(0,230,118,0.12); color: var(--mc-emerald); }
.audit-action-badge.warn { background: rgba(255,196,0,0.12); color: var(--mc-gold); }
.audit-action-badge.danger { background: rgba(255,85,85,0.12); color: #ff5555; }
.audit-meta {
  margin-top: 8px; padding-top: 8px;
  border-top: 1px solid rgba(255,255,255,0.06);
  font-size: 13px; display: flex; align-items: center; flex-wrap: wrap; gap: 2px;
}
</style>
