# Authorization Fundamentals

PolicyForge separates authentication from authorization.

Authentication asks:

```text
Who are you?
```

Authorization asks:

```text
What are you allowed to do?
```

The project needs authorization because an authenticated user can still attempt IDOR, tenant escape, role manipulation, permission bypass, feature flag bypass, queue bypass, API bypass, and MCP tool bypass.

## RBAC

Role-based access control assigns capabilities through roles.

RBAC is sufficient when:

- users fit predictable job functions
- permissions change slowly
- resources do not need complex per-object decisions
- tenant boundaries are simple
- the main question is "does this role have this capability?"

RBAC becomes too restrictive when:

- access depends on ownership
- access depends on organization membership
- users have different rights per tenant
- a user can belong to multiple organizations
- the same action should be allowed for one resource but denied for another
- risk, time, token scope, feature state, or approval state matters

For PolicyForge, RBAC is useful for administration, but it is not enough by itself.

## ABAC

Attribute-based access control evaluates attributes about the principal, resource, action, and environment.

ABAC is useful when:

- tenant id must match resource organization id
- API token abilities must limit user abilities
- risk level should change the decision
- feature flags affect availability
- resource state matters, such as locked, archived, disabled, or pending approval
- context changes between HTTP dispatch and queued job execution

PolicyForge needs ABAC because the sprint repeatedly asks for tenant-aware, feature-aware, context-aware decisions.

## ReBAC

Relationship-based access control uses relationships between actors and resources.

ReBAC solves problems such as:

- a user can edit resources they own
- a manager can manage members in their organization
- an organization owner can transfer ownership
- a team member can view resources shared with their team
- service accounts can act only for linked tenants

PolicyForge may not need full Zanzibar-style ReBAC immediately, but it should leave room for relationship-aware checks.

## ACLs

Access control lists attach permissions directly to resources or principals.

ACLs are useful for exceptional grants, but they can become hard to audit at scale. PolicyForge should prefer roles, permissions, policies, and relationships first, then use direct grants only when the product needs them.

## Policy-Based Authorization

Policy-based authorization centralizes decisions in policy objects or services. Laravel's Gate and policies already support this model.

PolicyForge should use policy-based authorization as its backbone because it makes decisions explicit, testable, auditable, and independent from controllers.

## Least Privilege

Every principal gets only the minimum capability required.

PolicyForge should apply least privilege to:

- users
- organization roles
- API tokens
- MCP tools
- service accounts
- queued jobs

## Deny by Default

Unknown principal, unknown tenant, unknown resource, unknown action, missing permission, disabled feature, unacceptable risk, or failed lookup should deny the operation.

The secure failure mode is denial, not accidental authorization.

## Separation of Duties

Sensitive operations should not be controlled by one casual check. Examples include deleting an organization, transferring ownership, granting permissions, revoking passkeys, rotating API keys, and executing privileged MCP tools.

PolicyForge can require extra permission, approval, or step-up authentication for these actions later.

## Can One System Combine RBAC, ABAC, ReBAC, ACLs, and Policies?

Yes.

PolicyForge should combine them carefully:

- RBAC for admin-friendly role assignment
- permissions for named capabilities
- ABAC for tenant, feature, token, risk, and environment context
- ReBAC for ownership and organization relationships
- policies for the executable decision boundary
- ACLs only for exceptional direct grants when needed

The decision service should produce one final `AuthorizationDecision` so callers do not need to understand every internal model.
