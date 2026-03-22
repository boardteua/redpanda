# T46 — Spike: рендер тіла повідомлення (посилання, картинки, ембеди)

**Вибір:** без додаткових npm-пакетів (`linkifyjs`, `DOMPurify` тощо). Реалізація — **чистий JS** + **Vue 2.7** з розбиттям тіла на **типізовані сегменти** (`text` | `link` | `image` | `embed`).

**Чому:**

- **Бандл:** нуль нових залежностей; Vite-збірка лишається легкою.
- **Безпека (узгоджено з T35):** користувацький текст **не** потрапляє в `v-html`. Текст — у `{{ }}` (екранування Vue). Посилання — `href` лише після `new URL()` + `http/https` + заборона `username`/`password` у URL. Ембеди — `iframe` тільки з **канонічних** `src`, зібраних з allowlist-патернів (YouTube, Spotify, Apple embed host), не з сирого вводу.
- **Підтримка:** правила для нових провайдерів — явні функції в одному модулі; **масштабованість** — масив **`EMBED_RESOLVERS`** у `chatMessageBodyParse.js` (порядок = пріоритет). Новий провайдер = один `resolve(trimmed)` + запис у масив.
- **Майбутній oEmbed:** реєстр провайдерів і споживач описані в специфікації oEmbed ([Context7: `/iamcal/oembed`](https://context7.com/iamcal/oembed)); пакет `oembed-providers` дозволяє знайти endpoint за URL на бекенді й підставляти готовий `html` замість жорстких iframe — за потреби окремий таск.

**Соцмережі (клієнтські iframe, без додаткових npm):**

| Джерело | Патерн URL | `iframe` / примітка |
|--------|------------|---------------------|
| **X (Twitter)** | `twitter.com/.../status/{id}`, `x.com/...`, `/i/web/status/{id}` | `platform.twitter.com/embed/Tweet.html?id=` |
| **Threads** | `threads.net/(@user/)post/{postId}` | `threads.net/embed/post/{postId}/` |
| **Telegram** | `t.me` / `telegram.me` `{slug}/{numericMessageId}` (не `joinchat`, `s`, `+`, …) | `t.me/...?embed=1` |
| **Facebook** | `story_fbid`, `/posts/`, permalink, reels, groups permalink, `fb.me/…` | `facebook.com/plugins/post.php?href=` |

**Обмеження / продукт:**

- Apple Music: повноцінний ембед лише для URL на **`embed.music.apple.com`**; звичайні `music.apple.com/...` лишаються **звичайним посиланням** (стабільного on-client перетворення без oEmbed немає).
- Spotify: підтримка стандартних шляхів `/track|album|playlist|episode|show/{id}` з опційним префіксом `/intl-xx/`.
- Гаряче посилання на зображення: за розширенням у path (`.png`, `.jpg`, `.jpeg`, `.gif`, `.webp`, `.avif`).
- **X / Meta / Facebook:** частина сторінок може блокувати показ у `iframe` (політики фрейму, логін); тоді користувач може відкрити оригінальне посилання (архів показує лінк замість iframe).
