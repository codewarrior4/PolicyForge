# Day 1 Reflection

Today is Monday, and PolicyForge is starting authorization as infrastructure rather than scattered checks.

## What is the difference between role and permission?

A permission is a named capability, such as `api_keys.revoke` or `mcp.execute`.

A role is a bundle of permissions, such as Owner, Administrator, Developer, Member, or Viewer.

Roles make administration easier, but permissions are the real capabilities. PolicyForge should not hardcode behavior around role names alone because that creates brittle privilege boundaries.

## Why is authentication not enough?

Authentication only proves who the caller is. It does not prove they are allowed to perform the requested action.

An authenticated user can still be outside the organization, missing the required permission, acting on another user's resource, blocked by a feature flag, or trying a risky sensitive operation.

## Where should authorization happen?

Authorization should happen at every sensitive boundary:

- HTTP controllers and form requests before mutation
- route/model loading for tenant-owned resources
- policies and gates for Laravel-native checks
- application actions and services for reusable domain operations
- queued jobs at execution time
- MCP tools before tool execution
- API token handling before sensitive API behavior

Controllers may call authorization, but they should not own the policy logic.

## Can a feature flag grant permission?

No.

Pennant can answer whether a feature is enabled for a scope. It must not answer whether the principal is allowed to use the feature.

The safe flow is:

```text
authenticated -> authorized -> feature enabled -> execute
```

Both authorization and feature availability must pass.

## What happens when authorization is forgotten in a queue job?

The job can become a privilege bypass.

A user may be denied through HTTP but still dispatch work that mutates data later. Even if the user was authorized when the job was dispatched, permissions, roles, organization membership, feature state, or resource ownership can change before the job runs.

PolicyForge jobs must rehydrate the principal, organization, resource, and action at execution time, then re-authorize before mutation.

## Laravel Internals Learned

Laravel 13's `Gate` resolves the current user, runs global `before` callbacks, resolves a policy or ability callback, runs policy `before` when present, evaluates the ability, runs `after` callbacks, dispatches `GateEvaluated`, and normalizes the result into an authorization `Response`.

`Response::allow()`, `Response::deny()`, `Response::denyWithStatus()`, and `Response::denyAsNotFound()` are important for PolicyForge because the system needs explicit denial reasons internally without leaking sensitive resource existence to clients.

## Monday Outcome

- Authorization architecture drafted.
- Threat model drafted.
- Permission naming convention drafted.
- Initial role boundaries drafted.
- Pennant boundary documented.
- Package direction recorded for Laravel and Spatie additions.
