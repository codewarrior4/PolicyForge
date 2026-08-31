# Pennant and Authorization Boundary

PolicyForge will use Laravel Pennant for feature availability, not permission.

Authorization asks:

```text
Is this principal allowed to execute MCP?
```

Pennant asks:

```text
Is MCP released for this principal, organization, or cohort?
```

Both answers matter, but they are not interchangeable.

## Correct Flow

```text
Authenticated
     |
     v
Authorized?
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
Execute
```

## Forbidden Shortcut

```php
if (Feature::active('mcp')) {
    // execute privileged MCP operation
}
```

This is unsafe because feature access is not authorization.

## Safe Shape

```php
$authorization = $authorizationService->inspect($context);

if ($authorization->denied()) {
    return $authorization->toClientResponse();
}

if (! $featureAccess->active('mcp', $context->principal)) {
    return AuthorizationDecision::deny(reason: 'feature_disabled');
}

// Execute privileged operation.
```

The exact API can change during implementation, but the boundary should not.

## PolicyForge Rules

- Pennant may deny access to unreleased features.
- Pennant must never grant access to unauthorized principals.
- Feature checks should be audited when they affect authorization-sensitive decisions.
- Feature state must be rechecked in queued jobs and MCP tools.
- Disabled or unavailable feature infrastructure should fail closed for sensitive operations.

## Package Timing

`laravel/pennant` should be added when the first runtime feature check is implemented. Until then, the boundary is documented here so implementation does not confuse rollout control with access control.
