# Authorization Failure Modes

PolicyForge should fail toward denial when authorization state cannot be trusted.

## Redis Unavailable

If Redis powers permission cache or Pennant state and becomes unavailable, sensitive operations should deny unless the application can read fresh truth from the database.

For non-sensitive reads, a future failover cache store may be acceptable, but only if stale authorization state cannot grant access.

## Database Unavailable

If the database cannot validate membership, resource ownership, role, permission overrides, or token state, the system should deny protected actions.

The exception is public, intentionally unauthenticated behavior.

## Permission Cache Stale

Stale permission cache can grant access after revocation.

Mitigation:

- version cached permission sets
- invalidate cache on membership and override changes
- re-authorize queued jobs at execution time
- bypass cache for sensitive operations until stronger guarantees exist

## User Removed From Organization

Queued jobs, scheduled tasks, listeners, and MCP executions must reload membership before acting.

If membership is gone, the action must stop.

## Permission Revoked While Job Is Queued

`DeletePolicyDocumentJob` carries only ids and the intended permission. On execution it reloads the user, organization, resource, and current role permissions before deleting anything.

If permission was revoked after dispatch, execution is denied.

## Pennant Unavailable

Feature availability must never grant permission.

If feature state cannot be checked for sensitive or high-risk execution, deny. If a feature has no configured state yet, PolicyForge currently treats it as enabled only after permission and tenant checks have already passed.

## Default

When the system cannot prove a protected action is allowed, it should not perform the action.
