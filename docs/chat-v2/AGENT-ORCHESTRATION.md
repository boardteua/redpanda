# Chat v2 — оркестрація агентів (Agents Orchestrator)

Цей документ адаптує пайплайн з [.cursor/skills/agents-orchestrator/SKILL.md](../../.cursor/skills/agents-orchestrator/SKILL.md) під репозиторій **redpanda** і чат v2.

## Артефакти

| Призначення | Шлях |
|-------------|------|
| Індекс специфікації | [project-specs/chat-v2-setup.md](../../project-specs/chat-v2-setup.md) |
| Чекліст задач (`### [ ]` для підрахунку) | [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) |
| OpenAPI (Auth + Chat HTTP) | [openapi.yaml](openapi.yaml) |
| Вимоги board.te.ua | [docs/board-te-ua/](../board-te-ua/) |
| Майстер-план (архітектура, безпека, UI) | Файл плану Cursor: `chat_v2_laravel_vue_*.plan.md` |
| Пам’ять агентів (Continual Learning) | [AGENTS.md](../../AGENTS.md); інструкція [CONTINUAL-LEARNING.md](CONTINUAL-LEARNING.md); індекс `.cursor/hooks/state/continual-learning-index.json` |

## Пайплайн

1. **Planning (PM)** — з `chat-v2-setup.md` + board-te-ua: уточнити task list у `chat-v2-tasklist.md` (без scope creep).
2. **Architecture / UX foundation** — вже закладено в майстер-плані; при старті коду — короткий ADR: Sanctum cookie vs token, політика `aria-live`.
3. **Dev ↔ QA** — **строго одна задача** з task list: implement → **код-рев’ю** (роль [code-reviewer](../../.cursor/skills/code-reviewer/SKILL.md): коректність, безпека, тести) → збір доказів QA → **PASS** / **FAIL** (до 3 спроб на задачу). Якщо QA передбачає **live-чат / Echo / присутність / приват по WS** — дотримуйся розділу **«Локальне середовище real-time при завершенні задачі»** нижче.
4. **Integration** — після всіх обов’язкових `[x]`: повний прохід + скептичний вердикт (NEEDS WORK за замовчуванням, якщо немає сильних доказів).

## Git: коміти при закритті задачі

- Після **QA PASS** по задачі **Txx** — зробити **git commit** змін, що належать цій задачі (одна задача ≈ один коміт або зв’язана серія з чіткими префіксами у повідомленні).
- Повідомлення коміту: короткий **subject** + у **body** (за потреби) рядок `Task: Txx` і що саме змінено / посилання на QA-файл.
- Не змішувати в одному коміті **непов’язані** задачі (наприклад T08 і правки README без прив’язки).
- Якщо за один прохід закрито кілька задач — **окремі коміти** по Txx (ретроспектива теж бажано по задачах, а не один «все разом», якщо це ще можливо рознести по `git add -p` / файлах).

Після великих етапів або стабільних рішень — за потреби оновити [AGENTS.md](../../AGENTS.md) за [CONTINUAL-LEARNING.md](CONTINUAL-LEARNING.md) (нові **Workspace Facts** / **User Preferences** з транскриптів).

## Стан оркестратора (ведіть у коментарі / issue / окремому STATUS.md)

- **Фаза:** PM | ArchitectUX | DevQALoop | Integration | Complete
- **Поточна задача:** Txx з task list
- **Спроба Dev:** 1–3
- **Останній QA вердикт:** PASS | FAIL + цитата фідбеку
- **Блокери:** текст

## Маршрутизація делегатів

| Задача / тип роботи | Типовий агент (роль) |
|---------------------|----------------------|
| Laravel API, БД, Reverb, політики | Backend Architect / senior Laravel |
| Vue 2 SPA, Echo, layout, a11y | Frontend Developer |
| Складний Laravel + Flux (не цей стек) | engineering-senior-developer — лише якщо змішуєте стек |
| UI токени, контраст, чекліст WCAG | UI Designer (рев’ю поверх Frontend) |
| Перевірка з доказом (скрін, лог) | EvidenceQA або еквівалент |
| Фінальна інтеграція | testing-reality-checker / code-reviewer |
| CI, деплой, секрети | DevOps Automator (коли з’явиться потреба) |

## Шаблон handoff для виконавця

```text
Goal: Закрити Txx з project-tasks/chat-v2-tasklist.md — [назва].
Inputs: spec index project-specs/chat-v2-setup.md; розділи board-te-ua [перелік]; майстер-план [шлях].
Outputs: [файли/PR]; оновлений task list (позначити [x] лише після QA PASS).
Constraints: не змінювати scope; PII не в репо; channel auth обов’язковий для WS.
```

## Шаблон QA

```text
Задача: Txx
Вердикт: PASS | FAIL
Доказ: [команда + вихід / скріншот / посилання на тест run]
Якщо FAIL: кроки відтворення, очікуване vs фактичне, файли для правки.
```

## Локальне середовище real-time при завершенні задачі

Правила для **QA PASS**, коли прийняття задачі залежить від **реального часу в чаті** (повідомлення з іншого сеансу/браузера, presence, приват по WebSocket, індикатор degraded/poll). Якщо для Txx достатньо лише **PHPUnit + `npm run build`** без ручної перевірки WS — цей блок можна скоротити одним реченням у доказі QA («real-time вручну не перевірявся»).

1. **Laravel Reverb** — окремий процес WebSocket. Локально: з каталогу `backend/` виконати **`php artisan reverb:start`**. У `.env` мають бути задані **`REVERB_*`**; у фронті (Vite) — **`VITE_REVERB_*`** узгоджені з хостом/портом/схемою, з яких відкривається SPA. Після зміни цих змінних у `.env` — **перезапустити** `npm run dev` (або перезібрати assets).
2. **Черга** — у `.env.example` за замовчуванням **`QUEUE_CONNECTION=database`**. Події `ShouldBroadcast` потрапляють у чергу: без воркера інші клієнти **не** отримують push, хоча POST у API успішний. Локально: **`php artisan queue:work`** або **`php artisan queue:listen`** (паралельно з `serve` і Reverb).
3. **Мінімальний набір процесів для ручного сценарію live-чату:** веб (`php artisan serve` або ваш stack) + **`reverb:start`** + **`queue:work`** + за потреби **`npm run dev`** у `backend/`.

Деталі та типові збої: [T11-RUNBOOK.md](T11-RUNBOOK.md), [T05-QA.md](T05-QA.md). Деплой і prod-операції: [T80-DEPLOY-CHECKLIST.md](T80-DEPLOY-CHECKLIST.md).

## Швидкий підрахунок відкритих задач

```bash
grep -c "^### \[ \]" project-tasks/chat-v2-tasklist.md
```

## Single-shot (для людини-оператора)

```text
Запусти пайплайн для project-specs/chat-v2-setup.md: уточни task list → для кожної задачі з project-tasks/chat-v2-tasklist.md: Developer (одна задача) → QA з доказом → лише PASS переходить далі → фінальна інтеграція T14.
```

## Звіт статусу (копіюйте в кінці сесії)

Див. шаблон «Workflow orchestrator status» у [.cursor/skills/agents-orchestrator/reference.md](../../.cursor/skills/agents-orchestrator/reference.md).
