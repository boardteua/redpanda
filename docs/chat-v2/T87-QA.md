# T87 — Інформаційні попапи (toast) + композер — QA

**Вердикт:** PASS

## Доказ

- **Фронт:** `npm run build` у `backend/` — PASS.
- **Бекенд:** `php artisan test` — PASS (регресія; зміни лише у SPA/CSS).

## Що зроблено

- **`utils/rpToastStack.js`:** `Vue.observable` стек повідомлень; `showError` / `showWarning` (автозникнення за таймером, кнопка закриття) / `showProgress` (`setPercent`, `done`); обмеження кількості видимих тостів.
- **`components/ui/RpToastStack.vue`:** портал унизу екрана, `role="alert"` + `aria-live="assertive"` для помилок, `status` + `polite` для інших; прогрес — смуга або indeterminate анімація; `z-index: 230`.
- **`App.vue`:** підключено `RpToastStack`.
- **`app.css`:** стилі `.rp-toast-*`.
- **`ChatRoomComposer.vue`:** клієнтська валідація розміру/типу (T86) → `showError`; paste під час відправки → `showWarning`; upload → `showProgress` + `onUploadProgress` (Axios).
- **`ChatRoomComposerAttachmentPreviews.vue`:** прибрано дубль тексту «Завантаження…» та inline `role="alert"` для помилок (оголошення через toast).

## Ручний чек (оператор)

- Файл понад лімітом → toast помилки, фокус лишається в textarea.
- Успішний upload → прогрес зникає, прев’ю в композері без дублювання помилки в стрічці прев’ю.
