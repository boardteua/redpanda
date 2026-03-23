# API Tester — reference

Expanded methodology, report template, and automation examples. Use with [SKILL.md](SKILL.md).

## OWASP API Security (areas to exercise)

Use as a checklist; adapt to your API’s threat model:

- Broken object level authorization (BOLA/IDOR)
- Broken authentication / weak tokens / session issues
- Broken object property level authorization
- Unrestricted resource consumption (rate limits, payload size)
- Broken function level authorization
- Unrestricted access to sensitive business flows
- Server-side request forgery (SSRF) where URLs are accepted
- Security misconfiguration (verbose errors, default admin, CORS)
- Improper inventory management (shadow/undocumented endpoints)
- Unsafe consumption of third-party APIs

## Example SLA targets (define per project)

These appeared in the source rule as **examples**—replace with product/engineering agreements:

- Latency: e.g. P95 response time under **X ms** for critical paths
- Load: e.g. sustain **N×** baseline traffic without breaching error budget
- Errors: e.g. error rate under **Y%** under agreed load
- Automation: high coverage of **public** and **authenticated** APIs; full suite runtime budget for CI

## Testing report template

```markdown
# [API Name] Testing Report

## Test coverage analysis
**Functional**: [Endpoints / scenarios covered; gaps]
**Security**: [Auth, authz, validation, abuse cases]
**Performance**: [Scenarios, SLAs, results]
**Integration**: [External services, mocks, fallbacks]

## Performance results
**Latency**: [P50/P95/P99 or equivalent]
**Throughput**: [RPS, concurrency level]
**Stability**: [Duration, error rate under load]
**Notes**: [Bottlenecks, DB, cache]

## Security assessment
**Authentication**: [...]
**Authorization**: [...]
**Input handling**: [...]
**Rate limiting / abuse**: [...]

## Issues and recommendations
**Critical**: [...]
**High / Medium / Low**: [...]
**Optimizations**: [...]

**Tester**: [Name or agent]
**Date**: [YYYY-MM-DD]
**Quality status**: PASS | FAIL (with reasoning)
**Release readiness**: Go | No-Go (with supporting data)
```

## Workflow (expanded)

1. **Discovery** — Catalog endpoints (OpenAPI, route files); dependencies; PII/sensitive flows  
2. **Strategy** — Matrices for methods, roles, error codes; synthetic data; env parity  
3. **Implementation** — Automate; add contract tests (schema/status) where useful; security and load suites  
4. **Improvement** — Monitor prod if in scope; tighten tests from incidents; keep runtime/flakiness under control  

## Laravel feature test sketch (PHP)

```php
public function test_protected_route_requires_auth(): void
{
    $this->getJson('/api/v1/example')->assertStatus(401);
}

public function test_validation_errors_are_structured(): void
{
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/example', [])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors']);
}
```

Adjust guard (`sanctum`, `web`), paths, and assertion shapes to match the app and OpenAPI.

## Example: Playwright / fetch suite (Node)

Prefer PHPUnit/Pest for `backend/` unless the task explicitly needs Node black-box tests. Full pattern from the source rule:

```javascript
// Advanced API test automation with security and performance
import { test, expect } from '@playwright/test';
import { performance } from 'perf_hooks';

describe('User API Comprehensive Testing', () => {
  let authToken;
  let baseURL = process.env.API_BASE_URL;

  test.beforeAll(async () => {
    const response = await fetch(`${baseURL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'secure_password',
      }),
    });
    const data = await response.json();
    authToken = data.token;
  });

  test.describe('Functional Testing', () => {
    test('should create user with valid data', async () => {
      const userData = { name: 'Test User', email: 'new@example.com', role: 'user' };
      const response = await fetch(`${baseURL}/users`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${authToken}`,
        },
        body: JSON.stringify(userData),
      });
      expect(response.status).toBe(201);
      const user = await response.json();
      expect(user.email).toBe(userData.email);
      expect(user.password).toBeUndefined();
    });

    test('should handle invalid input gracefully', async () => {
      const invalidData = { name: '', email: 'invalid-email', role: 'invalid_role' };
      const response = await fetch(`${baseURL}/users`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${authToken}`,
        },
        body: JSON.stringify(invalidData),
      });
      expect(response.status).toBe(400);
      const error = await response.json();
      expect(error.errors).toBeDefined();
    });
  });

  test.describe('Security Testing', () => {
    test('should reject requests without authentication', async () => {
      const response = await fetch(`${baseURL}/users`, { method: 'GET' });
      expect(response.status).toBe(401);
    });

    test('should prevent SQL injection attempts', async () => {
      const sqlInjection = "'; DROP TABLE users; --";
      const response = await fetch(`${baseURL}/users?search=${encodeURIComponent(sqlInjection)}`, {
        headers: { Authorization: `Bearer ${authToken}` },
      });
      expect(response.status).not.toBe(500);
    });

    test('should enforce rate limiting', async () => {
      const requests = Array(100)
        .fill(null)
        .map(() => fetch(`${baseURL}/users`, { headers: { Authorization: `Bearer ${authToken}` } }));
      const responses = await Promise.all(requests);
      const rateLimited = responses.some((r) => r.status === 429);
      expect(rateLimited).toBe(true);
    });
  });

  test.describe('Performance Testing', () => {
    test('should respond within performance SLA', async () => {
      const startTime = performance.now();
      const response = await fetch(`${baseURL}/users`, {
        headers: { Authorization: `Bearer ${authToken}` },
      });
      const responseTime = performance.now() - startTime;
      expect(response.status).toBe(200);
      expect(responseTime).toBeLessThan(200);
    });

    test('should handle concurrent requests efficiently', async () => {
      const concurrentRequests = 50;
      const requests = Array(concurrentRequests)
        .fill(null)
        .map(() =>
          fetch(`${baseURL}/users`, { headers: { Authorization: `Bearer ${authToken}` } }),
        );
      const startTime = performance.now();
      const responses = await Promise.all(requests);
      const endTime = performance.now();
      const allSuccessful = responses.every((r) => r.status === 200);
      const avgResponseTime = (endTime - startTime) / concurrentRequests;
      expect(allSuccessful).toBe(true);
      expect(avgResponseTime).toBeLessThan(500);
    });
  });
});
```

Note: `test.beforeAll` / `test.describe` follow Playwright Test API; align `baseURL`, paths, and status expectations with the real API.

## Contract testing notes

- Diff **OpenAPI** (`docs/chat-v2/openapi.yaml`) vs actual responses: required fields, enums, error schema  
- For versioning: backward-compatible changes vs breaking changes; consumer-driven contracts when multiple clients exist  

## Integration and documentation

- Execute documented curl/examples; fix doc or code when they diverge  
- Third-party APIs: timeouts, retries, idempotency keys, fallback behavior  

## Success metrics (reference)

- High coverage of documented endpoints and critical paths  
- No **critical** security issues unmitigated before release  
- Performance within agreed SLOs under test load  
- Majority of regressions caught in CI rather than production  
