# Upgrading from 0.0.43 to 1.0

Gustav 1.0 is a deliberate reset of the pre-1.0 application API. Upgrade one
area at a time and keep the application running between steps.

## 1. Update the package constraint

Require Gustav 1.0 and refresh the complete dependency graph:

```json
{
    "require": {
        "php": "^8.2",
        "gustav-php/gustav": "^1.0@RC"
    }
}
```

```bash
composer update --with-all-dependencies
```

The `@RC` stability flag is required while 1.0 is a release candidate. Remove
it after the stable 1.0.0 release is available.

PHP-DI and Latte are no longer framework dependencies. Remove application code
that configures either package unless you use it independently.

## 2. Use the shared project bootstrap

Create `app/bootstrap.php`:

```php
<?php

use GustavPHP\Gustav\Configuration;

return Configuration::forProject(
    namespace: 'App',
    root: dirname(__DIR__),
);
```

Then start the HTTP worker from `app/index.php`:

```php
<?php

namespace App;

require_once __DIR__ . '/../vendor/autoload.php';

use GustavPHP\Gustav\Application;

Application::run(require __DIR__ . '/bootstrap.php');
```

The `gustav` CLI loads the same bootstrap automatically.

## 3. Convert routes to controller attributes

Mark controller classes with `#[Controller]` and replace generic route
declarations with `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]`,
`#[Head]`, or `#[Options]`.

```php
#[Controller('/users')]
final readonly class Users
{
    #[Get('/{user}', name: 'users.show')]
    public function show(#[Param('user')] int $user): UserOutput
    {
        // ...
    }
}
```

Controller arguments and return values must be typed. Gustav validates route
signatures during application startup.

## 4. Move service setup into application classes

Use `#[Service]` for discoverable implementations and `#[Factory]` when a
service needs construction logic. Keep `Service\Provider` only for explicit or
third-party registrations. Constructor injection is available to controllers,
commands, listeners, middleware, factories, and exception handlers.

Choose service lifetimes deliberately: singleton for application-wide state,
scoped for one request or command, and transient for a new instance on every
resolution.

## 5. Adopt typed input and validation

Use `#[Body]`, `#[Query]`, `#[Param]`, `#[Header]`, `#[Cookie]`, `#[Request]`,
and `#[AuthUser]` on controller parameters. Constructor-based readonly DTOs are
supported for body and query input. Unknown fields and invalid scalar or enum
values now produce structured client errors instead of reaching controller
code.

Attach repeatable `#[Validate]` attributes to parameters or DTO constructor
fields. Expected validation failures use HTTP 422, malformed JSON uses HTTP
400, and unsupported required body media types use HTTP 415.

## 6. Return typed values directly

Arrays, scalars, backed enums, and DTOs are serialized as JSON when declared as
the controller return type. Return a PSR-7 response or use the response helper
when status codes or headers need to be customized.

## 7. Replace Latte templates

Rename templates to `.phtml` and use Gustav's native layouts, sections,
partials, escaping helper, and `SafeHtml` wrapper. Templates execute PHP
directly: escape every untrusted value with `$view->escape()` or `$view->e()`.
Use `SafeHtml` or `$view->raw()` only for deliberately trusted markup.

## 8. Replace legacy events

Dispatch plain typed event objects through
`Psr\EventDispatcher\EventDispatcherInterface`. Listeners are invokable classes
marked with `#[Listener]`; they may receive constructor dependencies.

## 9. Review errors, sessions, and production configuration

Use typed `HttpException` subclasses for expected HTTP failures and
`#[ExceptionHandler]` classes for application exceptions. Unexpected failures
remain production-safe and are logged with an `X-Request-ID`.

Session-backed routes can inject `Session`; add `#[Csrf]` to state-changing
browser routes. Review `.rr.prod.yaml`, environment variables, session storage,
and logging before deploying.

The current starter project is the canonical reference for a complete 1.0
application structure.
