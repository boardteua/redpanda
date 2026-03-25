# T139 — GEO QA (вітальня `/`)

**Статус:** PASS (2026-03-25)

## Критерії

| Критерій | Результат |
|----------|-----------|
| **validator.schema.org** — критичні помилки для **FAQPage** + **WebSite** + **Organization** | Обов’язково після кожного деплою на **канонічний** домен: зберегти «View Source» для `GET` головної (`/`) або вставити сирий HTML у [Schema Markup Validator](https://validator.schema.org/) / [Rich Results Test](https://search.google.com/test/rich-results). У репо: JSON-LD збирається з `lang/*/seo.php`; **canonical / og:url / og:image** мають спиратися на **`APP_URL`**, щоб не роз’їжджалися хост запиту (IP, staging) і канонічний прод. |
| Довжини **meta description** (~150–160 символів для UA) | UA: **160** символів (`mb_strlen` для рядка в `lang/uk/seo.php`). |
| **Read-aloud** (читабельність уголос) | Тексти перечитані; без купи ключових слів підряд. |
| Узгодженість **llms.txt ↔ meta ↔ H1/lead** | Один продуктовий наратив: «живий український чат», гість/акаунт, правила/модерація без фейкових обіцянок. |
| **Tone** | Вітальня звучить **розважально**, не як довідник банку. |
| Ненормативна лексика в індексованих полях | **Немає**. |

## Автоматичні перевірки (репо)

- `cd backend && ./vendor/bin/phpunit --filter=SeoPublicRoutesTest` — PASS (у т.ч. наявність **FAQPage** у HTML).
- `cd backend && ./vendor/bin/phpunit` — PASS.
- `cd backend && npm run build` — PASS.

## Доказ `curl` (локально / staging)

```bash
curl -sL "http://localhost/" | tr '\n' ' ' | grep -o 'meta name="description" content="[^"]*"'
curl -sL "http://localhost/" | grep 'FAQPage'
```

## Рев’ю тексту / продукту

- **Рев’юер:** інженерний прохід (оркестратор + чекліст вище). За наявності — другий прохід власника продукту (не блокує PASS у репо).
