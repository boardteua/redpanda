# Workflow Architect — reference

Templates, registry layouts, discovery checklist, and collaboration protocol. Use with [SKILL.md](SKILL.md).

## Workflow registry — four views

### View 1: By workflow (master list)

```markdown
## Workflows

| Workflow | Spec file | Status | Trigger | Primary actor | Last reviewed |
|---|---|---|---|---|---|
| User signup | WORKFLOW-user-signup.md | Approved | POST /auth/register | Auth service | 2026-03-14 |
| Order checkout | WORKFLOW-order-checkout.md | Draft | UI "Place Order" click | Order service | — |
| Payment processing | WORKFLOW-payment-processing.md | Missing | Checkout completion event | Payment service | — |
```

Status: `Approved` | `Review` | `Draft` | `Missing` | `Deprecated`

- **Missing** — exists in code but no spec (red flag; surface immediately)
- **Deprecated** — replaced; keep row for history

### View 2: By component

```markdown
## Components

| Component | File(s) | Workflows it participates in |
|---|---|---|
| Auth API | src/routes/auth.ts | User signup, Password reset, Account deletion |
| Order worker | src/workers/order.ts | Order checkout, Payment processing |
```

### View 3: By user journey

```markdown
## User Journeys

### Customer Journeys
| What the customer experiences | Underlying workflow(s) | Entry point |
|---|---|---|
| Signs up for the first time | User signup -> Email verification | /register |

### Operator Journeys
| What the operator does | Underlying workflow(s) | Entry point |
|---|---|---|
| Creates a new user manually | Admin user creation | Admin panel /users/new |

### System-to-system journeys
| What happens automatically | Underlying workflow(s) | Trigger |
|---|---|---|
| Trial period expires | Billing state transition | Scheduler cron job |
```

### View 4: By state

```markdown
## State Map

| State | Entered by | Exited by | Workflows that can trigger exit |
|---|---|---|---|
| pending | Entity creation | -> active, failed | Provisioning, Verification |
| active | Provisioning success | -> suspended, deleted | Suspension, Deletion |
```

### Registry maintenance

- Update whenever a workflow is discovered or specced
- Cross-reference all four views
- Keep status current; never delete rows — deprecate instead

## Branch coverage (required)

Every workflow must cover:

1. Happy path
2. Input validation failures (specific errors, user-visible result)
3. Timeout failures (per-step timeout and behavior)
4. Transient failures (retry with backoff where applicable)
5. Permanent failures (fail fast, cleanup)
6. Partial failures (what exists mid-flight, what to roll back)
7. Concurrent conflicts (duplicate create/update)

## Observable states (per step)

Answer for customer, operator, database, and logs.

## Handoff contract template

```
HANDOFF: [From] -> [To]
  PAYLOAD: { field: type, ... }
  SUCCESS RESPONSE: { field: type, ... }
  FAILURE RESPONSE: { error: string, code: string, retryable: bool }
  TIMEOUT: Xs — treated as FAILURE
  ON FAILURE: [recovery action]
```

## Workflow tree spec template

```markdown
# WORKFLOW: [Name]
**Version**: 0.1
**Date**: YYYY-MM-DD
**Author**: Workflow Architect
**Status**: Draft | Review | Approved
**Implements**: [Issue/ticket reference]

## Overview
[2-3 sentences: outcome, trigger, output]

## Actors
| Actor | Role in this workflow |
|---|---|
| Customer | Initiates via UI |
| API Gateway | Validates and routes |

## Prerequisites
- [What must be true before start]

## Trigger
[User action, API, job, event — exact entry]

## Workflow Tree

### STEP 1: [Name]
**Actor**: [who]
**Action**: [what]
**Timeout**: Xs
**Input**: `{ field: type }`
**Output on SUCCESS**: `{ field: type }` -> GO TO STEP 2
**Output on FAILURE**:
  - `FAILURE(validation_error)`: ... -> [recovery]
  - `FAILURE(timeout)`: ... -> [recovery]
  - `FAILURE(conflict)`: ... -> [recovery]

**Observable states during this step**:
  - Customer sees: ...
  - Operator sees: ...
  - Database: ...
  - Logs: ...

### STEP 2: [Name]
[same format]

### ABORT_CLEANUP: [Name]
**Triggered by**: [failure modes]
**Actions** (in order):
  1. [destroy in reverse creation order]
  2. [set statuses / errors]
  3. [notify]
**What customer sees**: ...
**What operator sees**: ...

## State Transitions
```
[pending] -> (success) -> [active]
[pending] -> (fail + cleanup) -> [failed]
```

## Handoff Contracts
### [Service A] -> [Service B]
**Endpoint**: `POST /path`
**Payload** / **Success** / **Failure** / **Timeout**: (JSON shapes as needed)

## Cleanup Inventory
| Resource | Created at step | Destroyed by | Destroy method |
|---|---|---|---|

## Reality Checker Findings
| # | Finding | Severity | Spec section | Resolution |
|---|---|---|---|---|

## Test Cases
| Test | Trigger | Expected behavior |
|---|---|---|

## Assumptions
| # | Assumption | Where verified | Risk if wrong |
|---|---|---|---|

## Open Questions
- ...

## Spec vs Reality Audit Log
| Date | Finding | Action taken |
|---|---|---|
```

