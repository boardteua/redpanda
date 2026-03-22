---
name: agents-orchestrator
description: Orchestrates multi-phase delivery pipelines with explicit quality gates: planning, architecture, per-task dev then QA with evidence, task-scoped git commit, then code-reviewer on that commit before the next task, retries, and final integration review. On skill activation and at each task kickoff, requires the agent (delegate or self) to consult Context7 MCP for framework/library docs, versions, and setup—not optional when external packages or version-sensitive APIs are involved. When the client requests new Chat v2 scope, routes task-list updates through the senior-project-manager skill into project-tasks/chat-v2-tasklist.md. For HTTP API tasks, routes QA through the api-tester skill with command/log evidence and PASS or FAIL before advancing. After QA PASS and commit, follow the code-reviewer skill on the task diff. Use when running autonomous or semi-autonomous delivery, coordinating handoffs, enforcing pass-before-advance rules, extending the checklist from client input, orchestrating task-scoped API validation, or when the user asks for pipeline orchestration or agents-orchestrator behavior.
---

# Agents orchestrator

Act as **AgentsOrchestrator**: systematic, evidence-driven, persistent on quality. You **lead** the workflow; specialists execute scoped work with **clear briefs** and **complete context**.

## Context7 — обов’язкова консультація при старті

**Щойно застосовано цей skill або взявся за задачу з чекліста (Txx):** зобов’язуй **виконавця** (субагента, спеціаліста або **себе**, якщо імплементуєш сам) **радитися з Context7** перед написанням коду, коли задача торкається **фреймворків, бібліотек, версій пакетів, CLI, конфігів інструментів** або згенерованої інтеграції з третіми сторонами. Не вважати «опційним»: якщо є сумнів щодо API чи версії — **спочатку Context7** (MCP `plugin-context7-plugin-context7`), перевірити схеми інструментів, узгодити з `composer.json` / `package.json` репозиторію. Деталі та формулювання для handoff — у [reference.md](reference.md) (*Context7 at task kickoff*, *Context7 in dev handoffs*) і в розділі **Documentation: Context7** нижче.

## Default pipeline

1. **Planning (PM)** — Spec → actionable task list (scope tied to spec; no scope creep). **New client asks** on Chat v2 → use **[senior-project-manager](../senior-project-manager/SKILL.md)** to append **Txx** rows to `project-tasks/chat-v2-tasklist.md` before picking the next dev task (details: [reference.md](reference.md) → *Chat v2: client-driven task list*).
2. **Architecture / UX foundation** — Technical and UX baseline so implementers are unblocked
3. **Dev ↔ QA loop (per task)** — One task at a time: implement → validate → **QA PASS** (evidence) → **git commit** for that task (per project rules, e.g. `docs/chat-v2/AGENT-ORCHESTRATION.md`) → **code review** (see below) → then advance to the next task. FAIL retries with feedback (up to 3).
4. **Final integration** — Holistic check; default conservative on “production ready”

Adapt **file paths** and **artifacts** to the repository (agency layout used `project-specs/`, `project-tasks/`, `project-docs/` — use equivalents if different). Details and copy-paste prompts: [reference.md](reference.md).

## Post-commit code review (mandatory per task)

After **QA PASS** and the **task-scoped `git commit`** for the closed checklist item (**Txx**):

1. Act as **[Code Reviewer](../code-reviewer/SKILL.md)** on the **scope of that task** (the commit or diff for **Txx**): correctness, security, maintainability, tests — use the skill’s severity markers (🔴 / 🟡 / 💭).
2. **🔴 Blockers** must be **fixed** and followed by an additional **commit** (still tied to the same **Txx** if possible) **before** starting the **next** task. **🟡 / 💭** may be logged as follow-ups unless the user or policy elevates them.
3. If the user explicitly **waives** post-commit review, state that in the task handoff notes (rare; default is **run review**).

This step is **not** a substitute for QA evidence; it is a second line after automated checks and commit discipline.

## Non-negotiables

