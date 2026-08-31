# PolicyForge Permissions

Permissions are named capabilities. Roles are bundles of permissions. Policies decide whether a principal may use a permission against a specific resource in a specific context.

PolicyForge should never treat a role name as the authorization architecture.

## Naming Convention

Use dot-separated permission names:

```text
domain.action
domain.subdomain.action
```

Examples:

```text
users.view
users.create
users.update
users.delete
organizations.view
organizations.update
organizations.delete
api_keys.create
api_keys.revoke
mcp.execute
```

Rules:

- Use lowercase ASCII.
- Use plural resource domains.
- Use verbs that describe capabilities, not UI labels.
- Prefer stable names over route names.
- Do not encode tenant ids, user ids, or resource ids in permission names.
- Keep risky operations separate from broad manage permissions.
- Avoid negative permission names such as `users.not_delete`.

## Initial Permission Catalog

### Users

```text
users.view
users.create
users.update
users.delete
users.invite
users.disable
```

### Organizations

```text
organizations.view
organizations.update
organizations.delete
organizations.transfer_ownership
organizations.manage_members
```

### Roles and Permissions

```text
roles.view
roles.assign
roles.revoke
permissions.view
permissions.grant
permissions.revoke
```

### Passkeys

```text
passkeys.view
passkeys.register
passkeys.revoke
```

### Audit

```text
audit.view
audit.export
```

### MCP

```text
mcp.execute
mcp.admin
mcp.tools.view
mcp.tools.manage
```

### API Keys

```text
api_keys.view
api_keys.create
api_keys.revoke
api_keys.rotate
```

### Sensitive Operations

```text
sensitive_operations.approve
sensitive_operations.execute
```

## Initial Role Boundaries

Roles are defaults for administration. They should be persisted as permission mappings once the implementation begins.

### Owner

The highest organization authority. Can manage organization settings, role assignments, billing-sensitive operations if added later, audit access, MCP administration, and ownership transfer. Some destructive actions may still require step-up confirmation.

### Administrator

Can manage most organization resources and members, but cannot transfer ownership or delete the organization unless explicitly granted.

### Developer

Can manage API keys, execute approved MCP tools, view relevant audit entries, and perform technical operations. Should not manage owners or organization deletion.

### Member

Can access ordinary resources assigned to them and manage their own passkeys/API keys where allowed.

### Viewer

Read-only access to permitted resources. No mutation, no role changes, no sensitive operations.

## Suggested Default Mapping

```text
Owner
  organizations.view
  organizations.update
  organizations.delete
  organizations.transfer_ownership
  organizations.manage_members
  users.view
  users.create
  users.update
  users.delete
  users.invite
  users.disable
  roles.view
  roles.assign
  roles.revoke
  permissions.view
  permissions.grant
  permissions.revoke
  passkeys.view
  passkeys.register
  passkeys.revoke
  audit.view
  audit.export
  mcp.execute
  mcp.admin
  mcp.tools.view
  mcp.tools.manage
  api_keys.view
  api_keys.create
  api_keys.revoke
  api_keys.rotate
  sensitive_operations.approve
  sensitive_operations.execute

Administrator
  organizations.view
  organizations.update
  organizations.manage_members
  users.view
  users.create
  users.update
  users.invite
  users.disable
  roles.view
  roles.assign
  roles.revoke
  permissions.view
  passkeys.view
  passkeys.revoke
  audit.view
  mcp.execute
  mcp.tools.view
  api_keys.view
  api_keys.create
  api_keys.revoke
  api_keys.rotate

Developer
  organizations.view
  users.view
  passkeys.view
  audit.view
  mcp.execute
  mcp.tools.view
  api_keys.view
  api_keys.create
  api_keys.revoke
  api_keys.rotate

Member
  organizations.view
  users.view
  passkeys.view
  passkeys.register
  passkeys.revoke
  api_keys.view
  api_keys.create
  api_keys.revoke

Viewer
  organizations.view
  users.view
  audit.view
```

## Policy Rule

A permission is necessary but not always sufficient.

For example, `api_keys.revoke` may allow a principal to revoke their own key. Revoking another user's key should also require organization membership, resource ownership or delegated management authority, and acceptable risk context.

## Package Direction

`spatie/laravel-permission` is a good candidate for storing roles and permissions when this catalog becomes executable. PolicyForge should still keep its own authorization decision layer because the project needs tenant context, resource ownership, feature flags, risk, MCP, queues, and audit metadata around the permission check.