## Discovery audit checklist

```markdown
# Workflow Discovery Audit — [Project Name]
**Date**: YYYY-MM-DD
**Auditor**: Workflow Architect

## Entry Points Scanned
- [ ] All API route files (REST, GraphQL, gRPC)
- [ ] All background worker / job processor files
- [ ] All scheduled job / cron definitions
- [ ] All event listeners / message consumers
- [ ] All webhook endpoints

## Infrastructure Scanned
- [ ] Service orchestration (docker-compose, k8s, etc.)
- [ ] Infrastructure-as-code
- [ ] CI/CD pipeline definitions
- [ ] Bootstrap / cloud-init
- [ ] DNS and CDN configuration

## Data Layer Scanned
- [ ] All database migrations
- [ ] Seed / fixture files
- [ ] State machines / status enums
- [ ] Foreign keys (ordering implications)

## Config Scanned
- [ ] Environment variable definitions
- [ ] Feature flags
- [ ] Secrets management
- [ ] Service dependency declarations

## Findings
| # | Discovered workflow | Has spec? | Severity | Notes |
|---|---|---|---|---|
```

## Discovery commands (adapt to stack)

Generic patterns:

```bash
# HTTP routes (examples — adapt)
grep -rn "router\.\(post\|put\|delete\|get\|patch\)" src/routes/ --include="*.ts" --include="*.js"
grep -rn "@app\.\(route\|get\|post\|put\|delete\)" src/ --include="*.py"

# Workers / jobs / consumers
find src/ -type f \( -name "*worker*" -o -name "*job*" -o -name "*consumer*" -o -name "*processor*" \)

# Migrations
find . -path "*/migrations/*" -type f | head -30

# Infra
find . \( -name "*.tf" -o -name "docker-compose*.yml" -o -name "*.yaml" \) -print

# Schedulers
grep -rn "cron\|schedule\|setInterval\|@Scheduled" src/ 2>/dev/null
```

**Laravel (this repo `backend/`):**

```bash
grep -E "Route::(get|post|put|patch|delete)" backend/routes/*.php
grep -rn "dispatch\|ShouldQueue\|implements ShouldQueue" backend/app --include="*.php"
grep -rn "Event::listen\|->listen(" backend/app backend/routes --include="*.php"
find backend/database/migrations -type f -name "*.php"
```

## Agent collaboration (optional)

- **Reality / code verification** — before **Approved**: compare spec order, steps, and failure modes to code; report gaps
- **Backend Architect** — implementation gaps (retries, idempotency, missing cleanup)
- **Security** — workflows touching secrets, auth, or sensitive external calls
- **QA / API tests** — implement rows from the Test Cases table after approval
- **DevOps** — destroy order, infra lifecycle vs cleanup inventory

## File layout (suggested)

```
docs/workflows/
  REGISTRY.md
  WORKFLOW-user-signup.md
  WORKFLOW-order-checkout.md
```

Naming: `WORKFLOW-[kebab-case-name].md`

## Success metrics (reference)

- No undocumented **Missing** workflows left unaddressed for long
- Test cases derivable without extra clarification
- Cleanup inventory complete; failures do not orphan resources
- Assumptions table shrinks as items are verified
