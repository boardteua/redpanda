# Code Reviewer — reference

Optional templates and examples. Keep findings **actionable** and **proportionate** to change size.

## PR summary template (Markdown)

```markdown
## Summary
[1–3 sentences: what changed, risk level, merge recommendation if clear.]

## What’s working well
- …

## Findings

### Blockers
- …

### Suggestions
- …

### Nits
- …

## Questions
- …

## Next steps
- [ ] …
```

## Example comments

**🟡 Suggestion — error handling**

```
🟡 **Reliability: swallowed errors**
`fetchUser` catches all errors and returns `null` without logging or distinguishing network vs 404.

**Why:** Operators and callers cannot tell failure modes apart; debugging production issues becomes guesswork.

**Suggestion:** Narrow the catch, rethrow or map to a typed result, and log structured context at the boundary.
```

**🟡 Suggestion — tests**

```
🟡 **Testing: regression gap**
The new retry path for `submitOrder` has no test; a small timing change could infinite-loop or double-charge.

**Why:** Retry/backoff logic is high-risk and easy to break silently.

**Suggestion:** Add a unit test with a fake clock or injected transport that fails N times then succeeds.
```

**💭 Nit — naming**

```
💭 **Naming**
`data2` in `normalizePayload` is easy to misread next to `data`.

**Suggestion:** Rename to reflect role, e.g. `sanitized` or `parsed`, if you touch this file again.
```

**🔴 Blocker — auth**

```
🔴 **Security: missing authorization**
`DELETE /api/projects/:id` only checks authentication, not that `req.user` may delete this project.

**Why:** Any logged-in user could delete another tenant’s project if they guess the ID.

**Suggestion:** Enforce resource-scoped authZ (membership/role check) before delete; add a test for cross-tenant denial.
```

## When the diff is huge

- Summarize **themes** (e.g. “error handling”, “SQL access”) instead of line-by-line noise
- Call out **highest-risk files** (auth, persistence, concurrency, public API)
- Ask for **follow-up PRs** if a single change mixes refactors with behavior changes
