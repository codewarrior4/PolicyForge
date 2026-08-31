# PolicyForge Authorization Threat Model

The primary threat for Week 04 is privilege escalation. PolicyForge must be safe against IDOR, broken access control, horizontal escalation, vertical escalation, tenant isolation failure, mass assignment, role manipulation, permission bypass, feature flag bypass, queue bypass, API bypass, and MCP bypass.

The default posture is deny-by-default. An operation is allowed only when authentication, tenant context, policy, permission, resource constraint, feature state, and risk checks all pass.

## Attack 1: IDOR

```text
GET /api/invoices/1002
```

The user belongs to Organization A, but invoice `1002` belongs to Organization B.

### Risk

If the route resolves `Invoice::findOrFail(1002)` before applying tenant scope, the user may read or mutate another tenant's data.

### Prevention

- Resolve the active organization before loading tenant-owned resources.
- Query resources through tenant-scoped access paths.
- Use scoped route model binding or explicit organization-owned relationships.
- Policies must check that the resource belongs to the active organization.
- Return `404` for resources outside the tenant when revealing existence would leak data.
- Audit denied cross-tenant attempts with principal, action, resource type, resource id, requested organization, active organization, and reason.

### Expected Decision

```text
DENY: resource_outside_organization
```

## Attack 2: Vertical Escalation

```text
DELETE /organizations/1
```

A normal member attempts to delete an organization.

### Risk

Checking only authentication or a loose `is_admin` flag can let users perform owner-only operations.

### Prevention

- Require a named action such as `organizations.delete`.
- Require an organization-scoped permission grant.
- Require policy checks for role boundary, ownership, and separation of duties.
- Treat organization deletion as a sensitive operation that may require step-up authentication or approval.
- Keep role names out of controller logic; ask the authorization layer for a decision.

### Expected Decision

```text
DENY: missing_permission
```

or:

```text
DENY: insufficient_role_boundary
```

## Attack 3: Horizontal Escalation

User A attempts to modify User B's API key.

### Risk

If both users belong to the same organization, a simple tenant check is not enough. The resource may still be owned by a different principal.

### Prevention

- Model API keys as organization-owned and principal-owned.
- Policy checks must verify both tenant membership and resource ownership or delegated management permission.
- Normal users may revoke their own keys; administrators may need `api_keys.manage` to revoke keys for others.
- Audit all API key changes.

### Expected Decision

```text
DENY: resource_owned_by_another_principal
```

## Attack 4: Tenant Escape

The user belongs to Organization A but submits:

```text
organization_id=B
```

### Risk

Mass assignment or trusting request-provided tenant IDs can create or mutate records in another organization.

### Prevention

- Never trust `organization_id` from client input for tenant-owned writes.
- Derive organization IDs from the active tenant context.
- Exclude privileged fields such as `organization_id`, `role`, and `permissions` from mass assignment.
- Validate route tenant and session/token tenant together.
- Log any mismatch between requested organization and active organization.

### Expected Decision

```text
DENY: tenant_context_mismatch
```

## Attack 5: Background Job Bypass

A user cannot delete a resource through HTTP, but a malicious request queues:

```text
DeleteResourceJob
```

### Risk

If the HTTP layer authorizes dispatch but the job blindly executes later, permission revocation or tenant membership changes between dispatch and execution may be ignored.

### Prevention

- Jobs must carry only the minimum identity and context needed to re-authorize.
- Rehydrate principal, organization, and resource at execution time.
- Re-run the same authorization decision before mutation.
- Deny if the user was removed, the organization was disabled, the permission was revoked, the resource moved tenants, or the feature is no longer enabled.
- Audit job-time authorization failures.

### Expected Decision

```text
DENY: permission_revoked_after_dispatch
```

or:

```text
DENY: stale_authorization_context
```

## Additional Abuse Cases

### Role Manipulation

Users submit a payload containing `role=Owner` or direct permission grants.

Prevention: only privileged role-assignment actions may alter roles; role and permission fields are never mass assignable from ordinary profile/resource requests.

### Permission Bypass

Code checks only ownership and forgets capability.

Prevention: sensitive actions require both policy/resource constraints and permissions. Tests should prove that ownership alone does not grant administrative capabilities.

### Feature Flag Bypass

Code treats a feature flag as permission.

Prevention: Pennant can only answer whether a feature is released. Authorization must still answer whether the principal may use it.

### MCP Tool Bypass

An MCP tool executes privileged application behavior without calling the authorization service.

Prevention: every MCP tool maps to a named action such as `mcp.execute` plus a specific tool identifier. Tool handlers must authorize before reading or mutating data.

### Internal Service Bypass

An internal service calls a lower-level repository directly and avoids the decision layer.

Prevention: sensitive operations are exposed as application actions that require `AuthorizationContext`, not as raw model writes.

## Audit Requirements

Authorization audit entries should capture:

- decision id
- allowed or denied outcome
- denial reason
- principal type and id
- organization id
- action
- resource type and id
- policy name
- feature checks involved
- risk level
- request id or job id
- token id when applicable
- timestamp

Client responses should remain generic. Internal audit records may be specific.
