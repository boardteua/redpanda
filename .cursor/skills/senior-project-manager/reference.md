# Senior project manager — reference (Chat v2 task list)

Use with [SKILL.md](SKILL.md).

## Task block template (match `chat-v2-tasklist.md`)

Insert after the last task block (or in logical order if the user asked to group with a feature). Use the same heading level and separators as the file.

```markdown
### [ ] Txx — Короткий заголовок (за потреби: Опційно / після Tyy)

- **Delegate:** Backend Architect | Frontend Developer | Full stack | … (як у сусідніх задачах)
- **Deliverables:** конкретні артефакти, файли, ендпоінти, UX-паритет з назвою документа з `docs/board-te-ua/`, якщо застосовно
- **QA evidence:** тести | curl/HTTP | скріни | логи — щоб оркестратор міг зафіксувати PASS за доказом

---
```

**Field notes:**

- **Title language:** Ukrainian matches the current task list; English is acceptable if the user standardizes otherwise—stay consistent within one edit.
- **Delegate:** Mirror patterns from nearby tasks (e.g. T04–T08).
- **Deliverables:** Avoid “implement feature X”; use verifiable bullets (routes, components, migrations, OpenAPI updates).
- **QA evidence:** Align with [AGENT-ORCHESTRATION.md](../../../docs/chat-v2/AGENT-ORCHESTRATION.md) (evidence types per Backend vs UI).

## Choosing `Txx`

1. `grep` or search `chat-v2-tasklist.md` for `T[0-9][0-9]` / `T[0-9][0-9][0-9]` if ever needed.
2. Let `N = max(all nn)`.
3. New tasks get `T(N+1)`, `T(N+2)`, … in creation order.
4. Never renumber **closed** tasks; never change historical **Txx** identifiers.

## Client request → tasks (workflow)

1. Read client message; list **concrete outcomes** (user-visible or API).
2. Check `chat-v2-setup.md` + relevant `docs/board-te-ua/*` for parity or constraints.
3. If the ask is **ambiguous**, add **one** small “spike” or “clarify spec” task **or** document questions for the user before writing vague dev tasks.
4. Split **large** asks into multiple **Txx** so each fits **one** orchestrator task in flight (see task list header rules).
5. Edit `project-tasks/chat-v2-tasklist.md` only (unless user also wants spec updates—then say so explicitly).

## Optional: link to OpenAPI or QA pages

If the client ask touches HTTP API, mention in **Deliverables** or **QA evidence**:

- `docs/chat-v2/openapi.yaml`
- `backend/resources/views/qa/*.blade.php` when manual QA applies

## Original PM template (adapted paths)

The agency rule used `ai/memory-bank/...`. In **this** repo the canonical list is:

- **`project-tasks/chat-v2-tasklist.md`**

Keep the spirit of the old template:

- Specification summary → **cite** `chat-v2-setup.md`, do not duplicate the whole spec inside the task list unless the user asks for a summary section.
- **Acceptance** lives in **Deliverables** + **QA evidence** bullets per task, not only in a global “Quality Requirements” block (the chat-v2 file uses per-task QA).

## Example (illustrative)

**Client:** “Хочемо експорт історії чату в CSV для модераторів.”

**PM outcome:** One task if small; two if split admin API + UI.

```markdown
### [ ] T15 — Експорт історії кімнати в CSV (модератор)

- **Delegate:** Backend Architect + Frontend Developer (або два підряд мікро-задачі за погодженням)
- **Deliverables:** ендпоінт/дія з auth модератора; ліміти розміру/часу; CSV з колонками за узгодженою схемою; без секретів у URL
- **QA evidence:** тест на 403 для не-модератора; завантаження тестового CSV у staging; перевірка кодування utf-8

---
```

(Replace `T15` with the actual next id after scanning the file.)

## Orchestration handoff

After adding tasks, the **agents-orchestrator** skill can drive **one Txx at a time**. New tasks should be written so **api-tester** / **EvidenceQA** paths from orchestrator apply where relevant—see `.cursor/skills/agents-orchestrator/`.
