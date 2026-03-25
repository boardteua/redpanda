# T136 — Канонічний домен: cutover на **board.te.ua**

**Задача:** [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) → **T136**.  
**Мета:** публічний чат відкривається за **`https://board.te.ua/`** (і **`https://www.board.te.ua/`** у сертифікаті); тимчасовий vhost на окремому DNS-префіксі **більше не** є джерелом правди для прод-посилань у коді та документації репозиторію.

Цей файл — **runbook для оператора** (SSH + `sudo`). Секрети, приватні ключі та повний вміст vhost **не** комітити в git.

**Журнал (prod, 2026-03-25):** cutover на **`https://board.te.ua/`** виконано (env, nginx, ремап URL **`chat:legacy-remap-board-urls`**, перезбірка Vite, recycle контейнерів). Задача **T136** у чеклісті — **PASS**; за потреби повторити перевірку real-time — [T96-QA.md](T96-QA.md).

---

## 1. Змінні середовища (prod / staging, без коміту секретів)

Оновити у канонічному env (напр. `docker/production.env` на сервері, secret store, CI vars), потім **перезбірка фронту** якщо змінювались **`VITE_*`**:

| Ключ | Орієнтир значення |
|------|-------------------|
| `APP_URL` | `https://board.te.ua` (без завершального `/`, як прийнято в Laravel) |
| `SANCTUM_STATEFUL_DOMAINS` | `board.te.ua,www.board.te.ua` (формат як у вашому поточному env) |
| `SESSION_DOMAIN` | зазвичай `.board.te.ua` або `board.te.ua` — **не** змінювати без розуміння cookie-політики; звірити з Sanctum |
| `ASSET_URL` | якщо використовується — канонічний origin |
| `VITE_REVERB_HOST` / `VITE_REVERB_PORT` / `VITE_REVERB_SCHEME` | публічний хост **board.te.ua**, схема **`wss`**, порт **443** (або як у вашому проксі) |
| `REVERB_*` / `BROADCAST_*` | узгодити з [T96-QA.md](T96-QA.md), **T80**, `docker/README.md` |
| `LEGACY_URL_REMAP_TARGET_ORIGIN` | після cutover для другого проходу ремапу БД: `https://board.te.ua` (див. § 3) |

**Зовнішні консолі (вручну):** Auth0, OAuth redirect URIs, CORS / allowed origins у будь-яких сервісах, де було зареєстровано прев’ю-хост — додати **`https://board.te.ua`** (і за потреби **`https://www.board.te.ua`**), прибрати або залишити редирект зі старого імені за політикою продукту.

Після зміни `VITE_*` на сервері: виконати деплойний крок **`npm run build`** (див. **`docker/deploy.sh`**, symlink **`backend/.env`** / копія env для Vite).

---

## 2. Nginx + TLS на хості (`ssh board.te.ua`)

Робочий код: **`/var/www/redpanda`** (не плутати з legacy-деревом **`/var/www/board.te.ua`** — **T113** / **T132**).

### 2.1 Бекап поточного vhost

```bash
sudo cp -a /etc/nginx/sites-available/board.te.ua \
  "/root/nginx-board.te.ua.bak.$(date -u +%Y%m%dT%H%M%SZ)"
```

Шлях до файлу бекапу зафіксувати в QA (ім’я файлу без вмісту).

### 2.2 Перенесення робочої конфігурації

1. Робочий site чату раніше міг бути у файлі на кшталт **`/etc/nginx/sites-available/new.board.te.ua`** (окремий `server_name` для прев’ю).
2. **Скопіювати** вміст (або об’єднати блоки) у **`/etc/nginx/sites-available/board.te.ua`** так, щоб:
   - `server_name` містив **`board.te.ua`** і за потреби **`www.board.te.ua`**;
   - були **`location /`**, **`location /app/`**, **`location /apps`** з повним **`proxy_set_header`** на Laravel (**:8080**) і Reverb (**:6001**) — канонічний приклад: [docker/nginx/host-nginx-reverb-proxy.example.conf](../../docker/nginx/host-nginx-reverb-proxy.example.conf).
3. У **результатуючому** `board.te.ua` **прибрати застарілі** `ssl_certificate` / `ssl_certificate_key`, що суперечать новому випуску Certbot (щоб не було подвійних або прострочених шляхів).
4. Оновити **`sites-enabled`**: один логічний site для канонічного імені (симлінки за фактичною схемою на VPS — **один** узгоджений набір кроків у команді).

### 2.3 Перевірка та reload

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 2.4 Certbot

Випустити або оновити сертифікат для **`board.te.ua`** і **`www.board.te.ua`** (плагін nginx або `certonly` — як на сервері). Перевірити таймер оновлення (`systemctl list-timers | grep certbot` або аналог).

### 2.5 Старий прев’ю-site

Після успіху: **за рішенням продукту** — залишити окремий server з **301** на `https://board.te.ua$request_uri`, або вимкнути site і прибрати DNS. Зафіксувати рішення тут одним рядком у PR / коментарі до задачі.

---

## 3. БД: абсолютні URL у текстах

Таблиці та колонки (як у **T132**): **`chat.post_message`**, **`chat.avatar`**, **`private_messages.body`**.

1. **Бекап** цільових таблиць або повної БД.
2. У `.env`: **`LEGACY_URL_REMAP_TARGET_ORIGIN=https://board.te.ua`** (без `/` в кінці).
3. З контейнера / `backend`:

```bash
cd backend && php artisan chat:legacy-remap-board-urls --dry-run
php artisan chat:legacy-remap-board-urls --force   # на production лише після бекапу та review
```

Команда ідемпотентна: префікси **`https?://new.board.te.ua`**, **`//new.board.te.ua`**, а також класичні **`board.te.ua` / www** замінюються на target (див. `LegacyBoardTeUaUrlRemapService`). Повторний прогін з тим самим target не псує дані.

Перевірка після прогону: SQL або Artisan — **0** рядків з підрядком прев’ю-хоста в цільових колонках (конкретний запит узгодити з оператором; не публікувати PII).

---

## 4. QA evidence (повне закриття T136)

- **Репозиторій:** `rg 'new\.board\.te\.ua'` — очікувано лише згадки в контексті міграції (**T136** у чеклісті, цей runbook, **T132** whitelist) або порожньо за політикою команди.
- **`npm run build`** / **`php artisan test`** — PASS після змін у JS/PHP.
- **Зовні:** `curl -sI https://board.te.ua/` (і політика для `www`); валідний ланцюг TLS.
- **Smoke:** логін → чат → **WS 101** (**T96**).
- **Без** секретів і приватних ключів у git.

---

## 5. Посилання

- Деплой: [T80-DEPLOY-CHECKLIST.md](T80-DEPLOY-CHECKLIST.md), **`docker/deploy.sh`**.  
- Ремап медіа/URL: [T132-LEGACY-MEDIA-MIGRATION.md](T132-LEGACY-MEDIA-MIGRATION.md).  
- Real-time / nginx: [T96-QA.md](T96-QA.md), **`docker/nginx/host-nginx-reverb-proxy.example.conf`**.
