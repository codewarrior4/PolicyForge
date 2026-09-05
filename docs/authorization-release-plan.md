# Authorization Release Plan

## Verification Order

1. Run unit and feature tests.
2. Run formatter.
3. Run static analysis when the binary is installed.
4. Run authorization security tests.
5. Run the performance benchmark helper.
6. Review boundary scan results.
7. Keep risky feature gates disabled.
8. Roll out internally.
9. Monitor audit events and denial spikes.
10. Expand rollout.

## Tag

Target tag:

```text
v0.4.0-alpha
```

Create the tag only after the current work is committed and the tree is green.

## Current Local Verification

The local release gate should include:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

PHPStan is not installed in the project, so static analysis needs the package or a CI job before this gate can include it.
