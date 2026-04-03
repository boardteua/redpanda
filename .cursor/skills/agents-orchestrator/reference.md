# Agents orchestrator — reference

Configurable paths, prompts, decision logic, report templates, and specialist roster.

## Paths (agency default — replace if your repo differs)

| Purpose | Example pattern |
|--------|------------------|
| Project spec | `project-specs/[project]-setup.md` |
| Task list | `project-tasks/[project]-tasklist.md` |
| Architecture doc | `project-docs/*-architecture.md` |
| Shared styles | `css/` (if used) |

Verify files exist before claiming a phase is done.

## Phase 1: Planning

```bash
ls -la project-specs/*-setup.md
```

**Delegate prompt (project-manager-senior) — greenfield or full regen:**

> Read the specification at `project-specs/[project]-setup.md`. Produce a comprehensive task list; **quote exact requirements** from the spec — do not add unrequested features. Save to `project-tasks/[project]-tasklist.md`.

**Chat v2 incremental adds (client request):** use **[senior-project-manager](../senior-project-manager/SKILL.md)** and the section **Chat v2: client-driven task list** above — edit `project-tasks/chat-v2-tasklist.md` only, preserve closed tasks, append new **Txx**.

```bash
ls -la project-tasks/*-tasklist.md
```

## Chat v2: client-driven task list

Use when the **user or client** asks for **new features, changes, or follow-up work** on Chat v2 and the canonical checklist already exists (`project-tasks/chat-v2-tasklist.md`). This is **incremental PM** work—not a full regen of the list from scratch.

**Delegate (follow [senior-project-manager](../senior-project-manager/SKILL.md)):**

> Read the client request and `project-specs/chat-v2-setup.md` (and relevant `docs/board-te-ua/*` if parity matters). Append one or more new tasks to **`project-tasks/chat-v2-tasklist.md`** using the **same markdown shape** as existing rows (`### [ ] Txx — …`, **Delegate**, **Deliverables**, **QA evidence**, `---`). Assign **Txx** = next id after the **maximum** existing `Tnn` in the file. Do **not** change `[x]` / `[ ]` on old tasks unless the user explicitly asked. Flag any ask that **conflicts** with the spec before writing tasks.

**Orchestrator:**

- Run this **before** starting implementation of the new scope (or between sprints), so **Dev ↔ QA** always targets a **defined Txx**.
- After the file is updated, resume **Phase 3** on the chosen open task.

## Phase 2: Architecture / UX foundation

```bash
head -n 20 project-tasks/*-tasklist.md
```

**Delegate prompt (ArchitectUX):**

> From `project-specs/[project]-setup.md` and the task list, produce technical architecture and UX foundation so developers can implement confidently.

```bash
ls -la css/ project-docs/*-architecture.md 2>/dev/null || true
```

### Superpowers (optional, Chat v2 / redpanda)

Якщо в IDE встановлено колекцію **Superpowers** (Cursor skills): повна **матриця** «фаза → skill» для цього репозиторію живе в **[docs/chat-v2/AGENT-ORCHESTRATION.md](../../../docs/chat-v2/AGENT-ORCHESTRATION.md)** → розділ *Superpowers (Cursor): де вмикати в пайплайні*. Тут не дублюємо таблицю — лишаються канонічні вимоги **Context7**, **api-tester**, **code-reviewer** з цього файлу та з [SKILL.md](SKILL.md).

## Phase 3: Dev ↔ QA (per task)

Optional task count (if checklist uses `### [ ]` headings):

```bash
TASK_COUNT=$(grep -c "^### \[ \]" project-tasks/*-tasklist.md 2>/dev/null || echo 0)
echo "Pipeline: $TASK_COUNT open tasks"
```

**Implementation (one task only):**

> Spawn the appropriate developer (Frontend Developer, Backend Architect, engineering-senior-developer, Mobile App Builder, DevOps Automator, etc.) to implement **TASK N only** from the task list, following the ArchitectUX foundation. **Mandatory:** at task start, the delegate must **consult Context7** (`plugin-context7-plugin-context7`) for any framework/library/CLI aspect that is not trivially verified in-repo; check MCP tool schemas before calling. Mark the task complete when implementation is finished.

### Context7 at task kickoff

Use **as soon as a Txx is picked** (orchestrator or implementer):

> **Consult Context7 before coding:** identify which libraries/frameworks this task touches (e.g. Laravel, Sanctum, Vue, Vite, Echo). Use MCP **Context7** to resolve IDs and fetch **current** docs/examples; align with this repo’s declared versions (`backend/composer.json`, `backend/package.json`). If the task is UI-only with no new APIs, state “Context7: N/A” in one line and proceed.

Orchestrator: paste this block into the first message of the task or into the delegate brief so the agent **must** either run Context7 or explicitly justify N/A.

### Context7 in dev handoffs

Use in any **Phase 3** developer brief when APIs or tooling are non-trivial (or by default for full-stack tasks):

> Before implementing, you **must** use **Context7** for the relevant libraries (Laravel, Vue, Vite, testing tools, etc.): resolve library IDs and fetch current documentation/examples so code matches the project’s actual stack versions. Do not rely on memory for version-specific APIs.

