# Authorization Production Checklist

## Security

- Deny by default when identity, organization, permission, feature state, or resource scope cannot be proven.
- Resolve tenant-owned resources through the organization boundary.
- Keep policies and gates as framework integration points.
- Validate permission names against the registry.
- Re-authorize queued jobs at execution time.
- Require step-up authentication for sensitive operations.
- Emit audit events for allowed, denied, tenant-denied, privilege-escalation, and sensitive-action attempts.
- Never log secrets, credentials, raw tokens, or API keys in audit metadata.

## Infrastructure

- Use MySQL with foreign keys and indexes for organization, membership, resources, and overrides.
- Introduce Redis only with a clear fail-closed cache strategy for authorization.
- Use queue workers with sane retry and timeout settings.
- Add Horizon when Redis-backed queues are active.
- Add monitoring for denial spikes, queue authorization failures, and tenant-access-denied events.

## Operations

- Prepare emergency permission revocation.
- Invalidate permission cache on membership and override changes.
- Keep an incident response runbook for privilege escalation and tenant isolation bugs.
- Roll back risky authorization changes behind feature flags where possible.
- Keep disabled-by-default rollout for new high-risk features.

## Release Gate

- Run full tests.
- Run formatter.
- Run static analysis when installed.
- Run security tests.
- Review authorization boundary scan.
- Confirm performance benchmark shape.
- Confirm production failure mode notes.
- Tag release only from a committed, green tree.
