# Authorization Demo

Use this demo flow to explain PolicyForge's authorization infrastructure.

1. Authenticate a user.
2. Resolve organization context.
3. Evaluate the organization policy.
4. Resolve role permissions.
5. Apply organization permission overrides.
6. Check feature availability.
7. Resolve the tenant-owned resource through the organization.
8. Execute an MCP tool only after authorization passes.
9. Re-authorize a queued job at execution time.
10. Emit an audit event.
11. Show a deliberate cross-tenant or revoked-permission attack being denied.

## Demo Attack

Use two organizations and one policy document in each.

Attempt to resolve Organization B's document through Organization A:

```php
$organizationA->policyDocuments()->whereKey($organizationBDocument->id)->firstOrFail();
```

Expected result: not found.

Then queue a delete job while the user is still an owner. Revoke the delete permission before execution.

Expected result: denied before deletion.
