# T138 — SEO технічний QA (вітальня `/`)

**Статус:** PASS (2026-03-25)

## Передумови

- `APP_URL` узгоджений із канонічним прод-доменом (**T136**).
- У `public/` **немає** статичного `robots.txt`, що перехоплює трафік до Laravel (nginx/`php artisan serve` віддають файл з `public/` раніше за `index.php`).

## Автоматичні перевірки

- `cd backend && ./vendor/bin/phpunit --filter=SeoPublicRoutesTest` — PASS.
- `cd backend && ./vendor/bin/phpunit` — PASS (повний набір).
- `cd backend && npm run build` — PASS.

## Ручні перевірки (`curl`)

Підставити базовий URL середовища замість `https://board.te.ua`.

```bash
curl -sI "https://board.te.ua/" | head
curl -sL "https://board.te.ua/" | tr '\n' ' ' | grep -oE '<title>[^<]+</title>' | head -1
curl -sL "https://board.te.ua/" | grep -E 'meta name="description"|rel="canonical"|property="og:|twitter:card|application/ld\+json'
curl -s "https://board.te.ua/robots.txt" | head -30
curl -s "https://board.te.ua/sitemap.xml" | head -40
```

**Очікування**

- У HTML: `<title>`, `meta name="description"`, `link rel="canonical"`, повний набір основних **Open Graph** тегів, **Twitter Card** `summary_large_image`, блок **`application/ld+json`** з **`WebSite`** та **`Organization`**.
- **`og:image`** — абсолютний URL на **`/brand/og-default.png`** (1200×630).
- **`robots.txt`**: `Content-Type: text/plain`, `Allow: /`, обмеження для `/api/`, `/sanctum/`, `/broadcasting/`, явні секції для **Googlebot**, **Bingbot**, **GPTBot**, **ChatGPT-User**, **PerplexityBot**, **ClaudeBot**, **anthropic-ai** (усі з `Allow: /`), рядок **`Sitemap:`** на **`/sitemap.xml`**.
- **`sitemap.xml`**: валідний XML з `urlset`, мінімум головна **`/`** та публічні шляхи з `config/seo.php` → `sitemap_paths`.

## Зовнішні валідатори (після деплою на канонічний хост)

- [Rich Results Test](https://search.google.com/test/rich-results) — прев’ю структурованих даних.
- [Schema Validator](https://validator.schema.org/) — синтаксис JSON-LD (вставити HTML або фрагмент `<script type="application/ld+json">` з «View Source»).
- Переконатися, що **`APP_URL`** у середовищі збігається з URL у браузері; інакше **canonical**, **og:url** та **og:image** можуть вказувати на інший хост (виправлено в коді: абсолютні URL з кореня `config('app.url')`).

---

## Handoff → T139 (копірайт і GEO)

Фінальні **публічні формулювання** та **тон** виносяться в **T139**. Для оновлення текстів використовуйте:

| Що змінювати | Файли |
|--------------|--------|
| `<title>`, description, OG/Twitter, JSON-LD (назви, описи) | `backend/lang/uk/seo.php`, `backend/lang/en/seo.php` |
| Каркас розмітки (canonical, граф schema, підключення тегів) | `backend/resources/views/partials/seo-head.blade.php` |
| Шляхи OG / логотипу організації | `backend/config/seo.php` (env `SEO_*`) |
| Публічний текст для LLM | `backend/resources/content/llms.txt` (підстановка `__APP_URL__` без зміни контракту контролера) |
| Видимий **H1** і лід на вітальні | `backend/resources/js/views/AuthWelcome.vue` (**один** H1 на сторінку; узгодити з meta) |
| **FAQPage** у JSON-LD | додається в **T139** (той самий partial або сусідній include) |

**Тон і зміст** публічних рядків для індексації — лише **T139**: чат **розважальний**, не «корпоративна сухість».
