# Service Authorization

PolicyForge does not treat internal execution as automatically trusted.

Internal services still need identity, permissions, and resource scope before they can act.

```text
Service Identity
  -> Service Permission
  -> Organization Scope
  -> Authorization Decision
```

## Service Identities

`ServiceIdentity` represents a non-human actor such as:

- `analytics-service`
- `billing-service`
- `mcp-service`
- `notification-service`

Each service declares the permissions it can use and the organization ids it can operate within. A wildcard scope is explicit:

```php
new ServiceIdentity(
    name: 'billing-service',
    permissions: [Permission::AuditExport],
    organizationScope: ['*'],
);
```

## Deny Conditions

`ServiceAuthorizationService` denies when:

- the service does not carry the requested permission
- the service is outside the requested organization scope

Out-of-scope organization access returns a hidden-style denial with status `404`, matching the tenant-isolation posture used for user access.

## Rule

An internal caller is still a caller.

It must prove:

- who it is
- what permission it has
- what organization or resource it is allowed to touch

That keeps future scheduled tasks, listeners, jobs, and MCP tool calls from relying on location-based trust.
