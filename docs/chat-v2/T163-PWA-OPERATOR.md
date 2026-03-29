# T163 — PWA: нотатки для оператора

## HTTPS

Встановлення PWA та service worker у продакшені вимагають **HTTPS** (або `localhost` для розробки). Без TLS браузер не зареєструє SW / не покаже стандартну пропозицію «Встановити».

## Service Worker у каталозі `/build/`

Vite з `laravel-vite-plugin` публікує `sw.js` як **`/build/sw.js`**. За замовчуванням максимальний **scope** SW обмежений каталогом `/build/`, тоді як SPA живе під `/`, `/chat`, … Тому для коректної роботи PWA на весь додаток потрібен заголовок відповіді для **`/build/sw.js`**:

```http
Service-Worker-Allowed: /
```

### Apache (`public/.htaccess`)

У репозиторії додано правило через `mod_headers` (див. `backend/public/.htaccess`).

### Nginx (приклад)

```nginx
location = /build/sw.js {
    add_header Service-Worker-Allowed /;
    try_files $uri =404;
}
```

Якщо застосунок обслуговується з **префікса шляху** (не з кореня домену), узгодьте `REQUEST_URI` / `location` з фактичним префіксом.

## Оновлення після релізу

- Після `npm run build` змінюються хеші файлів у прекеші; **vite-plugin-pwa** з `registerType: 'autoUpdate'` підтягує новий SW і оновлює клієнт.
- Користувачі з довгоживучими вкладками можуть отримати нову версію після наступного відкриття сторінки / автооновлення SW.
- **HTML** (`spa.blade.php`) **не** кешується SW — токен CSRF Sanctum залишається з сервера, а не з застарілого кешу.

## Іконки

Набір **`/pwa/*`** (маніфест, maskable, Apple Touch, favicon-32) генерується скриптом **`npm run pwa:icons`** з `public/brand/board-te-ua-orange.png` — див. **T164** та `docs/chat-v2/T164-QA.md`.
