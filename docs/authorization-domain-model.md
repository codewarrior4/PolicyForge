# PolicyForge Authorization Domain Model

This document defines the Monday design vocabulary for the authorization layer.

## Principal

The actor attempting an operation.

Initial principal type:

- authenticated user

Future principal types:

- API token
- service account
- MCP client
- queued job actor

The principal must be known before authorization can continue.

## Action

A stable operation name such as:

```text
organizations.delete
api_keys.revoke
mcp.execute
audit.view
```

Actions should become PHP enums during implementation so typos cannot silently create new abilities.

## Resource

The thing being acted on.

Examples:

- organization
- user
- passkey
- API key
- audit event
- MCP tool
- queued job target

Create actions may use a class name as the resource. Instance actions should use a tenant-scoped model or value object.

## Policy

The executable rule boundary.

A policy can consider:

- principal
- action
- resource
- active organization
- permission grants
- role membership
- resource ownership
- feature state
- risk context
- token abilities

Policies should not depend on raw request payloads. Convert request data into explicit context first.

## Permission

A grantable capability with a stable name.

Permissions answer:

```text
Does this principal have this named capability in this organization?
```

Permissions do not answer:

```text
Does this resource belong to the principal?
Is the feature enabled?
Is the operation safe right now?
```

Those are policy/context questions.

## Role

A named bundle of permissions in an organization.

Roles should be easy to explain to humans and easy to map to permissions. They should not hide extra business logic.

## Organization

The tenant boundary.

Every tenant-owned resource must be accessed through a known organization context. Client-supplied `organization_id` must not be trusted for writes.

## AuthorizationContext

The structured input to the decision engine.

Likely fields:

```text
principal
organization
action
resource
permission
feature
token
risk
request_id
metadata
```

Implementation should keep context immutable after creation so audit records match the evaluated input.

## AuthorizationDecision

The structured output from the decision engine.

Likely fields:

```text
allowed
reason
policy
action
resource_type
resource_id
principal_type
principal_id
organization_id
status
metadata
```

Example design:

```php
AuthorizationDecision::allow();

AuthorizationDecision::deny(
    reason: 'resource_outside_organization',
);
```

The public client response should not expose sensitive internals. Internal audit should keep the specific reason.

## Contracts

`app/Authorization/Contracts` should contain interfaces for:

- authorization service
- permission resolver
- role resolver
- tenant context resolver
- feature access checker
- risk evaluator
- decision auditor

## DTOs

`app/Authorization/DTOs` should contain:

- `AuthorizationContext`
- `AuthorizationDecision`

These should be small, typed value objects.

## Enums

`app/Authorization/Enums` should contain:

- action names
- decision outcomes
- denial reasons
- risk levels

## Exceptions

`app/Authorization/Exceptions` should contain domain exceptions for invalid or unsafe authorization state, not ordinary denials.

Examples:

- missing active organization context
- unknown permission name
- malformed action
- unsafe tenant context

## Policies

`app/Authorization/Policies` should contain PolicyForge domain policies that can be bridged into Laravel policies and Gate abilities.

## Services

`app/Authorization/Services` should contain orchestration and resolver services.

Examples:

- `AuthorizationService`
- `PermissionResolver`
- `RoleResolver`
- `TenantContext`
- `FeatureAccess`
- `RiskEvaluator`
- `DecisionAuditor`

## Actions

`app/Authorization/Actions` should contain application operations that mutate authorization state.

Examples:

- register permissions
- assign role
- revoke role
- grant permission
- revoke permission
- approve sensitive operation

These actions must authorize themselves before mutating authorization state.
