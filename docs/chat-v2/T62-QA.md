# T62 — QA: UI primitives (`Rp*`)

## Scope (перша хвиля)

- Каталог `backend/resources/js/components/ui/` з `RpButton`, `RpTextField`, `RpPanel`, `RpBanner` і коротким `README.md`.
- Глобальна реєстрація в `backend/resources/js/app.js`.
- Стилі `.rp-btn-secondary`, `.rp-btn-danger`, `.rp-btn-outline`, `.rp-btn--pending` у `backend/resources/css/app.css`.
- Міграція розмітки без зміни бізнес-логіки:
  - `StaffStopWordsView.vue`, `StaffFlaggedMessagesView.vue`, `ArchiveChat.vue`
  - `ConfirmDialogModal.vue`, `PrivateChatPanel.vue`

## Автоматичні перевірки

| Команда | Результат |
|--------|-----------|
| `npm run build` (у `backend/`) | PASS |
| `npm run test:msg-parse` | PASS |
| `php artisan test` | PASS (212 тестів) |

## Візуальний / a11y чекліст (оператор)

- [ ] Staff stop-words / flagged / archive: тема (ghost), primary/ghost кнопки, панелі та банери виглядають як раніше; фокус Tab і `focus-visible` на кнопках і полях.
- [ ] Модал підтвердження: «Скасувати» (outline), «Підтвердити» (danger), початковий фокус на `data-rp-initial-focus`.
- [ ] Приват: кнопка «Надіслати» — primary, disabled коли порожньо.

## Follow-up (не в цьому PR)

- `StaffUsersView.vue`, `ChatRoom.vue`, інші staff-модалі — поступова заміна на `Rp*`.
- Окремі `RpSelect` / `RpTextarea` за потреби.
- Storybook / візуальні снапшоти — за рішенням команди.
