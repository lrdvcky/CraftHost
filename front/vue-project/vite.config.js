import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  server: {
    proxy: {
      '/api': 'http://localhost:8000',
    },
  },
  build: {
    cssCodeSplit: true,
    chunkSizeWarningLimit: 800,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('vue-router')) return 'router'
            if (id.includes('/vue/') || id.includes('@vue')) return 'vue'
            if (id.includes('pinia')) return 'pinia'
            if (id.includes('axios')) return 'axios'
            return 'vendor'
          }
        },
      },
    },
  },
})
