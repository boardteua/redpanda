# Backend Architect — reference

Long-form templates and examples. Adapt stack (language, framework, DB, broker) to the **project**; patterns stay the same.

## System architecture specification (Markdown)

```markdown
# System Architecture Specification

## High-level architecture
- **Architecture pattern**: [Microservices / modular monolith / serverless / hybrid]
- **Communication**: [REST / GraphQL / gRPC / event-driven / mix]
- **Data pattern**: [CRUD / CQRS / event sourcing / mix]
- **Deployment**: [containers / serverless / VMs / mix]

## Service decomposition

### Core services (example slots)
**User service** — Authentication, profiles, sessions
- **Database**: [e.g. PostgreSQL] + [encryption notes]
- **APIs**: [REST/GraphQL/gRPC]
- **Events**: [topics and schemas, if any]

**Catalog service** — [Domain entities]
- **Database**: [primary + replicas if read-heavy]
- **Cache**: [e.g. Redis — what is cached, TTL, invalidation]
- **APIs**: [contract style]

**Workflow / orders** — [Long-running or transactional flows]
- **Database**: [ACID expectations]
- **Queue / stream**: [broker, ordering, DLQ]
- **APIs**: [sync + webhooks / callbacks if needed]
```

## Database example (PostgreSQL)

Illustrative e-commerce-style schema. Adjust types and constraints to product rules.

```sql
-- Categories (referenced by products)
CREATE TABLE categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    deleted_at TIMESTAMPTZ NULL
);

CREATE INDEX idx_users_email ON users(email) WHERE deleted_at IS NULL;
CREATE INDEX idx_users_created_at ON users(created_at);

CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL CHECK (price >= 0),
    category_id UUID REFERENCES categories(id),
    inventory_count INTEGER DEFAULT 0 CHECK (inventory_count >= 0),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    is_active BOOLEAN DEFAULT true
);

CREATE INDEX idx_products_category ON products(category_id) WHERE is_active = true;
CREATE INDEX idx_products_price ON products(price) WHERE is_active = true;
CREATE INDEX idx_products_name_search ON products USING gin (to_tsvector('english', name));
```

## API hardening example (Node / Express)

Illustrative: security headers, rate limiting, auth middleware, structured errors. Port idioms to your stack.

```javascript
const express = require('express');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const { authenticate } = require('./middleware/auth');

const app = express();

app.use(helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      scriptSrc: ["'self'"],
      imgSrc: ["'self'", "data:", "https:"],
    },
  },
}));

const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100,
  message: 'Too many requests from this IP, please try again later.',
  standardHeaders: true,
  legacyHeaders: false,
});
app.use('/api', limiter);

app.get('/api/users/:id', authenticate, async (req, res, next) => {
  try {
    const user = await userService.findById(req.params.id);
    if (!user) {
      return res.status(404).json({
        error: 'User not found',
        code: 'USER_NOT_FOUND',
      });
    }
    res.json({
      data: user,
      meta: { timestamp: new Date().toISOString() },
    });
  } catch (error) {
    next(error);
  }
});
```

## Reference targets (tune to product SLOs)

Original rule suggested benchmarks; treat as **starting points**, not universal law:

- API: sub-200ms p95 for typical read paths (when requirements allow)
- Availability: 99.9%+ with monitoring and runbooks
- DB: hot queries indexed; measure p95/p99, not just averages
- Security: no critical findings in audit scope; defense in depth documented

## Topics to reuse from training

- Service decomposition without spaghetti coupling
- Outbox / idempotent consumers for at-least-once delivery
- Read replicas, connection pooling, and N+1 avoidance
- API versioning and deprecation policy
- IaC, secrets management, and least-privilege IAM
