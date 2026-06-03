<template>
  <div class="home-page">

    <!-- ===== HERO ===== -->
    <section class="hero-section">
      <div class="container hero-inner">
        <div class="hero-left">
          <div class="badge">
            <img :src="mcAsset('Ender_Pearl_JE3_BE2.png')" alt="" class="inline-icon pixel-img">
            Запуск за 60 секунд
          </div>
          <h1 class="hero-title">
            Премиальный хостинг<br>
            для <span class="highlight">Ваших Серверов</span>
          </h1>
          <p class="hero-text">
            Высокочастотные процессоры, NVMe накопители и удобная панель Pterodactyl.
            Управляйте сервером, делайте бэкапы и приглашайте друзей — всё в одном месте.
          </p>
          <div class="hero-stats">
            <div class="stat-item">
              <div class="stat-val">99.9%</div>
              <div class="stat-label">Uptime</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <div class="stat-val">NVMe</div>
              <div class="stat-label">Накопители</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <div class="stat-val">24/7</div>
              <div class="stat-label">Поддержка</div>
            </div>
          </div>
          <div class="hero-actions">
            <router-link to="/order" class="soft-button primary">Арендовать сервер</router-link>
            <a href="#how-it-works" class="soft-button secondary">Как это работает</a>
          </div>
        </div>

        <div class="hero-right">
          <div class="server-window glass">
            <div class="window-bar">
              <div class="win-dot red"></div>
              <div class="win-dot yellow"></div>
              <div class="win-dot green"></div>
              <span class="win-title">CraftHost Panel</span>
            </div>
            <div class="hero-mc-scene">
              <img :src="mcAsset('Grass_Block_JE7_BE6.png')" class="scene-block sb1 pixel-img" alt="">
              <img :src="mcAsset('Bookshelf_JE4_BE2.png')" class="scene-block sb6 pixel-img" alt="">
              <img :src="mcAsset('Crafting_Table_JE4_BE3.png')" class="scene-block sb7 pixel-img" alt="">

              <div class="scene-label">
                <div class="status-dot"></div>
                <span>12 игроков онлайн</span>
              </div>
            </div>
            <div class="server-status-bar">
              <div class="status-dot"></div>
              <span>Сервер активен — версия 1.21.4</span>
            </div>
          </div>
          <div class="floating-items">
            <img :src="mcAsset('Diamond_Sword_JE2_BE2.png')" class="float-item fi-1 pixel-img" alt="">
            <img :src="mcAsset('Emerald_JE3_BE3.png')" class="float-item fi-2 pixel-img" alt="">
            <img :src="mcAsset('Ender_Pearl_JE3_BE2.png')" class="float-item fi-3 pixel-img" alt="">
            <img :src="mcAsset('Golden_Apple_JE2_BE2.png')" class="float-item fi-4 pixel-img" alt="">
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FEATURES ===== -->
    <section id="features" class="features-section container">
      <div class="section-header">
        <h2 class="section-title">Всё что нужно для игры</h2>
        <p class="section-subtitle">Никаких скрытых ограничений — вы получаете полноценный сервер</p>
      </div>

      <div class="features-grid">
        <div class="soft-block diamond">
          <img :src="mcAsset('Diamond_Sword_JE2_BE2.png')" alt="" class="feature-icon pixel-img">
          <h3 class="feature-title">Мощное железо</h3>
          <p class="feature-text">Никаких лагов при прогрузке чанков. Ресурсы строго выделены под ваш сервер на базе новейших процессоров с высокой тактовой частотой.</p>
        </div>

        <div class="soft-block gold">
          <img :src="mcAsset('Knowledge_Book_JE2.png')" alt="" class="feature-icon pixel-img">
          <h3 class="feature-title">Pterodactyl Panel</h3>
          <p class="feature-text">Встроенный файловый менеджер, веб-консоль, автоматические бэкапы и установка ядер в один клик. Доступно с любого устройства.</p>
        </div>

        <div class="soft-block emerald">
          <img :src="mcAsset('Emerald_JE3_BE3.png')" alt="" class="feature-icon pixel-img">
          <h3 class="feature-title">Реферальная система</h3>
          <p class="feature-text">Делитесь ссылкой с друзьями и получайте реальный баланс с каждой их оплаты. 10% начисляется автоматически без ограничений.</p>
        </div>

        <div class="soft-block">
          <img :src="mcAsset('160px-Minecart_with_Chest_JE4_BE2.png')" alt="" class="feature-icon pixel-img">
          <h3 class="feature-title">Автобэкапы</h3>
          <p class="feature-text">Создавайте резервные копии мира в один клик и восстанавливайте их при необходимости. Все бэкапы хранятся на отдельном хранилище.</p>
        </div>

        <div class="soft-block">
          <img :src="mcAsset('Nether_Star_JE2_BE1.png')" alt="" class="feature-icon pixel-img">
          <h3 class="feature-title">Любое ядро</h3>
          <p class="feature-text">Vanilla, Paper, Spigot, Forge, Fabric — устанавливайте любую сборку без лишних настроек. Поддерживаются все актуальные версии Minecraft.</p>
        </div>

        <div class="soft-block">
          <img :src="mcAsset('Amethyst_Shard_JE2_BE1.png')" alt="" class="feature-icon pixel-img">
          <h3 class="feature-title">DDoS-защита</h3>
          <p class="feature-text">Все серверы защищены от DDoS-атак на сетевом уровне. Ваши игроки не заметят ни одной атаки — фильтрация происходит автоматически.</p>
        </div>
      </div>
    </section>

    <!-- ===== HOW IT WORKS ===== -->
    <section id="how-it-works" class="hiw-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Как это работает</h2>
          <p class="section-subtitle">От регистрации до запуска сервера — четыре шага</p>
        </div>

        <div class="steps-row">
          <div class="step-card glass">
            <div class="step-num">01</div>
            <img :src="mcAsset('Book_JE2_BE2.png')" alt="" class="step-img pixel-img">
            <h3 class="step-title">Создайте аккаунт</h3>
            <p class="step-text">Быстрая регистрация без лишних данных. Только email и пароль — и вы уже внутри.</p>
            <router-link to="/register" class="step-link">Зарегистрироваться</router-link>
          </div>

          <div class="step-arrow">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>

          <div class="step-card glass">
            <div class="step-num">02</div>
            <img :src="mcAsset('Gold_Ingot_JE4_BE2.png')" alt="" class="step-img pixel-img">
            <h3 class="step-title">Пополните баланс</h3>
            <p class="step-text">Внесите удобную сумму на счёт через доступные способы оплаты. Деньги зачисляются моментально.</p>
            <router-link to="/dashboard" class="step-link">В кабинет</router-link>
          </div>

          <div class="step-arrow">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>

          <div class="step-card glass">
            <div class="step-num">03</div>
            <img :src="mcAsset('Iron_Pickaxe_JE3_BE2.png')" alt="" class="step-img pixel-img">
            <h3 class="step-title">Выберите тариф</h3>
            <p class="step-text">Подберите план под вашу игровую ситуацию: от небольшого сервера для друзей до крупного проекта.</p>
            <router-link to="/pricing" class="step-link">Смотреть тарифы</router-link>
          </div>

          <div class="step-arrow">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>

          <div class="step-card glass">
            <div class="step-num">04</div>
            <img :src="mcAsset('Grass_Block_JE7_BE6.png')" alt="" class="step-img pixel-img">
            <h3 class="step-title">Играйте</h3>
            <p class="step-text">Сервер поднимается автоматически. Панель управления Pterodactyl открывается сразу после оплаты.</p>
            <span class="step-label">Готово за 60 секунд</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== PANEL SHOWCASE ===== -->
    <section class="panel-section container">
      <div class="panel-inner">
        <div class="panel-text">
          <h2 class="section-title">Pterodactyl Panel — всё под рукой</h2>
          <p class="hero-text" style="max-width:100%;margin:0 0 32px;">
            Профессиональная панель управления, которая используется по всему миру.
            Полный контроль над сервером прямо из браузера — без SSH и командной строки.
          </p>
          <div class="panel-features">
            <div class="pf-item">
              <div class="pf-icon">
                <img :src="mcAsset('Potato_JE3_BE2.png')" class="pixel-img" alt="">
              </div>
              <div>
                <strong>Веб-консоль в реальном времени</strong>
                <p>Вводите команды и видите логи сервера напрямую из браузера без SSH.</p>
              </div>
            </div>
            <div class="pf-item">
              <div class="pf-icon">
                <img :src="mcAsset('Potato_JE3_BE2.png')" class="pixel-img" alt="">
              </div>
              <div>
                <strong>Файловый менеджер</strong>
                <p>Загружайте плагины, редактируйте конфиги и управляйте файлами мира прямо в браузере.</p>
              </div>
            </div>
            <div class="pf-item">
              <div class="pf-icon">
                <img :src="mcAsset('Potato_JE3_BE2.png')" class="pixel-img" alt="">
              </div>
              <div>
                <strong>Расписание задач</strong>
                <p>Настройте автоматические перезапуски и резервное копирование по расписанию.</p>
              </div>
            </div>
            <div class="pf-item">
              <div class="pf-icon">
                <img :src="mcAsset('Potato_JE3_BE2.png')" class="pixel-img" alt="">
              </div>
              <div>
                <strong>Мониторинг ресурсов</strong>
                <p>Следите за потреблением CPU и RAM в реальном времени. Никаких сюрпризов с производительностью.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="panel-visual">
          <div class="glass panel-card">
            <div class="window-bar">
              <div class="win-dot red"></div>
              <div class="win-dot yellow"></div>
              <div class="win-dot green"></div>
              <span class="win-title">Pterodactyl — my-server</span>
            </div>
            <div class="panel-mock">
              <div class="mock-sidebar">
                <div class="mock-menu-item active">Консоль</div>
                <div class="mock-menu-item">Файлы</div>
                <div class="mock-menu-item">Базы данных</div>
                <div class="mock-menu-item">Бэкапы</div>
                <div class="mock-menu-item">Расписание</div>
                <div class="mock-menu-item">Настройки</div>
              </div>
              <div class="mock-console">
                <div class="console-line"><span class="cl-time">[10:42:01]</span> <span class="cl-info">INFO</span>: Server starting...</div>
                <div class="console-line"><span class="cl-time">[10:42:03]</span> <span class="cl-info">INFO</span>: Loading world "world"</div>
                <div class="console-line"><span class="cl-time">[10:42:05]</span> <span class="cl-ok">DONE</span>: Server online!</div>
                <div class="console-line"><span class="cl-time">[10:42:10]</span> <span class="cl-info">INFO</span>: Player_1 joined the game</div>
                <div class="console-line"><span class="cl-time">[10:42:41]</span> <span class="cl-info">INFO</span>: Player_2 joined the game</div>
                <div class="console-cursor">_</div>
              </div>
            </div>
            <div class="mock-stats">
              <div class="mock-stat">
                <div class="ms-label">CPU</div>
                <div class="ms-bar"><div class="ms-fill cpu"></div></div>
                <div class="ms-val">23%</div>
              </div>
              <div class="mock-stat">
                <div class="ms-label">RAM</div>
                <div class="ms-bar"><div class="ms-fill ram"></div></div>
                <div class="ms-val">1.4 / 4 GB</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== SPECS ===== -->
    <section class="specs-section container">
      <div class="section-header">
        <h2 class="section-title">Технические характеристики</h2>
        <p class="section-subtitle">Железо, которое обеспечивает стабильную игру без лагов</p>
      </div>

      <div class="specs-grid">
        <div class="spec-card glass">
          <img :src="mcAsset('Potato_JE3_BE2.png')" class="spec-icon pixel-img" alt="">
          <h4 class="spec-name">Процессор</h4>
          <div class="spec-val">AMD EPYC / Intel Xeon</div>
          <p class="spec-desc">Высокая тактовая частота — ключевой параметр производительности Minecraft. Чанки прогружаются мгновенно.</p>
        </div>

        <div class="spec-card glass">
          <img :src="mcAsset('Potato_JE3_BE2.png')" class="spec-icon pixel-img" alt="">
          <h4 class="spec-name">Накопитель</h4>
          <div class="spec-val">NVMe SSD</div>
          <p class="spec-desc">Скорость записи на уровне 3500 МБ/с. Мир загружается быстро, бэкапы создаются без остановки сервера.</p>
        </div>

        <div class="spec-card glass">
          <img :src="mcAsset('Potato_JE3_BE2.png')" class="spec-icon pixel-img" alt="">
          <h4 class="spec-name">Оперативная память</h4>
          <div class="spec-val">DDR4 ECC</div>
          <p class="spec-desc">Память с защитой от ошибок ECC. Ваши данные в безопасности даже при максимальной нагрузке.</p>
        </div>

        <div class="spec-card glass">
          <img :src="mcAsset('Potato_JE3_BE2.png')" class="spec-icon pixel-img" alt="">
          <h4 class="spec-name">Сеть</h4>
          <div class="spec-val">1 Гбит/с порт</div>
          <p class="spec-desc">Гигабитный канал на каждый сервер. Низкие пинги для игроков из России и СНГ. DDoS-защита включена.</p>
        </div>
      </div>
    </section>

    <!-- ===== ПРЕИМУЩЕСТВА ===== -->
    <section id="advantages" class="advantages-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Почему выбирают CraftHost</h2>
          <p class="section-subtitle">Мы создаём лучший опыт для владельцев Minecraft-серверов</p>
        </div>

        <div class="adv-grid">
          <div class="adv-card glass">
            <div class="adv-num">01</div>
            <img :src="mcAsset('Diamond_Sword_JE2_BE2.png')" class="adv-icon pixel-img" alt="">
            <h3 class="adv-title">Моментальный запуск</h3>
            <p class="adv-text">Сервер разворачивается за 60 секунд после оплаты. Никаких ожиданий, ручных настроек и писем в поддержку — всё автоматизировано.</p>
          </div>

          <div class="adv-card glass">
            <div class="adv-num">02</div>
            <img :src="mcAsset('Emerald_JE3_BE3.png')" class="adv-icon pixel-img" alt="">
            <h3 class="adv-title">Прозрачная тарификация</h3>
            <p class="adv-text">Посуточная оплата без скрытых комиссий. Платите ровно за те дни, которые используете. Баланс расходуется только когда сервер работает.</p>
          </div>

          <div class="adv-card glass">
            <div class="adv-num">03</div>
            <img :src="mcAsset('Nether_Star_JE2_BE1.png')" class="adv-icon pixel-img" alt="">
            <h3 class="adv-title">Выделенные ресурсы</h3>
            <p class="adv-text">CPU и RAM строго зарезервированы под ваш сервер. Никакого оверселлинга — ваши игроки не ощутят соседей по ноде.</p>
          </div>

          <div class="adv-card glass">
            <div class="adv-num">04</div>
            <img :src="mcAsset('Golden_Apple_JE2_BE2.png')" class="adv-icon pixel-img" alt="">
            <h3 class="adv-title">Полный контроль</h3>
            <p class="adv-text">Панель Pterodactyl, веб-консоль, файловый менеджер, бэкапы, расписания — всё из браузера. Полный root-доступ к серверу без SSH.</p>
          </div>

          <div class="adv-card glass">
            <div class="adv-num">05</div>
            <img :src="mcAsset('Amethyst_Shard_JE2_BE1.png')" class="adv-icon pixel-img" alt="">
            <h3 class="adv-title">Защита от DDoS</h3>
            <p class="adv-text">Сетевая фильтрация L7 включена на всех тарифах бесплатно. Атаки блокируются автоматически, сервер продолжает работать без перебоев.</p>
          </div>

          <div class="adv-card glass">
            <div class="adv-num">06</div>
            <img :src="mcAsset('Ender_Pearl_JE3_BE2.png')" class="adv-icon pixel-img" alt="">
            <h3 class="adv-title">Живая поддержка 24/7</h3>
            <p class="adv-text">Среднее время ответа — 15 минут. Поддержка через встроенный чат, тикет-систему и Discord. Помогаем с настройкой и решением любых проблем.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="cta-section container">
      <div class="cta-inner glass">
        <div class="cta-blocks-left">
          <img :src="mcAsset('Grass_Block_JE7_BE6.png')" class="cta-block pixel-img" alt="">
          <img :src="mcAsset('Grass_Block_JE7_BE6.png')" class="cta-block pixel-img" alt="">
          <img :src="mcAsset('Grass_Block_JE7_BE6.png')" class="cta-block pixel-img" alt="">
        </div>
        <div class="cta-content">
          <h2 class="cta-title">Готовы начать?</h2>
          <p class="cta-text">Зарегистрируйтесь сейчас и запустите свой первый сервер за 60 секунд.</p>
          <div class="hero-actions" style="justify-content:center;">
            <router-link to="/register" class="soft-button primary">Создать аккаунт</router-link>
            <router-link to="/pricing" class="soft-button secondary">Посмотреть тарифы</router-link>
          </div>
        </div>
        <div class="cta-blocks-right">
          <img :src="mcAsset('Grass_Block_JE7_BE6.png')" class="cta-block pixel-img" alt="">
          <img :src="mcAsset('Grass_Block_JE7_BE6.png')" class="cta-block pixel-img" alt="">
          <img :src="mcAsset('Grass_Block_JE7_BE6.png')" class="cta-block pixel-img" alt="">
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { mcAsset } from '../utils/assets'
</script>

