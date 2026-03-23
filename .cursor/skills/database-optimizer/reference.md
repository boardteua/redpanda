# Database Optimizer — reference patterns

Copy/adapt to the project’s SQL dialect and ORM.

---

## 1. Indexed schema (PostgreSQL-style)

```sql
-- Indexed FKs, constraints explicit
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_users_created_at ON users(created_at DESC);

CREATE TABLE posts (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(500) NOT NULL,
    content TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    published_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_posts_user_id ON posts(user_id);

CREATE INDEX idx_posts_published
ON posts(published_at DESC)
WHERE status = 'published';

CREATE INDEX idx_posts_status_created
ON posts(status, created_at DESC);
```

---

## 2. Query optimization with EXPLAIN

```sql
-- Bad: N+1 pattern in logic
-- SELECT * FROM posts WHERE user_id = 123;
-- then per post: SELECT * FROM comments WHERE post_id = ?

-- Better: one round-trip with aggregation (Postgres)
EXPLAIN ANALYZE
SELECT
    p.id, p.title, p.content,
    json_agg(json_build_object(
        'id', c.id,
        'content', c.content,
        'author', c.author
    )) AS comments
FROM posts p
LEFT JOIN comments c ON c.post_id = p.id
WHERE p.user_id = 123
GROUP BY p.id;
```

**Plan reading (Postgres):** prefer Index Scan / Bitmap Index Scan over Seq Scan on large tables; compare estimated vs actual rows; watch for mis-estimates that push bad join order.

---

## 3. N+1 in application code

```typescript
// Bad: N+1
const users = await db.query("SELECT * FROM users LIMIT 10");
for (const user of users) {
  user.posts = await db.query(
    "SELECT * FROM posts WHERE user_id = $1",
    [user.id]
  );
}

// Better: single query with aggregation
const usersWithPosts = await db.query(`
  SELECT
    u.id, u.email, u.name,
    COALESCE(
      json_agg(
        json_build_object('id', p.id, 'title', p.title)
      ) FILTER (WHERE p.id IS NOT NULL),
      '[]'
    ) AS posts
  FROM users u
  LEFT JOIN posts p ON p.user_id = u.id
  GROUP BY u.id
  LIMIT 10
`);
```

---

## 4. Safer migrations (Postgres-oriented)

```sql
BEGIN;

ALTER TABLE posts
ADD COLUMN view_count INTEGER NOT NULL DEFAULT 0;

COMMIT;

-- Prefer CONCURRENTLY to reduce blocking (run outside a transaction block)
CREATE INDEX CONCURRENTLY idx_posts_view_count
ON posts(view_count DESC);
```

Avoid adding nullable columns without defaults then backfilling in a way that locks the table for long periods; prefer additive, online-friendly steps.

---

## 5. Supabase client + pooler hint

```typescript
import { createClient } from '@supabase/supabase-js';

const supabase = createClient(
  process.env.SUPABASE_URL!,
  process.env.SUPABASE_ANON_KEY!,
  {
    db: { schema: 'public' },
    auth: { persistSession: false },
  }
);

// Transaction pooler port (example — confirm in project docs)
const pooledUrl = process.env.DATABASE_URL?.replace('5432', '6543');
```

Use the pooler URL and mode (session vs transaction) that match serverless vs long-lived workers.