Orchestrator: include this line in custom handoffs when the task is likely to hit version-sensitive APIs; for **every** new task, default to including **Context7 at task kickoff** unless the scope is purely internal refactors with zero external API surface.

**QA — use the path that matches the task** (UI only, API only, or both):

### API QA (task-scoped)

Use when **TASK N** changes or introduces **HTTP APIs** (REST/JSON, webhooks, broadcasting contracts tied to HTTP). This is the **API Tester** path; it uses the **same Dev ↔ QA loop** as UI QA (PASS → next task; FAIL → dev retry, max 3 attempts).

**Delegate prompt (API Tester — follow [api-tester](../api-tester/SKILL.md)):**

> Validate **TASK N only** for API quality. **Inputs:** task title and acceptance criteria from `project-tasks/*-tasklist.md`; OpenAPI `docs/chat-v2/openapi.yaml` if the task touches documented endpoints; Laravel routes under `backend/routes/api.php` (and related). **Do:** map endpoints affected by this task; run or add **PHPUnit/Pest** feature tests (and any agreed security/contract checks) for that scope; optionally exercise `backend/resources/views/qa/*.blade.php` if the task relies on it. **Output:** explicit **PASS** or **FAIL**; attach **evidence** — test command + exit code, failing test names/lines, or a completed testing report (template in [api-tester/reference.md](../api-tester/reference.md)). **FAIL** must list blocking issues actionable for the developer.

**PASS criteria (orchestrator view):**

- Evidence is tied to **this task’s** API surface (not the whole monolith unless the task demands it).
- No unaddressed **blocking** failures; follow-ups may be listed but must not block PASS if the user/orchestrator agreed otherwise (document waivers).

### UI QA (EvidenceQA)

> Spawn **EvidenceQA** to validate **TASK N only**. Use screenshot or other visual evidence where UI is involved. Return **PASS** or **FAIL** with specific, actionable feedback.

**Loop:**

- PASS → **git commit** for this task (per project checklist rules — e.g. Chat v2: one commit per **Txx** after QA PASS) → **post-commit code review** (below) → next task; reset attempt counter
- FAIL → increment attempts; if attempts are under 3, send QA feedback back to dev for same task; otherwise escalate (see Failure management)

**Chat v2 (redpanda) — real-time QA before PASS:** If manual QA depends on live chat (Echo/WebSocket, presence, private push), ensure **Reverb** (`php artisan reverb:start`), **queue worker** when `QUEUE_CONNECTION` is not `sync` (`php artisan queue:work`), and matching **`VITE_REVERB_*`** after `.env` changes. Full checklist: `docs/chat-v2/AGENT-ORCHESTRATION.md` → section *Локальне середовище real-time при завершенні задачі*.

### Post-commit code review (after QA PASS + commit)

Run **after** task-scoped **QA PASS** evidence **and** the **`git commit`** for that **Txx**.

**Delegate (follow [code-reviewer](../code-reviewer/SKILL.md)):**

> Review the changes introduced for **TASK N / Txx** (the latest commit or diff for that task only). Output: summary, what works well, findings by 🔴 / 🟡 / 💭, questions, next steps. **🔴 blockers** must be fixed and committed before starting the next task unless the user waives.

**Orchestrator:**

- Treat 🔴 as **blocking** the pipeline for the **next** open task until resolved (fix + commit).
- 🟡 / 💭: track as debt or optional follow-ups per team policy.

## Phase 4: Integration

When **all** tasks have passed per-task QA:

```bash
grep "^### \[x\]" project-tasks/*-tasklist.md
```

**Delegate prompt (testing-reality-checker):**

> Run final integration testing on the completed system. Cross-check prior QA. Default to **NEEDS WORK** unless evidence strongly supports production readiness.

---

## Task validation process (summary)

### Step 1 — Development

- Pick specialist by task type; ensure **one task** in focus
- Where libraries or CLI setup matter, require **Context7 MCP** for current docs/examples (see *Context7 in dev handoffs* above)
- Confirm deliverables and task marked done in the task list when appropriate

### Step 2 — QA

- **UI:** **EvidenceQA** — task-scoped checks; **screenshot or equivalent** when UI matters
- **API:** **API Tester** ([api-tester](../api-tester/SKILL.md)) — task-scoped endpoints; **evidence** = test run output and/or filled report ([reference](../api-tester/reference.md)); explicit **PASS/FAIL** + feedback

### Step 3 — Loop

| QA | Action |
|----|--------|
| PASS | Validate task; next task; retry counter → 0 |
| FAIL | Retry counter +1; if under 3 attempts, dev again with QA notes; otherwise escalate / block |

### Step 4 — Commit + code review (per task, after QA PASS)

- **`git commit`** scoped to the closed **Txx** (see project `AGENT-ORCHESTRATION` / git-task rules)
- **Code Reviewer** ([code-reviewer](../code-reviewer/SKILL.md)) on that task’s diff — address **🔴** before the next task

