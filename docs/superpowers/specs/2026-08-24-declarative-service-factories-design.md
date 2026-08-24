# Declarative service factories

Date: 2026-08-24
Status: Approved for implementation

## Goal

Let an application construct third-party or scalar-configured objects through
ordinary, discovered PHP classes. A project should be able to inject a PDO
connection, HTTP client, SDK, mailer, or similar object without calling
`Application::services()`, implementing an imperative registry, or making the
framework own that library's behavior.

The feature extends Gustav's existing application-service discovery and keeps
the current scoped, singleton, and transient lifetime semantics.

## Non-goals

- Do not add database, cache, mail, queue, or HTTP-client integrations.
- Do not add method-level producer attributes or factories that produce more
  than one service.
- Do not support scalar, array, nullable, union, or intersection products.
- Do not eagerly construct factory products during application startup.
- Do not remove `Service\Provider`, `Application::services()`, or the existing
  programmatic container API in this milestone.
- Do not add a new dependency.

## Public API

Applications declare one invokable factory class for one service:

```php
namespace App\Services;

use App\Config\DatabaseConfig;
use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\Lifetime;
use PDO;

#[Factory(lifetime: Lifetime::Singleton)]
final readonly class DatabaseFactory
{
    public function __construct(private DatabaseConfig $config)
    {
    }

    public function __invoke(): PDO
    {
        return new PDO($this->config->dsn);
    }
}
```

`#[Factory]` targets classes and accepts one optional `Lifetime` argument. Its
default is `Lifetime::Scoped`, matching `#[Service]`. The non-nullable named
return type of `__invoke()` is the service identifier; no string identifier or
`as` argument is required.

The factory constructor uses normal service autowiring. Typed configuration,
other application services, and framework services can therefore be injected
without a container argument. `__invoke()` accepts no arguments so dependency
declarations remain in one conventional place.

## Discovery and compilation

Factories are discovered recursively under the same `Services` namespace and
additional `serviceNamespaces` used by services and providers. Discovery
compiles immutable factory metadata once during application startup.

A valid factory:

- is an instantiable class with exactly one `#[Factory]` attribute;
- has a public, non-static `__invoke()` method;
- declares no `__invoke()` parameters;
- declares a non-nullable `ReflectionNamedType` return type;
- returns an existing class or interface type; and
- does not also declare `#[Service]` or implement `Service\Provider`.

Missing return types, built-in types, `object`, `mixed`, `void`, `never`,
nullable types, unions, and intersections fail startup compilation. Error
messages identify the factory class and invalid signature.

Discovery produces a registration containing the product service ID, factory
class, and requested lifetime. The factory class itself is not explicitly
registered as an application service by the attribute.

## Resolution and lifetimes

Factory products remain lazy. When a product is requested, the active
container:

1. autowires a new factory instance through its constructor;
2. invokes `__invoke()`; and
3. verifies the result satisfies the declared product type.

The product, not the factory object, owns the configured lifetime:

- a singleton product invokes its factory once in the root container;
- a scoped product invokes its factory once per HTTP request or console
  command; and
- a transient product creates and invokes a new factory for every resolution.

Existing root-container lifetime enforcement remains authoritative. A
singleton product whose factory constructor depends on a scoped service fails
instead of retaining request or command state. Circular dependencies continue
to report the existing resolution chain.

Factory invocation failures are not translated into configuration errors.
They occur when the lazy product is first requested and follow Gustav's normal
production-safe request or command exception handling.

## Registration precedence and conflicts

Framework defaults are registered first. Application registrations may
replace those defaults, preserving the current customization behavior for
loggers, view renderers, session stores, and other framework interfaces.

Attributed application registrations are compiled as one deterministic set.
Two `#[Factory]` declarations for the same product, two `#[Service]`
declarations for the same identifier, or a `#[Factory]` product that collides
with a discovered `#[Service]` identifier fail application startup. The error
identifies both declaring classes. Registration order must not decide the
winner.

Discovered legacy providers run after attributed services and factories.
Their imperative registrations remain the explicit low-level override escape
hatch and retain existing behavior.

## Compatibility

Existing `#[Service]` classes, concrete-class autowiring, closure factories,
object definitions, providers, and direct container usage continue to work.
No existing public API is removed. The documentation will make declarative
factory classes the preferred integration mechanism and describe providers as
an advanced compatibility escape hatch.

## Testing

Implementation follows test-driven development. Coverage must include:

- attribute lifetime metadata and the scoped default;
- discovery in the conventional and additional service namespaces;
- constructor injection of typed configuration and another service;
- products declared as concrete classes and interfaces;
- singleton, scoped, and transient product identity;
- request and command scope cleanup;
- singleton-to-scoped dependency rejection;
- lazy invocation and safe propagation of factory failures;
- runtime product type enforcement;
- duplicate factory and factory/service collision errors;
- coexistence with framework-default overrides and legacy providers; and
- every invalid factory signature described above.

No RoadRunner transport behavior changes, so this milestone does not add or
run a transport smoke test.

## Documentation and delivery

Framework work is delivered from `feat/declarative-service-factories`. The
documentation repository receives a separate linked branch and draft PR. The
application-services guide will replace its imperative PDO provider example
with an invokable factory, explain lifetime behavior and startup validation,
and retain a concise provider escape-hatch section. The starter project does
not need a contrived third-party object solely to demonstrate this feature.

Framework verification:

```text
composer format
composer dump-autoload --optimize --strict-psr
composer test
composer check
composer lint
composer audit --format=plain
composer validate --strict --no-check-publish
git diff --check
```

Documentation verification:

```text
bun run fmt:check
bun run check
bun run build
git diff --check
```
