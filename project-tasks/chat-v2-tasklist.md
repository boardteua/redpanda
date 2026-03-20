# Chat v2 — task list (Agents Orchestrator)

**Правила:** одна задача в роботі; після реалізації — **код-рев’ю** (див. `docs/chat-v2/AGENT-ORCHESTRATION.md`), потім QA з **PASS** лише за доказом (логи, тести, скріншоти для UI). До **3** спроб на задачу; далі — escalate. Відкриті задачі: рядки `### [ ]`; закриті: `### [x]`.

**Специфікація:** [project-specs/chat-v2-setup.md](../project-specs/chat-v2-setup.md)  
**Оркестрація:** [docs/chat-v2/AGENT-ORCHESTRATION.md](../docs/chat-v2/AGENT-ORCHESTRATION.md)

---

### [x] T01 — Bootstrap: Laravel, Vite+Vue 2.7, Redis, MySQL міграції (users, rooms, chat), utf8mb4, індекси з DATABASE-SCHEMA §8.1

- **Delegate:** engineering-senior-developer або Backend Architect (за наявності)
- **Deliverables:** `composer install`, `npm ci`, міграції застосовуються на чистій БД; `.env.example` з ключами MySQL/Redis/Reverb
- **QA evidence:** `php artisan migrate --no-interaction`; `php artisan test` (хоча б smoke); `npm run build` без помилок

---

### [x] T02 — Auth API: реєстрація, логін, гість; Sanctum SPA (cookie+CSRF); Form Requests + rate limit на login/register

- **Delegate:** Backend Architect / senior Laravel
- **Deliverables:** ендпоінти `/api/v1/...`; політики гостя vs зареєстрованого
- **QA evidence:** PHPUnit/API тести або запис `curl`/HTTPie + очікувані коди; перевірка 429 при флуді

---

### [x] T03 — Vue: екрани логін/реєстрація/гість; design tokens; focus-visible та контраст AA на формах

- **Delegate:** Frontend Developer + узгодження з UI Designer (за потреби)
- **Deliverables:** маршрути `/`, інтеграція з T02; базові CSS змінні світла/темна тема
- **QA evidence:** скріншоти + короткий чекліст клавіатурної навігації (запис у коментарі до задачі)

---

### [x] T04 — Chat REST v1: список кімнат, історія (cursor), POST повідомлення з `client_message_id` (idempotency), rate limit, базовий pipeline slash-команд

- **Delegate:** Backend Architect / senior Laravel
- **Deliverables:** відповіді JSON узгоджені з планом; валідація кімнати та access
- **QA evidence:** тести на дубль POST з тим самим `client_message_id`; негативні кейси 403/422

---

### [x] T05 — Real-time: Reverb, `routes/channels.php` authorize для `room.*` / `user.*`, подія `MessagePosted`, черга/redis за потреби

- **Delegate:** Backend Architect / senior Laravel
- **Deliverables:** підписка лише з валідними правами; мінімальний payload події
- **QA evidence:** ручна або автоматична перевірка: неавторизований не підписується на чужу кімнату

---

### [x] T06 — Vue чат: стрічка, композер, Laravel Echo, дедуп по `post_id`, деградація до короткого poll якщо WS недоступний

- **Delegate:** Frontend Developer
- **Deliverables:** злиття HTTP відповіді після send + WS; індикатор degraded (за планом)
- **QA evidence:** скріншот стрічки + опис сценарію (відправка, отримання з іншого сеансу/браузера за можливості)

---

### [x] T07 — Сайдбар 320px: вкладки Люди, Кімнати, Друзі, Приват, Ігнор; порожні стани як у CHAT-PANEL-SIDEBAR; мобільний off-canvas

- **Delegate:** Frontend Developer
- **Deliverables:** паритет UX з документацією; touch targets ≥44px на іконках табів
- **QA evidence:** скріншоти desktop + вузький viewport; keyboard до табів

---

### [x] T08 — Приват + друзі + ignore: API, UI private_panel, broadcast `private.*`, узгодження `/msg` з одним шляхом відправки

- **Delegate:** Full stack (Backend + Frontend послідовно або два мікро-задачі за погодженням оркестратора)
- **Deliverables:** паритет PRIVATE-MESSAGES.md; авторизація private каналів
- **QA evidence:** тест сценарію «відкрити приват з меню → відправити → отримати» + скрін

---

### [x] T09 — Архів: таблиця, пагінація, пошук з лімітами; cursor/offset узгоджено з планом

- **Delegate:** Backend + Frontend
- **Deliverables:** паритет SITE-STRUCTURE (архів)
- **QA evidence:** скрін архіву + тест повільного запиту не падає (за можливості EXPLAIN у staging)

---

### [x] T10 — Медіа: upload зображень, таблиця `images`, обмеження MIME/size; інтеграція з повідомленням

- **Delegate:** Backend + Frontend
- **Deliverables:** файли поза webroot або signed URL; помилки зрозумілі для UI
- **QA evidence:** завантаження тестового зображення + відображення в стрічці

---

### [x] T11 — Спостережуваність: structured logs, health/readiness, короткий runbook (Reverb/Redis down) у `docs/chat-v2/` або README

- **Delegate:** Backend Architect / DevOps-oriented developer
- **QA evidence:** `curl` health endpoint; приклад рядка логу

---

### [x] T12 — Модерація MVP: banned IP, filter words, kick/mute hooks (мінімум який відповідає схемі users)

- **Delegate:** Backend Architect
- **QA evidence:** тест або ручний сценарій блокування

---

### [ ] T13 — (Опційно) ETL legacy: імпорт з org100h.sql у staging, без секретів у репо

- **Delegate:** Backend Architect
- **QA evidence:** звіт про кількість імпортованих рядків + перевірка сиріт

---

### [ ] T14 — Інтеграція: повний прохід «логін → чат → сайдбар → (приват якщо T08 готовий)»; code review / reality check

- **Delegate:** code-reviewer або testing-reality-checker (за роллю в команді)
- **QA evidence:** чекліст з AGENT-ORCHESTRATION; вердикт PASS / NEEDS_WORK
