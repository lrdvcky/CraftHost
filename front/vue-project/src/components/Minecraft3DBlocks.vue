<template>
  <div class="scene-container" aria-hidden="true">
    <!-- Генерируем 7 блоков -->
    <div class="iso-block-wrapper" v-for="i in 7" :key="i" :class="'pos-' + i">
      <!-- Идеально сглаженный векторный SVG блок земли -->
      <svg viewBox="0 0 100 110" class="iso-block" xmlns="http://www.w3.org/2000/svg">
        <!-- Левая грань (Земля светлая) -->
        <polygon points="10,30 50,50 50,100 10,80" fill="#6a4b35"/>
        <!-- Правая грань (Земля темная) -->
        <polygon points="50,50 90,30 90,80 50,100" fill="#4a3424"/>
        
        <!-- Свисающая трава (левая) -->
        <polygon points="10,30 50,50 50,62 40,52 30,62 20,48 10,55" fill="#4ea84e"/>
        <!-- Свисающая трава (правая) -->
        <polygon points="50,50 90,30 90,42 80,52 70,42 60,56 50,48" fill="#3f8a3f"/>
        
        <!-- Верхняя грань (Трава) -->
        <polygon points="50,10 90,30 50,50 10,30" fill="#5cbd5c"/>
      </svg>
    </div>
  </div>
</template>

<style scoped>
.scene-container {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.iso-block-wrapper {
  position: absolute;
  /* Аппаратное ускорение видеокартой для идеально плавной анимации без лагов */
  will-change: transform; 
  animation: float-up-down ease-in-out infinite alternate;
}

.iso-block {
  width: 100%;
  height: auto;
  display: block;
  /* Легкая тень под блоками для объема */
  filter: drop-shadow(0 15px 15px rgba(0,0,0,0.4));
}

/* 
  Позиции, размеры, скорость анимации и размытие (глубина резкости) 
*/
.pos-1 { top: 15%; left: 8%; width: 90px; animation-duration: 8s; }
.pos-2 { top: 65%; right: 12%; width: 120px; animation-duration: 10s; animation-delay: -2s; }

/* Блоки на заднем фоне (размытые) */
.pos-3 { bottom: 10%; left: 20%; width: 60px; animation-duration: 9s; animation-delay: -5s; filter: blur(3px); opacity: 0.6; }
.pos-4 { top: 25%; right: 25%; width: 50px; animation-duration: 12s; animation-delay: -1s; filter: blur(4px); opacity: 0.5; }
.pos-5 { top: 80%; left: 10%; width: 70px; animation-duration: 11s; animation-delay: -4s; filter: blur(2px); opacity: 0.8;}

/* Огромный блок на переднем плане */
.pos-6 { bottom: -5%; right: -2%; width: 250px; animation-duration: 15s; filter: blur(6px); opacity: 0.3;}
.pos-7 { top: -5%; left: 40%; width: 45px; animation-duration: 14s; filter: blur(5px); opacity: 0.4;}

/* Элегантное левитирование */
@keyframes float-up-down {
  0% { transform: translateY(0); }
  100% { transform: translateY(-40px); } /* Блок поднимается на 40px */
}
</style>