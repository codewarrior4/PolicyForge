# Authorization Red-Team Report

This report records the abuse cases tested against PolicyForge's authorization infrastructure.

## Attempts

| Attempt | Expected Result | Current Control |
| --- | --- | --- |
| Modify another user's resource | deny | Resource access must pass policy context and ownership metadata. |
| Modify another organization's resource | hide | Tenant-owned documents resolve through `Organization::policyDocuments()`. |
| Promote self to owner | deny | Role changes are not trusted as request input; platform permissions stay guarded. |
| Add own permissions | deny | Organization overrides cannot grant platform-level permissions. |
| Call admin endpoint directly | deny | Gates and policies must sit behind route/controller entry points. |
| Execute disabled MCP tool | deny | MCP execution checks permission and feature availability before execution. |
| Replay queued operation | deny | Jobs reload membership, organization, resource, and permission at execution time. |
| Manipulate organization id | hide | Context resolver rejects organizations outside membership. |
| Manipulate user id | deny | User identity comes from authenticated principal or queued context, not arbitrary payload. |
| Bypass policies through direct model access | flag | Direct tenant-owned lookup is treated as a review issue unless scoped by organization. |

## Verified By Tests

- `AuthorizationSecurityTest`
- `FullAuthorizationMatrixTest`
- `TenantIsolationTest`
- `QueueAuthorizationTest`
- `McpAuthorizationTest`
- `SensitiveOperationAuthorizerTest`

## Code Review Findings

The boundary scan did not find unrestricted tenant-owned resource lookup followed by late authorization.

Acceptable direct lookups remain in queued authorization because the job first reloads identity records by id, then resolves the tenant-owned policy document through the organization relationship.

## Remaining Risks

- API token authorization is still planned; tokens need organization scope and revocation checks.
- Pennant is not installed yet; feature state is represented through metadata and a resolver boundary.
- Persistent audit storage is not built yet; audit events exist and can be routed to storage later.
- Authorization cache invalidation needs implementation after caching is introduced.
