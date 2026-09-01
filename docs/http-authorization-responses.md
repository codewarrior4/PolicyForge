# HTTP Authorization Responses

PolicyForge should distinguish authentication failures, authorization failures, and resource-hiding failures.

## 401 Unauthorized

Use `401` when the principal is not authenticated.

Example:

```text
reason: unauthenticated
status: 401
```

The caller has not proven who they are. PolicyForge should not continue into permission, tenant, or feature evaluation until a principal exists.

## 403 Forbidden

Use `403` when the principal is authenticated but not allowed to perform the action.

Examples:

```text
reason: missing_permission
status: 403
```

```text
reason: feature_disabled
status: 403
```

This is the default denial response for most authorization failures.

## 404 Not Found

Use `404` when confirming that a resource exists would leak information.

Examples:

```text
reason: tenant_access_denied
status: 404
```

```text
reason: resource_outside_organization
status: 404
```

This matters for IDOR and tenant isolation. A user outside the tenant should not learn whether another organization's resource exists.

## Internal vs Client-Facing Detail

Internal decisions may keep specific reasons for audit, such as `missing_permission`, `feature_disabled`, or `tenant_access_denied`.

Client responses should stay generic:

```text
This action is unauthorized.
```

The audit log can be specific. The public response should avoid teaching attackers how to adjust the request.

## Laravel Boundary

Laravel's authorization responses already support denial statuses through `Response::denyWithStatus()` and resource hiding through `Response::denyAsNotFound()`.

PolicyForge's `AuthorizationDecision` can convert to a Laravel authorization response while preserving internal reason codes for audit and later Gate integration.
