# Authorization Performance

PolicyForge now has a small benchmark helper for comparing authorization paths:

- no authorization
- policy and permission
- policy, permission, and enabled feature
- policy, permission, and disabled feature

The helper lives in `AuthorizationPerformanceBenchmark`.

## Current Measurement Shape

The benchmark records:

- whether the decision allowed the action
- elapsed microseconds for the decision path

This is intentionally simple. It gives the project a stable interface before Redis, Pennant persistence, cached permissions, or audit writes are added.

## Local Sample

One local run through Laravel Tinker produced:

| Path | Allowed | Microseconds |
| --- | --- | ---: |
| `no_authorization` | yes | `0.416` |
| `policy` | yes | `719.542` |
| `policy_permission_feature` | yes | `5.583` |
| `policy_permission_feature_denied` | no | `2.625` |

Treat these as shape checks, not production numbers. Proper benchmarking needs repeated runs, warmed services, representative database state, and the same cache driver expected in production.

## What To Measure Next

When caching and feature storage are introduced, benchmark:

- latency
- database queries
- cache reads
- cache misses
- memory use
- denied-path cost

The denied path matters because secure systems deny often: missing tenant context, revoked permissions, disabled features, stale jobs, and suspicious service calls should all be cheap and predictable.

## Cache Posture

Permission caching must never trade correctness for speed.

Safe caching rules:

- cache effective permissions by user and organization
- include a version or timestamp that changes on membership and override updates
- invalidate on role change, permission override change, organization removal, and user disablement
- deny when cache state cannot be trusted for sensitive operations

For this project, cache-backed authorization should arrive after the uncached decision path is fully tested.
