/**
 * Возвращает URL картинки из public/assets/minecraft/ как строку.
 *
 * Зачем это нужно:
 * Если в шаблоне написать <img src="/assets/minecraft/foo.png">, компилятор
 * Vue по умолчанию (transformAssetUrls) попытается превратить путь в
 * import-модуль и собрать его в бандл. Для файлов из public/ это неверно —
 * они отдаются дев-сервером/проддом как статика по URL, а не импортируются.
 * Из-за этого Vite падает с "Failed to resolve import".
 *
 * Биндинг через :src="mcAsset('foo.png')" передаёт компилятору ВЫРАЖЕНИЕ
 * (а не строковый литерал), поэтому transformAssetUrls его не трогает,
 * и путь остаётся обычным runtime-URL.
 *
 * import.meta.env.BASE_URL учитывает vite `base` (если он будет задан),
 * по умолчанию это '/'.
 */
export function mcAsset(filename) {
  const base = import.meta.env.BASE_URL || '/'
  // base уже заканчивается на '/', поэтому конкатенация безопасна.
  return `${base}assets/minecraft/${filename}`
}
