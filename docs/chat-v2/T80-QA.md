# T80 — QA evidence (staging / production checklist)

## Вердикт

**PASS** (документація + локальна верифікація health; повний прохід на staging — рядок для оператора нижче).

## Дата

2026-03-23

## Перевірки (локально / CI)

- **`php artisan test --filter=HealthEndpointTest`** — PASS (readiness JSON і коди відповіді).
- **`npm run build`** — без змін у JS/CSS у межах T80; за потреби оператор після змін у `backend/`.

## Чекліст документації

- [x] Додано **`docs/chat-v2/T80-DEPLOY-CHECKLIST.md`**: міграції, `optimize`, черга, Reverb, проксі WS, backup (без секретів), health, real-time сценарій.
- [x] Розширено **`docs/chat-v2/T11-RUNBOOK.md`** посиланням на повний чекліст T80.
- [x] Таблиця ENV узгоджена з **`backend/.env.example`** (канонічне джерело прикладів).

## Staging (оператор)

Після проходження чеклісту з [T80-DEPLOY-CHECKLIST.md](T80-DEPLOY-CHECKLIST.md) заповнити:

| Крок | Результат (так / ні / N/A) |
|------|----------------------------|
| `GET /health/ready` → 200, `database: ok` | |
| `GET /up` → 200 | |
| Два клієнти: повідомлення через WS без перезавантаження | |
| Queue worker запущений, broadcast доходить | |
| Reverb за проксі (якщо застосовно) | |

**Підпис оператора / дата staging:** _______________________

## Примітки

- Повний dry-run на реальному staging без доступу агента до інфраструктури зафіксовано як **відкритий рядок** у таблиці вище; локальна частина T80 закрита тестами health.
