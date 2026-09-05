# Authorization Incidents

## Privilege Escalation Discovered

1. Disable the affected feature.
2. Identify affected users and organizations.
3. Revoke the suspect roles, permissions, tokens, and sessions.
4. Review audit events and request logs.
5. Patch the authorization boundary.
6. Add a regression test for the exact bypass.
7. Deploy the fix.
8. Investigate historical exploitation.
9. Notify affected parties when required.

## Tenant Isolation Bug

Answer these questions immediately:

- Which tenants were exposed?
- Which resources were accessed?
- Which principals made the requests?
- Which request ids, jobs, MCP tools, or services were involved?
- Did the response disclose existence, content, or mutation success?
- What logs prove the scope?

Containment:

1. Disable affected endpoints, MCP tools, or queued actions.
2. Stop processing queued jobs that can touch the exposed resource type.
3. Revoke affected API tokens.
4. Patch tenant scoping at the query boundary.
5. Add IDOR and route-binding regression tests.
6. Backfill audit analysis for the exposure window.

## Stale Authorization State

If stale cache or stale queued context granted access:

- invalidate all related authorization caches
- force fresh membership and permission reads
- pause risky queues
- add versioned cache keys before re-enabling cached decisions

## Evidence To Preserve

- audit events
- request ids
- actor ids
- organization ids
- resource ids
- queued job ids
- feature flag state
- permission override history
- deployment timestamp
