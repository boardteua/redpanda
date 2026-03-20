# Continual Learning у репозиторії redpanda

Інтеграція навички **Continual Learning**: оновлення [AGENTS.md](../../AGENTS.md) за дельтами транскриптів, без повного перескану.

## Файли

| Шлях | Роль |
|------|------|
| [AGENTS.md](../../AGENTS.md) | Лише розділи `## Learned User Preferences` та `## Learned Workspace Facts`; пункти-мультипоінти, без секретів і одноразових інструкцій |
| `.cursor/hooks/state/continual-learning-index.json` | Інкрементальний індекс: `mtime` оброблених транскриптів |
| `~/.cursor/projects/<slug>/agent-transcripts/` | Джерело `.jsonl` транскриптів Cursor для цього воркспейсу |

`<slug>` зазвичай відповідає імені папки проєкту в `~/.cursor/projects/` (наприклад `Users-koristuvac-redpanda-redpanda` для шляху `/Users/koristuvac/redpanda/redpanda`).

## Коли запускати

- Після серії узгоджених рішень, повторюваних правок або нових стабільних фактів про репозиторій.
- Після значних змін у плані чату / оркестрації — за потреби оновити **Learned Workspace Facts**.

## Workflow (для агента)

1. Прочитати поточний `AGENTS.md`.
2. Завантажити `.cursor/hooks/state/continual-learning-index.json`.
3. Знайти транскрипти в `agent-transcripts/`, обробити лише нові або з новішим `mtime`, ніж у індексі.
4. Додати лише **високосигнальні** пункти: стійкі переваги користувача, факти про воркспейс (див. skill Continual Learning — inclusion bar, exclusions).
5. Записати оновлений індекс з актуальними `mtimeMs` / `lastProcessedAt`.

## Що не писати в AGENTS.md

Секрети, токени, PII, хеші, одноразові задачі, тимчасові гілки/коміти.
