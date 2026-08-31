# PolicyForge

PolicyForge is a Laravel authorization infrastructure project for building tenant-aware, policy-driven, auditable access control across users, organizations, APIs, background jobs, and MCP tools.

The project is focused on answering one core question consistently:

```text
Is this principal allowed to perform this action on this resource in this context?
```

## Current Stack

- PHP 8.4
- Laravel 13
- MySQL
- PHPUnit
- Laravel Boost
- Laravel MCP package present in the installed dependency tree
- Tailwind CSS 4 and Vite

Planned package direction includes Laravel Pennant for feature availability, Spatie Permission for role and permission persistence, Spatie Activitylog or a dedicated audit table for authorization auditing, Sanctum for API tokens, and Horizon/Pulse once queues and runtime observability become part of the work.

## Authorization Focus

PolicyForge treats authorization as infrastructure, not as scattered `Gate::allows()` calls.

The authorization model is expected to account for:

- authenticated principals
- organization and tenant context
- stable actions and permissions
- roles as permission bundles
- resource ownership
- feature availability
- risk context
- explicit allow or deny decisions
- audit records

The default posture is deny-by-default.

## Monday Design Notes

The initial authorization planning lives in:

- [Authorization Architecture](docs/authorization-architecture.md)
- [Authorization Fundamentals](docs/authorization-fundamentals.md)
- [Laravel Authorization Internals](docs/laravel-authorization-internals.md)
- [Authorization Domain Model](docs/authorization-domain-model.md)
- [Authorization Threat Model](docs/authorization-threat-model.md)
- [Permissions](docs/permissions.md)
- [Pennant and Authorization Boundary](docs/pennant-authorization-boundary.md)
- [Day 1 Reflection](docs/reflections/day-1.md)

## Local Setup

Install dependencies:

```bash
composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `policyforge`, then run migrations:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS policyforge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

Run tests:

```bash
php artisan test --compact
```

## Development Notes

- Keep authorization logic outside controllers where possible.
- Use Laravel policies and Gate as framework integration points.
- Do not use feature flags as permissions.
- Re-authorize queued jobs at execution time.
- Treat tenant context as required for tenant-owned resources.
- Audit sensitive authorization decisions and permission changes.
