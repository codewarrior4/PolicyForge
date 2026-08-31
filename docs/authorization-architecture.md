# PolicyForge Authorization Architecture

PolicyForge treats authorization as decision infrastructure, not as controller decoration. Every sensitive operation should be answerable with the same question:

```text
Is this principal allowed to perform this action on this resource in this context?
```

The decision must be explicit, testable, auditable, tenant-aware, feature-aware, and safe by default.

## Current Stack Note

`compulsory.md` names Laravel 12, PHP 8.4, MySQL 8, Redis, and Laravel Pennant as the primary stack. The application currently reports Laravel 13.29.0 and PHP 8.4. The environment has been switched from SQLite to MySQL using the `policyforge` database name, while Pennant and Redis still need to be introduced when their runtime boundaries are implemented.

The architecture below is written for the installed Laravel 13 application while preserving the sprint intent. Pennant, Redis, MySQL, Sanctum, Horizon, Pulse, and Spatie packages should be introduced when their specific boundary becomes active.

## Core Model

```text
Principal
    |
    v
Authentication
    |
    v
Organization / Tenant Context
    |
    v
Authorization Policy
    |
    +---- Permission
    +---- Resource Ownership
    +---- Role
    +---- Feature Flag
    +---- Risk Context
    |
    v
Authorization Decision
    |
    +---- Allow
    +---- Deny
    |
    v
Audit
```

Authentication answers who the caller is. Authorization answers what the caller may do. PolicyForge must keep those concerns separate.

## Context Diagram

```text
Client
  |
  v
Application
  |
  +---- Authentication
  |       Identifies the principal.
  |
  +---- Organization Context
  |       Resolves the active tenant and rejects tenant escape attempts.
  |
  +---- Authorization
  |       Builds context, evaluates policy, permission, ownership, feature,
  |       risk, and returns an explicit decision.
  |
  +---- Resource
  |       Loads data through tenant-scoped access paths.
  |
  v
Database
```

## Decision Flow

```text
Request
  |
  v
Authenticated Principal?
  |
  +---- NO ----> DENY
  |
 YES
  |
  v
Correct Organization?
  |
  +---- NO ----> DENY
  |
 YES
  |
  v
Known Action?
  |
  +---- NO ----> DENY
  |
 YES
  |
  v
Policy Allows Action?
  |
  +---- NO ----> DENY
  |
 YES
  |
  v
Permission Present?
  |
  +---- NO ----> DENY
  |
 YES
  |
  v
Resource Constraint Satisfied?
  |
  +---- NO ----> DENY
  |
 YES
  |
  v
Feature Enabled?
  |
  +---- NO ----> DENY
  |
 YES
  |
  v
Risk Acceptable?
  |
  +---- NO ----> DENY / REQUIRE STEP-UP
  |
 YES
  |
  v
ALLOW
```

## Laravel Authorization Execution Flow

Laravel 13 already provides the core authorization engine through `Illuminate\Auth\Access\Gate`.

1. The caller checks an ability through `Gate::allows`, `Gate::denies`, `Gate::check`, `Gate::inspect`, `Gate::authorize`, `$user->can`, middleware, controller attributes, or a policy-aware helper.
2. `Gate` resolves the current user through its user resolver.
3. `Gate` wraps the arguments and calls registered `before` callbacks. A non-null result short-circuits the decision.
4. If the first argument maps to a model or class policy, `Gate` resolves the policy from explicit registration, a `UsePolicy` attribute, naming convention, or parent mapping.
5. Policy `before` runs when present. A non-null result short-circuits the policy method.
6. The policy method or gate callback runs and returns `bool`, `null`, or `Illuminate\Auth\Access\Response`.
7. `Gate` runs registered `after` callbacks and dispatches `GateEvaluated`.
8. `Gate::inspect` normalizes the result into `Response::allow()` or `Response::deny()`.
9. `Gate::authorize` calls `Response::authorize()`, which throws `AuthorizationException` for denied responses.
10. Laravel converts authorization exceptions into HTTP responses, usually `403`, unless the response supplied a custom status such as `404`.

PolicyForge should use this flow instead of bypassing it. The project-specific decision service can enrich the inputs and outputs, but Laravel's Gate and policies should remain the integration boundary.

## Domain Concepts

### Principal

The actor requesting the operation. Usually a `User`, but the architecture must eventually handle API tokens, MCP clients, service accounts, and queued jobs.

### Action

A named operation such as `users.update`, `organizations.delete`, `api_keys.revoke`, or `mcp.execute`. Actions must be stable strings or enums, not ad hoc controller method names.

### Resource

The object being acted on. It may be an Eloquent model, a class name for create actions, an MCP tool, an API key, an organization, or an internal service operation.

### Policy

The rule object that evaluates whether a principal can perform an action against a resource in context. Policies should return explicit allow/deny responses where denial reason matters.

### Permission

A grantable capability. Permissions are not roles. A role aggregates permissions; a permission is the smallest meaningful capability PolicyForge can assign or revoke.

### Role

A named bundle of permissions within an organization boundary. Roles speed up administration but should not contain hardcoded authorization logic.

### Organization

The tenant boundary. Tenant context must be resolved before resource access and carried through HTTP, API tokens, queues, MCP tools, and internal services.

### AuthorizationContext

The structured input to a decision. It should eventually include principal, active organization, action, resource, request origin, token abilities, feature flags, risk signals, and metadata.

### AuthorizationDecision

The structured output of a decision. It should represent allowed, denied, reason, policy, action, resource, principal, status, and metadata without leaking sensitive internals to clients.

## Package Direction

- Use Laravel policies and Gate as the native execution layer.
- Add `laravel/pennant` when feature flags become part of runtime authorization checks. Pennant may block feature access, but it must not grant permission.
- Consider `spatie/laravel-permission` when roles and permissions move from documentation into database-backed assignments.
- Consider `spatie/laravel-activitylog` or a dedicated audit table when authorization decisions and permission changes need durable audit trails.
- Consider `spatie/laravel-multitenancy` when organization context becomes runtime behavior instead of a design concept.
- Add `laravel/sanctum` for first-party or simple token APIs. Token abilities should be one input to PolicyForge authorization, not the whole authorization system.
- Add `laravel/horizon` after Redis queues exist and job authorization needs operational visibility.
- Add `laravel/pulse` when measuring authorization overhead, slow jobs, and slow endpoints.

## Initial Directory Responsibilities

```text
app/Authorization/
  Contracts/
    Interfaces for policy evaluators, permission resolvers, tenant resolvers,
    risk evaluators, and decision auditors.

  DTOs/
    AuthorizationContext and AuthorizationDecision value objects.

  Enums/
    Stable action names, decision outcomes, denial reasons, and risk levels.

  Exceptions/
    Domain-specific exceptions for missing context, invalid permissions,
    unsafe tenant state, or denied internal operations.

  Policies/
    Domain policies that can be bridged into Laravel Gate policies.

  Services/
    AuthorizationService, PermissionResolver, RoleResolver, TenantContext,
    FeatureAccess, RiskEvaluator, and DecisionAuditor.

  Actions/
    Application actions for permission registration, role assignment,
    permission revocation, and sensitive operation approval.
```

These folders should be created only as implementation begins. Monday records the ownership boundaries; Tuesday can introduce the smallest useful classes.
