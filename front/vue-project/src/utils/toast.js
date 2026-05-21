import { ref } from 'vue'

export const toasts = ref([])

export function useToast() {
  const showToast = (message, type = 'success') => {
    const id = Date.now()
    toasts.value.push({ id, message, type })
    
    // Автоматически удаляем уведомление через 3.5 секунды
    setTimeout(() => {
      toasts.value = toasts.value.filter(t => t.id !== id)
    }, 3500)
  }

  return { showToast }
}