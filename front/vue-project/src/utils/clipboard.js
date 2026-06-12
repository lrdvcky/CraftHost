// Надёжное копирование в буфер обмена.
// navigator.clipboard работает только в защищённом контексте (https / localhost),
// поэтому по http:// используем запасной вариант через скрытую textarea + execCommand.
export async function copyToClipboard(text) {
  const value = String(text ?? '').trim()
  if (!value) return false

  if (navigator.clipboard && window.isSecureContext) {
    try {
      await navigator.clipboard.writeText(value)
      return true
    } catch { /* падаем в запасной вариант ниже */ }
  }

  try {
    const ta = document.createElement('textarea')
    ta.value = value
    ta.setAttribute('readonly', '')
    ta.style.position = 'fixed'
    ta.style.top = '-9999px'
    ta.style.opacity = '0'
    document.body.appendChild(ta)
    ta.focus()
    ta.select()
    ta.setSelectionRange(0, value.length)
    const ok = document.execCommand('copy')
    document.body.removeChild(ta)
    return ok
  } catch {
    return false
  }
}
