# Weekly Content

## X Thread

I spent a week building authorization infrastructure in Laravel.

The biggest lesson:

Authorization is not middleware.

It is a system.

1. Authentication only answers who someone is.
2. Authorization answers what they can do, to which resource, in which context.
3. RBAC is useful, but roles are only bundles of permissions.
4. Multi-tenancy changes everything because the resource boundary matters.
5. IDOR happens when resource lookup is too broad.
6. Policies should make decisions explicit.
7. Queued jobs must re-authorize when they run.
8. MCP tools need the same policy boundary as HTTP.
9. Feature flags can disable access, but must never grant permission.
10. Audit trails turn authorization from a guess into evidence.

The shape I want:

```text
Authenticated
+
Authorized
+
Tenant Scoped
+
Feature Enabled
+
Auditable
=
Allowed
```

## LinkedIn Article

# Authorization Is Infrastructure: Building a Secure Policy System in Laravel

Authentication is not authorization.

A signed-in user can still be the wrong user for a resource, the wrong member for an organization, or the wrong actor for a sensitive operation.

That is the problem PolicyForge is built around.

The system asks:

```text
Is this principal allowed to perform this action on this resource in this context?
```

RBAC helps, but RBAC alone is not enough. A role can say someone is a developer, but it does not prove they can execute this tool for this organization against this resource.

Tenant isolation has to happen close to the data. Instead of fetching a document globally and checking ownership later, PolicyForge resolves tenant-owned resources through the organization relationship.

Queued jobs are another trap. A request can be authorized at dispatch time, then permissions can change before the job runs. The job must reload identity, organization, resource, and permission state before doing the work.

MCP tools need the same treatment. Tool execution is not special. It is another protected action with identity, tenant context, feature availability, validation, execution, and audit.

Feature flags are not permissions. They can make an authorized path unavailable, but they should never make an unauthorized path available.

Audit events are the evidence layer. Every important decision should leave enough context to reconstruct who acted, what they attempted, which organization and resource were involved, and why the system allowed or denied it.

The conclusion is simple:

Authorization belongs across the architecture, not at one controller line.