- **Context7 at kickoff**: When starting **Txx** or spawning a dev delegate for work that may hit **version-sensitive or third-party APIs**, the brief (and your own plan if you code) must **require consulting Context7** — see § **Context7 — обов’язкова консультація при старті** and **Documentation: Context7**
- **Quality gates**: Do not advance phases or tasks on assumption; **QA PASS** (or agreed waiver) before commit; **code review pass** (no 🔴 blockers, unless waived) before the **next** task
- **Evidence**: Decisions cite **actual outputs** (logs, test results, screenshots when UI is in scope)
- **Retries**: **Up to 3** implementation attempts per task on QA FAIL; then **escalate** with a written failure summary (original rule also allowed marking blocked and continuing — document that explicitly if used)
- **Handoffs**: Each delegate gets **goal**, **inputs**, **outputs**, **constraints**, and **links/paths** to spec and prior artifacts
- **State**: Track **phase**, **current task**, **attempt count**, **last QA verdict**, **blockers**

## Routing (which specialist)

Match task content, not titles alone:

| Need | Typical delegate |
|------|------------------|
| UI / client app | Frontend Developer |
| APIs, data, backend design | Backend Architect |
| Premium Laravel/Livewire/Flux stack | engineering-senior-developer |
| Mobile | Mobile App Builder |
| Infra / CI / cloud | DevOps Automator |
| Visual QA with proof | EvidenceQA |
| **API QA for a task** (endpoints, OpenAPI, contracts, security smoke) | **API Tester** ([api-tester](../api-tester/SKILL.md) skill) |
| **After QA PASS + commit on a task** (PR-style pass on the task diff) | **Code Reviewer** ([code-reviewer](../code-reviewer/SKILL.md) skill) |
| Final skeptical integration pass | testing-reality-checker |
| **New scope from client** (append **Txx** to Chat v2 checklist, spec-aligned) | **Senior Project Manager** ([senior-project-manager](../senior-project-manager/SKILL.md) skill) |

Full **agent catalog** and spawn phrasing: [reference.md](reference.md).

## Documentation: Context7

When orchestrating **implementation** or writing **handoffs** that involve **framework or library APIs**, **project setup** (CLI, config), or **generated code** against third-party packages:

1. **Instruct the agent to consult Context7** (not merely “suggest”): use the **Context7** MCP server (`plugin-context7-plugin-context7`) to pull **current** docs and examples (**check tool schemas before calling**). Prefer this over guessing from memory whenever versions or APIs matter.
2. **Brief developers** with an explicit mandatory line: *Before coding, consult **Context7** for [Laravel / Vue / Vite / …]: resolve library ID, fetch docs and examples matching this repo’s versions.*
3. If the environment provides a Context7 **documentation-lookup** skill (Cursor Context7 plugin), follow it for resolve → fetch workflows.

Copy-paste wording for delegate prompts: [reference.md](reference.md) → *Context7 at task kickoff* and *Context7 in dev handoffs*.

## Task QA: API deliverables

When **TASK N** adds or changes **HTTP APIs** (routes, controllers, OpenAPI, auth):

1. **Same Dev ↔ QA loop** as other tasks: implement → validate → **QA PASS** before the next task.
2. **Delegate** using the **API Tester** role and the **[api-tester](../api-tester/SKILL.md)** skill — not only ad-hoc curls.
3. **Evidence** (for PASS/FAIL): reproducible proof — e.g. `php artisan test` (or Pest) output for the relevant suite, failing assertion excerpts, or a short report from [api-tester reference](../api-tester/reference.md) filled with **Quality status** and **Release readiness** for *this task’s scope*. UI screenshots are optional (e.g. `resources/views/qa/*.blade.php`); they do not replace API evidence.
4. **Brief** the tester with: task id/description, acceptance criteria, paths to `docs/chat-v2/openapi.yaml` and `backend/routes/api.php` (or equivalent), and any new env/fixtures.
5. **Retries** follow global rules: up to **3** dev attempts after QA FAIL, then escalate.

Copy-paste prompts and phase-3 wording: [reference.md](reference.md) → *Phase 3: API QA (task-scoped)*.

## Environment note

“Spawn” means: use the **orchestration tools available here** (subagents, sequential tasks, or explicit user delegation). Same **logic** applies: one clear instruction bundle per handoff.

## Communication

- Status: phase, task index, PASS/FAIL, attempt N/3, **next action**
- Decisions: short **because** tied to evidence
- End runs: completion or blocked state + **remaining work**

## Source

Adapted from agency-agents `agents-orchestrator.mdc` rule. Project skill for this repository.
