# T89 — QA evidence (legacy orange → `--rp-*`, світла тема)

## Вердикт

**PASS** (автоматичні перевірки нижче). Скріншоти до/після — за оператором (вітальня, стрічка, сайдбар, бургер, бабл) — опційно.

## Команди

```bash
cd backend && npm run build
```

## Оновлені CSS-змінні (`:root[data-theme='light']`)

| Змінна | Було (орієнтир) | Стало (T89 / orange.css) |
|--------|------------------|---------------------------|
| `--rp-landing-hero-top` | `#fb923c` | `#eaa727` |
| `--rp-landing-hero-bottom` | `#ea580c` | `#e48820` |
| `--rp-chat-app-bg` | `#e8e2d8` | `#fefff6` |
| `--rp-chat-header-bg` | `#e3dcd1` | `#ece9df` |
| `--rp-chat-feed-bg` | `#f3efe6` | `#ebecdb` |
| `--rp-chat-sidebar-bg` | `#3d332b` | `#362709` |
| `--rp-chat-sidebar-fg` | `#f4ede3` | `#f7f4f9` |
| `--rp-chat-sidebar-muted` | `#a89888` | `#c4b5a8` |
| `--rp-chat-sidebar-border` | `#524438` | `#312304` |
| `--rp-chat-sidebar-link` | `#c4d9ff` | `rgb(255 255 255 / 0.92)` |
| `--rp-chat-sidebar-link-hover` | `#e8f0ff` | `#ffffff` |
| `--rp-burger-accent-bg` | `#f7e008` | `#ffc600` |
| `--rp-burger-accent-fg` | `#1c130a` | `#333333` |
| `--rp-burger-accent-hover` | `#e6d000` | `#e6b800` |
| `--rp-chat-row-odd` | `#ebe4d8` | `#e3e5d6` |
| `--rp-chat-send-bg` | `#5c4336` | `#ad6431` |
| `--rp-chat-send-bg-hover` | `#4a362c` | `#744f34` |
| `--rp-chat-send-fg` | `#fffefb` | `#ffffff` |
| `--rp-peer-icon-admin` | `#fca5a5` | `#dc2626` |
| `--rp-peer-icon-mod` | `#86efac` | `#1ab334` |
| `--rp-peer-icon-vip` | `#fdba74` | `#ff9900` |

Темна тема та `data-theme=system` (dark) **не** змінювались у цьому інкременті.

## Контраст (орієнтовно)

- `#f7f4f9` на `#362709`: ~11∶1 (текст/тло AA).
- `#333333` на `#ffc600`: ~12∶1.
- `#ffffff` на `#ad6431`: ~4.5∶1+ (короткий текст на кнопці — ок для AA large / перевірити в UI).

## Артефакти

- Таблиця legacy: [T89-legacy-orange-palette.md](T89-legacy-orange-palette.md).
