# Principal Challenge Authorization

PolicyForge at large scale must change from application-local checks to a dedicated authorization architecture.

Assumed scale:

- 10 million users
- 100,000 organizations
- 1,000 permissions
- 50 roles
- 10,000 authorization decisions per second

## 1. Continue Using Laravel Gate?

Yes, as the Laravel integration point.

No, as the only authorization engine.

Gate remains useful for controllers, policies, and framework conventions. The decision engine should become a dedicated service with clear inputs, outputs, caching, and audit behavior.

## 2. Dedicated Authorization Service?

Yes.

At this scale, authorization needs its own service boundary or at least a separately deployable module. It should own policy evaluation, relationship checks, cache invalidation, and audit emission.

## 3. Zanzibar-Style Authorization?

Yes, for relationship-heavy access.

Zanzibar-style tuples are useful when access depends on relationships like user belongs to team, team belongs to organization, organization owns resource, and role grants permission.

PolicyForge can keep permission enums for application actions while moving relationship resolution into a tuple-based model.

## 4. Relationship Model

Model relationships as facts:

```text
user:1 member organization:10
team:2 member organization:10
user:1 member team:2
organization:10 owner policy-document:99
role:developer grants mcp.execute
```

Then decisions compose facts with policy rules.

## 5. Safe Decision Caching

Cache effective permissions and relationship expansions, not irreversible final decisions for sensitive operations.

Cache keys should include:

- principal id
- organization id
- resource type
- role version
- permission version
- feature version

## 6. Cache Invalidation

Invalidate on:

- role assignment change
- permission override change
- organization membership change
- resource ownership change
- user disabled
- token revoked
- feature state change

Use versioned keys so stale entries become unreachable.

## 7. Prevent Stale Grants

For sensitive operations:

- bypass stale caches
- require fresh membership
- require step-up authentication
- recheck at execution time for queues

For normal operations:

- use short TTLs
- use versioned permission snapshots
- emit events on every revocation

## 8. Audit Billions Of Decisions

Do not write every decision synchronously to the primary database.

Use a stream:

```text
application -> event stream -> partitioned storage -> query/index layer
```

Store high-risk denials and sensitive operations with stronger durability. Sample low-risk allows if storage costs require it.

## 9. MCP Consumption

MCP tools should call the same authorization service before execution.

Each tool needs:

- authenticated actor
- organization context
- tool id
- required permission
- feature state
- input validation
- audit result

## 10. Zero-Downtime Migration

1. Run old and new authorization paths in shadow mode.
2. Compare decisions.
3. Log mismatches.
4. Fix policy gaps.
5. Enable new path for internal tenants.
6. Gradually expand by feature flag.
7. Keep rollback available.
8. Remove old path only after mismatch rate is acceptable.
