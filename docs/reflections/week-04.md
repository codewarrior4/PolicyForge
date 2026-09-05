# Week 04 Reflection

## Most Dangerous Assumption

The most dangerous assumption is that authentication is enough.

A signed-in user can still be outside the tenant boundary, missing permission, using a disabled feature, or replaying a queued action after access changed.

## Remaining Privilege Escalation Risk

Risk remains around future API tokens, persistent audit storage, cache invalidation, and any controller or service that bypasses scoped resource resolution.

## Checks For Policies

Policies should own permission, action, feature, resource-state, ownership, and tenant-match decisions.

## Checks For Database Boundary

Tenant-owned resources should be loaded through tenant relationships or scoped route binding. The query should reduce the attack surface before application code sees the model.

## Sensitive Operations

Sensitive operations include:

- changing email
- resetting authentication
- revoking all devices
- rotating API keys
- transferring organization ownership
- deleting organizations
- exporting customer data
- changing billing configuration

These need normal authorization plus step-up authentication.

## Pennant And Authorization

Pennant-style feature checks can disable a path for users, organizations, or cohorts. They must never grant permission by themselves.

## Infrastructure Failure

When authorization infrastructure fails, protected actions should deny unless fresh trusted state can still be read.

## Redesign At 10 Million Users

I would keep Laravel Gate as the framework adapter, move decision logic into a dedicated authorization service, add tuple-based relationship modeling for complex access, version permission caches, and stream audit events outside the primary database.
