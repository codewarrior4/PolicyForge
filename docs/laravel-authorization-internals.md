# Laravel Authorization Internals

This note summarizes the Laravel 13 authorization internals inspected for PolicyForge.

Inspected classes:

```text
Illuminate\Auth\Access\Gate
Illuminate\Auth\Access\Response
Illuminate\Auth\Access\AuthorizationException
Illuminate\Auth\Access\HandlesAuthorization
Illuminate\Foundation\Auth\Access\AuthorizesRequests
```

## Gate

`Gate` is Laravel's authorization engine. It stores abilities, policies, before callbacks, after callbacks, a user resolver, and the container used to resolve policy classes.

Important methods:

- `define()` registers named ability callbacks.
- `policy()` maps model classes to policy classes.
- `before()` registers global pre-check callbacks.
- `after()` registers global post-check callbacks.
- `allows()`, `denies()`, `check()`, `any()`, and `none()` return boolean authorization answers.
- `inspect()` returns a full `Response`.
- `authorize()` throws an `AuthorizationException` when denied.
- `forUser()` evaluates checks as a specific user.
- `getPolicyFor()` resolves the policy for a model or class.

## Execution Flow

```text
caller
  |
  v
Gate::allows / inspect / authorize
  |
  v
resolve current user
  |
  v
call global before callbacks
  |
  +---- non-null result -> final result
  |
  v
resolve policy or ability callback
  |
  v
call policy before method
  |
  +---- non-null result -> final result
  |
  v
call policy method or gate callback
  |
  v
call global after callbacks
  |
  v
dispatch GateEvaluated event
  |
  v
normalize to Response
  |
  v
return bool, return Response, or throw AuthorizationException
```

## Policy Resolution

Laravel resolves policies in this order:

1. Explicit policy registration.
2. `UsePolicy` attribute on the model class.
3. Guessed policy class names based on conventions.
4. Parent class mappings registered in the policy map.

This supports PolicyForge's goal of keeping authorization organized around resources while still allowing non-model actions through gates.

## Response

`Illuminate\Auth\Access\Response` can represent allow or deny decisions with optional message, code, and HTTP status.

Useful constructors:

```php
Response::allow();
Response::deny();
Response::denyWithStatus(404);
Response::denyAsNotFound();
```

PolicyForge should use response codes/reasons internally for auditability, while keeping client-facing messages generic.

## AuthorizationException

`AuthorizationException` is thrown when `Response::authorize()` is called on a denied response. It can carry the original `Response` and an optional HTTP status.

This matters because PolicyForge can use rich denial reasons internally while still returning `403` or `404` safely.

## HandlesAuthorization

The `HandlesAuthorization` trait gives policy classes convenience helpers:

```php
$this->allow();
$this->deny();
$this->denyWithStatus();
$this->denyAsNotFound();
```

These helpers are useful when writing resource policies with explicit denial reasons.

## AuthorizesRequests

`AuthorizesRequests` is the controller trait that calls the Gate contract from controllers.

It also maps resource controller methods:

```text
index   -> viewAny
show    -> view
create  -> create
store   -> create
edit    -> update
update  -> update
destroy -> delete
```

PolicyForge can use controller authorization as an integration point, but controllers should not own the decision logic.

## PolicyForge Implications

- Keep Laravel Gate and policies as the framework integration layer.
- Use `Gate::inspect()` where the application needs the full decision result.
- Use `Gate::authorize()` where exceptions and standard HTTP handling are appropriate.
- Use policy responses instead of plain booleans when denial reason matters.
- Use `denyAsNotFound()` for cross-tenant resource access when revealing existence is unsafe.
- Use `GateEvaluated` or Gate `after` callbacks later as one possible audit hook.
