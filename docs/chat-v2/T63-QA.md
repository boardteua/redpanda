# T63 — QA: каталог смайлів (CRUD, парсер `:code:`)

## Вердикт

**PASS** (автоматизовані перевірки; ручні скріни — за оператором).

## Доказ

- **PHPUnit:** `php artisan test` — усі тести зелені; зокрема `Tests\Feature\ChatEmoticonApiTest` (публічний список лише активні + сортування, 403 не-адміна на `mod/emoticons`, створення з upload + 422 на дубль `code`, PATCH `is_active` + DELETE з прибиранням файлу).
- **Парсер (Node):** `node --test resources/js/utils/chatMessageBodyParse.test.mjs` — 22/22 PASS, включно з сегментом `emoticon`, невідомим `:code:` і поєднанням з URL-зображенням.
- **Збірка SPA:** `npm run build` — без помилок.

## Потік (коротко)

1. Адмін відкриває «Налаштування чату» → блок **Каталог смайлів**: таблиця, додавання multipart (`POST /api/v1/mod/emoticons`), увімк/вимк (`PATCH`), видалення (`DELETE`).
2. Клієнт завантажує `GET /api/v1/chat/emoticons` при вході в чат / архів; індекс передається в `parseChatMessageBody` для `:code:`.
3. Модал смайлів у композері тягне той самий публічний список; прев’ю — `/emoticon/{file}`.

## Імпорт наявних файлів

```bash
php artisan chat:import-emoticons
php artisan chat:import-emoticons --dry-run
```

Сканує `public/emoticon/*.gif|png|webp`, пропускає вже наявні `code`.

## Ручний сценарій (опційно)

- Адмін: додати смайл → відкрити модал у композері → з’являється в сітці; надіслати `:код:` у кімнаті → у стрічці inline-зображення.
- Не-адмін: `GET /api/v1/mod/emoticons` → 403.