<style scoped>
.home-page { padding-bottom: 100px; }

/* ---- HERO ---- */
.hero-section { padding: 80px 0 60px; }
.hero-inner { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
.hero-left { display: flex; flex-direction: column; }

.badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(0, 230, 118, 0.1); color: var(--mc-emerald);
  padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;
  margin-bottom: 24px; border: 1px solid rgba(0, 230, 118, 0.2); width: fit-content;
}
.inline-icon { width: 18px; height: 18px; }

.hero-title { font-size: 50px; line-height: 1.1; margin: 0 0 20px; }
.highlight {
  background:  #00e676 100%;
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.hero-text { font-size: 17px; color: var(--text-muted); max-width: 520px; line-height: 1.7; margin: 0 0 32px; }

.hero-stats { display: flex; align-items: center; gap: 20px; margin-bottom: 32px; }
.stat-item { text-align: center; }
.stat-val { font-size: 22px; font-weight: 800; color: var(--mc-emerald); }
.stat-label { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
.stat-divider { width: 1px; height: 36px; background: rgba(255,255,255,0.1); }
.hero-actions { display: flex; gap: 16px; }

/* Hero right */
.hero-right { position: relative; }
.server-window { padding: 0; overflow: hidden; }
.window-bar {
  display: flex; align-items: center; gap: 6px;
  padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.07);
}
.win-dot { width: 12px; height: 12px; border-radius: 50%; }
.win-dot.red { background: #ff5f57; }
.win-dot.yellow { background: #ffbd2e; }
.win-dot.green { background: #28c840; }
.win-title { font-size: 12px; color: var(--text-muted); margin-left: 8px; }

/* MC scene inside hero window */
.hero-mc-scene {
  position: relative; height: 180px; overflow: hidden;
  background: linear-gradient(180deg, #87ceeb 0%, #87ceeb 60%, #4a7c59 60%, #4a7c59 100%);
  display: flex; align-items: flex-end; justify-content: center; gap: 0; padding-bottom: 0;
}
.scene-block {
  width: 52px; height: 52px; position: absolute; bottom: 0;
  filter: drop-shadow(0 -4px 8px rgba(0,0,0,0.4));
}
.sb1 { left: 0%; bottom: 0; }
.sb2 { left: 10%; bottom: 0; }
.sb3 { left: 20%; bottom: 0; }
.sb4 { left: 30%; bottom: 0; animation: floatY 4s ease-in-out infinite alternate; }
.sb5 { left: 42%; bottom: 0; animation: floatY 5s ease-in-out infinite alternate; animation-delay: -1s; }
.sb6 { left: 54%; bottom: 0; }
.sb7 { left: 66%; bottom: 0; }
.sb8 { left: 78%; bottom: 0; }
.scene-label {
  position: absolute; top: 12px; right: 12px;
  display: flex; align-items: center; gap: 6px;
  background: rgba(0,0,0,0.5); border-radius: 20px; padding: 4px 12px;
  font-size: 12px; color: #fff; backdrop-filter: blur(8px);
}

.server-status-bar {
  display: flex; align-items: center; gap: 8px;
  padding: 10px 16px; font-size: 13px; color: var(--text-muted);
  border-top: 1px solid rgba(255,255,255,0.07);
}
.status-dot {
  width: 8px; height: 8px; border-radius: 50%; background: var(--mc-emerald);
  box-shadow: 0 0 8px rgba(0,230,118,0.6); animation: pulse 2s infinite;
}
@keyframes pulse { 0%,100% { opacity:1 } 50% { opacity:0.4 } }

.floating-items { position: absolute; inset: 0; pointer-events: none; }
.float-item { position: absolute; width: 38px; height: 38px; }
.fi-1 { top: -16px; right: 60px; animation: floatY 5s ease-in-out infinite alternate; }
.fi-2 { bottom: -50px; left: 24px; animation: floatY 6s ease-in-out infinite alternate; animation-delay: -2s; }
.fi-3 { top: 40%; right: -18px; animation: floatY 7s ease-in-out infinite alternate; animation-delay: -1s; }
.fi-4 { top: -28px; left: 44px; animation: floatY 5.5s ease-in-out infinite alternate; animation-delay: -3s; }
@keyframes floatY { 0% { transform: translateY(0) } 100% { transform: translateY(-14px) } }

/* ---- FEATURES ---- */
.features-section { padding: 80px 0; }
.section-header { text-align: center; margin-bottom: 48px; }
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
.feature-icon { width: 52px; height: 52px; margin-bottom: 20px; filter: drop-shadow(0 8px 16px rgba(0,0,0,0.5)); }
.feature-title { font-size: 20px; margin: 0 0 10px; font-weight: 700; }
.feature-text { color: var(--text-muted); margin: 0; font-size: 15px; line-height: 1.6; }
.soft-block.gold:hover { border-color: var(--mc-gold); box-shadow: 0 15px 30px rgba(255,196,0,0.15); }

/* ---- HOW IT WORKS ---- */
.hiw-section {
  padding: 80px 0;
  background: linear-gradient(180deg, transparent 0%, rgba(0,230,118,0.03) 50%, transparent 100%);
}
.steps-row { display: flex; align-items: stretch; gap: 0; }
.step-card {
  flex: 1; padding: 32px 24px; text-align: center;
  display: flex; flex-direction: column; align-items: center; gap: 12px;
}
.step-num {
  font-family: var(--font-pixel); font-size: 11px; color: var(--mc-emerald);
  background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.2);
  padding: 6px 12px; border-radius: 8px;
}
.step-img { width: 56px; height: 56px; filter: drop-shadow(0 8px 14px rgba(0,0,0,0.5)); }
.step-title { font-size: 18px; font-weight: 700; margin: 0; }
.step-text { font-size: 14px; color: var(--text-muted); margin: 0; line-height: 1.6; flex: 1; }
.step-link {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 8px 20px; border-radius: var(--radius-md);
  background: rgba(0,230,118,0.1); color: var(--mc-emerald);
  font-size: 13px; font-weight: 700; border: 1px solid rgba(0,230,118,0.2); transition: 0.2s;
}
.step-link:hover { background: rgba(0,230,118,0.2); }
.step-label {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 8px 20px; border-radius: var(--radius-md);
  background: rgba(0,229,255,0.1); color: var(--mc-diamond);
  font-size: 13px; font-weight: 700; border: 1px solid rgba(0,229,255,0.2);
}
.step-arrow { display: flex; align-items: center; justify-content: center; color: var(--text-muted); padding: 0 6px; flex-shrink: 0; }

/* ---- PANEL SHOWCASE ---- */
.panel-section { padding: 80px 0; }
.panel-inner { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
.panel-features { display: flex; flex-direction: column; gap: 20px; }
.pf-item { display: flex; align-items: flex-start; gap: 16px; }
.pf-icon {
  width: 44px; height: 44px; flex-shrink: 0;
  background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
}
.pf-icon img { width: 28px; height: 28px; }
.pf-item strong { display: block; font-size: 15px; margin-bottom: 4px; }
.pf-item p { margin: 0; font-size: 14px; color: var(--text-muted); line-height: 1.5; }

/* Panel mock */
.panel-card { padding: 0; overflow: hidden; }
.panel-mock { display: flex; min-height: 220px; }
.mock-sidebar { width: 110px; flex-shrink: 0; background: rgba(0,0,0,0.2); border-right: 1px solid rgba(255,255,255,0.06); padding: 12px 0; }
.mock-menu-item { padding: 9px 14px; font-size: 12px; color: var(--text-muted); cursor: default; }
.mock-menu-item.active { color: var(--mc-emerald); background: rgba(0,230,118,0.08); }
.mock-console { flex: 1; padding: 14px; background: rgba(0,0,0,0.15); font-family: 'Courier New', monospace; font-size: 12px; display: flex; flex-direction: column; gap: 6px; }
.console-line { display: flex; gap: 8px; }
.cl-time { color: #555; }
.cl-info { color: var(--mc-diamond); }
.cl-ok { color: var(--mc-emerald); }
.console-cursor { color: var(--mc-emerald); margin-top: 4px; animation: blink 1s step-end infinite; }
@keyframes blink { 0%,100% { opacity:1 } 50% { opacity:0 } }
.mock-stats { display: flex; flex-direction: column; gap: 10px; padding: 14px 16px; border-top: 1px solid rgba(255,255,255,0.06); }
.mock-stat { display: flex; align-items: center; gap: 10px; font-size: 12px; }
.ms-label { width: 30px; color: var(--text-muted); }
.ms-bar { flex: 1; height: 6px; background: rgba(255,255,255,0.08); border-radius: 3px; overflow: hidden; }
.ms-fill { height: 100%; border-radius: 3px; }
.ms-fill.cpu { width: 23%; background: var(--mc-diamond); }
.ms-fill.ram { width: 35%; background: var(--mc-emerald); }
.ms-val { font-size: 12px; color: var(--text-muted); width: 70px; text-align: right; }

/* ---- SPECS ---- */
.specs-section { padding: 80px 0; }
.specs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
.spec-card { padding: 28px 24px; }
.spec-icon { width: 44px; height: 44px; margin-bottom: 16px; }
.spec-name { font-size: 13px; color: var(--text-muted); font-weight: 600; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.spec-val { font-size: 20px; font-weight: 800; color: var(--mc-diamond); margin-bottom: 12px; }
.spec-desc { font-size: 14px; color: var(--text-muted); margin: 0; line-height: 1.6; }

/* ---- ADVANTAGES ---- */
.advantages-section {
  padding: 80px 0;
  background: linear-gradient(180deg, transparent 0%, rgba(0,229,255,0.03) 50%, transparent 100%);
}
.adv-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
.adv-card {
  padding: 32px 28px; position: relative; overflow: hidden;
  transition: border-color 0.3s, box-shadow 0.3s, transform 0.3s;
}
.adv-card:hover { border-color: rgba(0,229,255,0.25); box-shadow: 0 12px 32px rgba(0,229,255,0.08); transform: translateY(-4px); }
.adv-num {
  position: absolute; top: 20px; right: 24px;
  font-family: var(--font-pixel); font-size: 11px; color: rgba(0,229,255,0.25);
  background: rgba(0,229,255,0.05); border: 1px solid rgba(0,229,255,0.1);
  padding: 4px 10px; border-radius: 8px;
}
.adv-icon { width: 48px; height: 48px; margin-bottom: 20px; filter: drop-shadow(0 8px 14px rgba(0,0,0,0.5)); }
.adv-title { font-size: 19px; font-weight: 700; margin: 0 0 10px; }
.adv-text { color: var(--text-muted); font-size: 14px; line-height: 1.7; margin: 0; }

/* ---- CTA ---- */
.cta-section { padding: 60px 0 0; }
.cta-inner { padding: 60px 40px; display: flex; align-items: center; gap: 32px; overflow: hidden; }
.cta-content { flex: 1; text-align: center; }
.cta-title { font-size: 36px; font-weight: 800; margin: 0 0 12px; }
.cta-text { color: var(--text-muted); font-size: 17px; margin: 0 0 32px; }
.cta-blocks-left, .cta-blocks-right { display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; opacity: 0.5; }
.cta-block { width: 50px; height: 50px; }

/* ---- RESPONSIVE ---- */
@media (max-width: 1024px) {
  .hero-section { padding: 50px 0 40px; }
  .hero-inner { grid-template-columns: 1fr; gap: 30px; }
  .hero-right { display: none; }

  /* Центрируем весь контент hero */
  .hero-left { align-items: center; text-align: center; }
  .badge { margin-left: auto; margin-right: auto; }
  .hero-title { font-size: 40px; }
  .hero-text { margin-left: auto; margin-right: auto; }
  .hero-stats { justify-content: center; }
  .hero-actions {
    flex-direction: column;
    width: 100%; max-width: 320px;
    margin-left: auto; margin-right: auto;
  }
  .hero-actions .soft-button { width: 100%; }

  .panel-inner { grid-template-columns: 1fr; }
  .steps-row { flex-direction: column; align-items: center; }
  .step { text-align: center; }
  .step-arrow { transform: rotate(90deg); padding: 4px 0; }
  .cta-blocks-left, .cta-blocks-right { display: none; }
}

@media (max-width: 768px) {
  .hero-title { font-size: 32px; }
  .hero-text { font-size: 15px; }
  .hero-stats { gap: 16px; }
  .stat-val { font-size: 18px; }
}

@media (max-width: 480px) {
  .hero-title { font-size: 26px; }
  .hero-stats { gap: 12px; }
}
</style>
