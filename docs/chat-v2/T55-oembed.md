# T55 — Backend oEmbed proxy

## Потік запиту

1. Клієнт (автентифікований, Sanctum SPA) викликає `GET /api/v1/oembed?url=...` з опційними `maxwidth`, `maxheight`.
2. Бекенд валідує довжину та схему `url` (`http` / `https`, без credentials).
3. **SSRF (вхідний URL):** хост має бути «публічним» — літеральні IP проходять `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`; для імені хоста виконується DNS (`A` / `AAAA`) і **кожна** отримана адреса має бути публічною. Інакше **422** (узагальнене повідомлення для клієнта).
4. **Реєстр провайдерів:** `backend/data/oembed-providers.json` (копія з npm [`oembed-providers`](https://www.npmjs.com/package/oembed-providers), див. `backend/data/oembed-providers.source.txt`). До масиву додаються **кураторські** записи (наприклад **Threads** → `graph.threads.net`, short TikTok — див. **T118** у `docs/chat-v2/T118-QA.md`). Для `url` шукається перший `endpoint`, у якого одна з `schemes` збігається з нормалізованим URL (wildcard `*` у схемі → `.*` у regex, порівняння без урахування регістру схеми/хоста).
5. Якщо провайдера немає — **422**, зовнішній HTTP не виконується.
6. **Вихідний запит** — лише GET на `endpoint.url` з реєстру з параметрами `url`, `format=json`, опційно `maxwidth` / `maxheight`. Редіректи вимкнені (`allow_redirects` = false), таймаут і максимальний розмір тіла — `config/oembed.php` / env.
7. Відповідь JSON кешується (`Cache` store додатку) з ключем від SHA-256 нормалізованого `url` + розмірів; TTL — `cache_age` з відповіді (у межах `max_cache_ttl_seconds`) або дефолт.
8. Поле `html` санітизується: для більшості провайдерів залишаються лише теги `iframe` з `src` на **https** і хостом з білого списку `allowed_iframe_hosts` у `config/oembed.php`. Для **Threads** (endpoint `graph.threads.net`) — окремий шлях: rich **blockquote** `text-post-media` з очищенням скриптів і небезпечних атрибутів (деталі — T118). Атрибути на кшталт `on*` відкидаються.

## Оновлення `providers.json`

1. Візьміть актуальний `providers.json` з пакета `oembed-providers` (наприклад `https://unpkg.com/oembed-providers@latest/providers.json`) або з репозиторію [iamcal/oembed](https://github.com/iamcal/oembed).
2. Замініть файл `backend/data/oembed-providers.json`.
3. Оновіть рядок версії у `backend/data/oembed-providers.source.txt` (наприклад `oembed-providers@x.y.z`).
4. Закомітьте зміни; після деплою кеш парсеного реєстру в Redis оновиться за ключем `filemtime` (див. `OEmbedProviderRegistry`).

Секрети не потрібні.

## SSRF checklist (оператор / рев’ю)

- [ ] Вхідний `url` не використовується як цільовий host вихідного запиту.
- [ ] Відхиляються loopback, RFC1918, link-local, metadata-IP на вхідному URL.
- [ ] Редіректи до внутрішніх мереж вимкнені на клієнті HTTP.
- [ ] Ліміт розміру відповіді та таймаут увімкнені.
- [ ] Throttle `oembed-read` увімкнений (залучений до маршруту).

## Приклад `curl`

Після входу в сесію (cookies + при потребі CSRF для змінюючих методів; для GET достатньо cookie сесії Sanctum):

```bash
curl -sS -b cookies.txt \
  'http://127.0.0.1:8000/api/v1/oembed?url='$(python3 -c 'import urllib.parse; print(urllib.parse.quote("https://www.youtube.com/watch?v=dQw4w9WgXcQ", safe=""))')
```

У тестах без мережі використовуйте `Http::fake()` (див. `Tests\Feature\OEmbedApiTest`).

## SPA (ChatMessageBody / T46)

- Для URL без локального резолвера, але з евристичного списку хостів (Vimeo, SoundCloud, Dailymotion, TikTok, Twitch), парсер емітить сегмент **`oembedPending`**.
- Компонент **`ChatOembedBlock`** викликає `GET /api/v1/oembed` (axios + cookies), з відповіді парсить **лише** `iframe` через `DOMParser` (без `v-html`).
- У варіанті **`archive`** такі URL показуються як звичайне посилання (без запиту oEmbed).

## QA

- `cd backend && php artisan test --filter=OEmbedApiTest`
- `cd backend && npm run test:msg-parse && npm run build`

У `phpunit.xml` задано `OEMBED_TESTING_SKIP_DNS_HOSTS` (лише `APP_ENV=testing`), щоб тести проходили без зовнішнього DNS (наприклад у sandbox CI). У продакшені цю змінну не встановлювати.