### Step 5 — Progression

- No next task until current **QA PASS**, **commit**, and **no blocking code-review findings** (unless user waives review)
- No integration until **all** tasks **PASS** (unless user approves an exception — document it)

---

## Failure management

| Failure | Action |
|---------|--------|
| Agent / delegate unavailable | Retry up to **2** spawns; then document and escalate |
| Implementation keeps failing | Max **3** tries with QA feedback each; then **blocked** task + report (integration may still surface gaps) |
| QA delegate fails | Retry QA; if screenshots fail, request alternate evidence |
| Evidence inconclusive | Treat as **FAIL** (safe default) |

---

## Status report template

```markdown
# Workflow orchestrator status

## Pipeline
- **Current phase**: [PM / ArchitectUX / DevQALoop / Integration / Complete]
- **Project**: [name]
- **Started**: [timestamp]

## Tasks
- **Total**: [X]
- **Completed**: [Y]
- **Current**: [Z] — [description]
- **QA**: [PASS / FAIL / IN_PROGRESS]

## Dev–QA loop
- **Attempt**: [1/2/3]
- **Last QA feedback**: [quote]
- **Post-commit review**: [PENDING / PASS / BLOCKERS — list 🔴]
- **Next action**: [spawn dev / spawn QA / commit / code-review / advance / escalate]

## Quality notes
- **Passed first try**: [X/Y]
- **Evidence artifacts**: [count or links]
- **Major issues**: [list]

## Next steps
- **Immediate**: [action]
- **ETA**: [if known]
- **Blockers**: [if any]

**Status**: [ON_TRACK / DELAYED / BLOCKED]  
**Report time**: [timestamp]
```

## Completion report template

```markdown
# Pipeline completion

## Summary
- **Project**: [name]
- **Duration**: [span]
- **Final status**: [COMPLETED / NEEDS_WORK / BLOCKED]

## Tasks
- **Total**: [X]
- **Completed**: [Y]
- **Retries**: [Z]
- **Blocked**: [list]

## QA
- **Cycles**: [count]
- **Evidence**: [count]
- **Critical fixes**: [count]
- **Integration**: [PASS / NEEDS_WORK]

## Delegates (high level)
- **project-manager-senior**: [note]
- **senior-project-manager** (skill): [note — client-driven **Txx** adds to chat-v2-tasklist]
- **ArchitectUX**: [note]
- **Developers**: [note]
- **EvidenceQA**: [note]
- **testing-reality-checker**: [note]

## Production readiness
- **Status**: [READY / NEEDS_WORK / NOT_READY]
- **Remaining work**: [list]
- **Confidence**: [HIGH / MEDIUM / LOW]

**Completed**: [timestamp]
```

---

## Specialist agent catalog (agency roster)

Use names as **roles** to assign work; exact tooling depends on Cursor/project setup.

**Design & UX:** ArchitectUX, UI Designer, UX Researcher, Brand Guardian, design-visual-storyteller, Whimsy Injector, XR Interface Architect

**Engineering:** Frontend Developer, Backend Architect, engineering-senior-developer, engineering-ai-engineer, Mobile App Builder, DevOps Automator, Rapid Prototyper, XR Immersive Developer, LSP/Index Engineer, macOS Spatial/Metal Engineer

**Marketing:** marketing-growth-hacker, marketing-content-creator, marketing-social-media-strategist, marketing-twitter-engager, marketing-instagram-curator, marketing-tiktok-strategist, marketing-reddit-community-builder, App Store Optimizer

**Product / PM:** project-manager-senior, **Senior Project Manager** ([senior-project-manager](../senior-project-manager/SKILL.md) — append **Txx** to `chat-v2-tasklist.md` from client/spec-aligned scope), Experiment Tracker, Project Shepherd, Studio Operations, Studio Producer, product-sprint-prioritizer, product-trend-researcher, product-feedback-synthesizer

**Support / ops:** Support Responder, Analytics Reporter, Finance Tracker, Infrastructure Maintainer, Legal Compliance Checker, Workflow Optimizer

**Testing / quality:** EvidenceQA, testing-reality-checker, API Tester, **Code Reviewer** ([code-reviewer](../code-reviewer/SKILL.md) — after QA PASS + task commit), Performance Benchmarker, Test Results Analyzer, Tool Evaluator

**Specialized:** XR Cockpit Interaction Specialist, data-analytics-reporter

---

## Single-shot launch (user-facing)

```
Run the full pipeline for project-specs/[project]-setup.md: project-manager-senior → ArchitectUX → [Developer ↔ QA per task: developers use **Context7 MCP** for library/setup accuracy where needed; EvidenceQA for UI, API Tester skill for HTTP API tasks; after each task **QA PASS**: **git commit**, then **Code Reviewer** skill on that task’s changes] → testing-reality-checker. Each task must pass QA and post-commit review (no 🔴 blockers) before advancing.
```

**Chat v2 — new client scope mid-flight:** run **senior-project-manager** first to append **Txx** to `project-tasks/chat-v2-tasklist.md`, then continue Dev ↔ QA on the new or next open task.
