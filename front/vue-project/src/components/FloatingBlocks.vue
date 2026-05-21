<template>
  <div class="floating-scene" aria-hidden="true">
    <div class="cube mint c1"></div>
    <div class="cube sky c2"></div>
    <div class="cube lavender c3"></div>
    <div class="cube peach c4"></div>
    <div class="cube sky c5"></div>
    <div class="cube mint c6"></div>
    <div class="blur-orb orb1"></div>
    <div class="blur-orb orb2"></div>
    <div class="blur-orb orb3"></div>
  </div>
</template>

<style scoped>
.floating-scene {
  position: fixed;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 0;
}

.cube {
  position: absolute;
  width: 82px;
  height: 82px;
  border-radius: 22px;
  transform-style: preserve-3d;
  box-shadow:
    0 20px 40px rgba(117, 164, 207, 0.15),
    inset 0 1px 0 rgba(255,255,255,0.95);
  animation: floatY 9s ease-in-out infinite;
}

.cube::before,
.cube::after {
  content: "";
  position: absolute;
  border-radius: 20px;
}

.cube::before {
  inset: 0;
  transform: translateZ(0);
}

.cube::after {
  inset: 8px;
  opacity: .45;
  background:
    linear-gradient(90deg, rgba(255,255,255,.45) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.35) 50%, rgba(255,255,255,.35) 75%, transparent 75%);
  background-size: 18px 18px;
}

.mint {
  background: linear-gradient(135deg, #e4fff0 0%, #baf3d2 55%, #8ee1b6 100%);
  border: 1px solid #a7e8c6;
}

.sky {
  background: linear-gradient(135deg, #eff9ff 0%, #bfe5ff 55%, #89cff7 100%);
  border: 1px solid #a8dafb;
}

.lavender {
  background: linear-gradient(135deg, #f6f2ff 0%, #d8cbff 55%, #baa8ff 100%);
  border: 1px solid #cebefc;
}

.peach {
  background: linear-gradient(135deg, #fff4ed 0%, #ffd8c2 55%, #ffbc97 100%);
  border: 1px solid #ffcbaa;
}

.c1 { top: 120px; left: 5%; animation-delay: 0s; }
.c2 { top: 220px; right: 10%; animation-delay: 1s; width: 110px; height: 110px; }
.c3 { top: 60%; left: 7%; animation-delay: 2s; width: 70px; height: 70px; }
.c4 { bottom: 12%; right: 8%; animation-delay: 3s; width: 92px; height: 92px; }
.c5 { top: 18%; left: 48%; animation-delay: 1.6s; width: 60px; height: 60px; }
.c6 { bottom: 18%; left: 46%; animation-delay: 2.8s; width: 74px; height: 74px; }

.blur-orb {
  position: absolute;
  border-radius: 999px;
  filter: blur(60px);
  opacity: .45;
}

.orb1 {
  width: 320px;
  height: 320px;
  background: #d2edff;
  top: -60px;
  right: -60px;
}

.orb2 {
  width: 280px;
  height: 280px;
  background: #e3d8ff;
  bottom: 10%;
  left: -80px;
}

.orb3 {
  width: 240px;
  height: 240px;
  background: #dff9ea;
  top: 45%;
  right: 20%;
}

@keyframes floatY {
  0%, 100% { transform: translateY(0) rotate(-6deg); }
  50% { transform: translateY(-18px) rotate(6deg); }
}
</style>