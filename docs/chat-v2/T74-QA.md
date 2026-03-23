# T74 — QA evidence (країна: combobox + вкладки профілю)

## Scope

- Статичні дані: `backend/resources/data/iso3166-alpha2-uk.json` (ISO 3166-1 alpha-2 + назви uk).
- `RpCountryCombobox` у `resources/js/components/ui/` — пошук по назві/коду, ↑↓, Enter, Escape, список через portal + `position: fixed` (не обрізається `overflow` модалки).
- `UserProfileModal` — поле країни через combobox; вкладки в обгортці `rp-profile-modal-tabs` (токени `--rp-chat-*`).
- `StaffUsersView` — те саме поле країни для адміна.
- API: `profile.country` — лише код з канонічного списку (uppercase); `prepareForValidation` / merge у staff; OpenAPI оновлено.

## Автоматичні перевірки

```bash
cd backend && php artisan test --filter=MeProfileApiTest
cd backend && php artisan test --filter=StaffUserManagementApiTest
cd backend && npm run test:country-filter
cd backend && npm run build
```

Скрипт `test:country-filter` див. `package.json` (Node test для `rpCountryComboboxFilter.js`).

Очікування: усі зазначені тести та збірка — PASS.

## Ручний сценарій (опційно)

1. Профіль → вкладки виглядають як «чатовий» хром (світла/темна тема).
2. Країна: фокус у полі — список; введення «ук» → Україна; Enter — обрано; збереження — у відповіді `UA`.
3. Кнопка «×» — очищує країну (PATCH з `null`).

## Вердикт

PASS після успішного виконання команд вище.
