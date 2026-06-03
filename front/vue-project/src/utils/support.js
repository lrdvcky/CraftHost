import { ref } from 'vue'

// Глобальное состояние плавающего виджета поддержки.
// SupportWidget.vue читает этот флаг, а любой компонент может его открыть.
export const supportOpen = ref(false)

export function openSupport() {
  supportOpen.value = true
}

export function closeSupport() {
  supportOpen.value = false
}

export function toggleSupport() {
  supportOpen.value = !supportOpen.value
}
