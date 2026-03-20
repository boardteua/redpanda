# AGENTS.md

Пам’ять для AI-агентів у цьому репозиторії. Оновлюється інкрементально за workflow **Continual Learning** (див. `docs/chat-v2/CONTINUAL-LEARNING.md`).

## Learned User Preferences

## Learned Workspace Facts

- Проєкт **redpanda**: greenfield-реалізація чату як наступника board.te.ua; стек цілі — Laravel (остання стабільна гілка), Vue 2.7 SPA, MySQL; вимоги та схема описані в `docs/board-te-ua/` (SITE-STRUCTURE, CHAT-*, PRIVATE-MESSAGES, DATABASE-SCHEMA). Код Laravel/Vite — у каталозі **`backend/`**; Vue 2.7 з **vue-router@3**, головна **`/`** — Blade `spa` + форми auth (T03); **Vite 7** + `@vitejs/plugin-vue2` (див. `backend/package.json`).
- Оркестрація багатоетапної розробки: `project-specs/chat-v2-setup.md`, `project-tasks/chat-v2-tasklist.md`, `docs/chat-v2/AGENT-ORCHESTRATION.md`; quality gate — одна задача з чекліста, QA PASS з доказом перед наступною.
- **Git:** після закриття задачі з чекліста (QA PASS) — **коміт** змін по цій задачі; деталі — розділ «Git: коміти при закритті задачі» у `docs/chat-v2/AGENT-ORCHESTRATION.md`.
