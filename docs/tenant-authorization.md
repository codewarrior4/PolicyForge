# Tenant Authorization

PolicyForge treats tenant context as a required input to authorization, not as a controller detail. A user can be authenticated and still be denied if the requested organization is missing, ambiguous, or outside their membership boundary.

## Boundary Model

The tenant boundary starts at `Organization`. Tenant-owned resources, such as policy documents, must be resolved through the active organization instead of direct model lookup.

```text
User
  -> organization_user membership
  -> Organization context
  -> Organization policy
  -> Organization-scoped resource query
  -> Authorization decision
```

This protects against horizontal privilege escalation: knowing another tenant's resource id or uuid should not be enough to read it.

## Context Resolution

`OrganizationContextResolver` resolves organization context in this order:

1. Route-bound organization.
2. Explicit `X-Organization-Id` header or `organization_id` query parameter, only when the authenticated user is a member.
3. The user's single organization membership.
4. Unresolved context when no organization can safely be inferred.

If a user belongs to multiple organizations and no explicit context is supplied, the resolver rejects the request as ambiguous.

## Policy Boundary

`OrganizationAccessPolicy` answers the coarse tenant question: can this user access this organization at all? Non-members receive a `404` authorization response so the application does not disclose whether another tenant exists.

Fine-grained permission checks should happen after this boundary, using the authorization decision objects already introduced in PolicyForge.

## Resource Resolution

`TenantScopedResourceResolver` resolves tenant-owned resources through the organization relationship:

```php
$organization->policyDocuments()->whereKey($policyDocumentId)->firstOrFail();
```

That pattern matters. It makes the tenant condition part of the query itself, so a foreign resource fails like a missing resource.

## Useful Industry Patterns

GitHub's organization and repository access model is a useful reference for separating membership from resource-level roles. Stripe Connect's account capability model is useful for thinking about tenant-local enablement. AWS IAM policies are a useful mental model for explicit decisions that combine action, resource, and condition.

PolicyForge is borrowing the shape, not the implementation: organization membership establishes the boundary, resource ownership narrows the query, and permissions decide the allowed action inside that boundary.
