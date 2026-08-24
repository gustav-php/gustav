# Declarative Service Factories Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Discover one invokable factory class per externally constructed service and resolve its product lazily with Gustav's existing service lifetimes.

**Architecture:** Add a class-level `#[Factory]` attribute and compile each attributed class into immutable `FactoryRegistration` metadata. Discovery uses the existing service namespaces; `Application` validates all attributed service IDs before registering factory closures that autowire and invoke the factory through the active container. Existing providers remain the final imperative override layer.

**Tech Stack:** PHP 8.2+, reflection, Gustav's service container and class discovery, Pest 3, PHPStan 2, Composer, Bun/Docia documentation tooling.

**Spec:** `docs/superpowers/specs/2026-08-24-declarative-service-factories-design.md`

## Global Constraints

- Do not add database, cache, mail, queue, or HTTP-client integrations.
- Do not add method-level producers or multi-product factories.
- Factory products must be non-nullable named class or interface types.
- Products stay lazy and use only `Lifetime::Singleton`, `Lifetime::Scoped`, or `Lifetime::Transient`.
- Keep `Service\Provider`, `Application::services()`, closure factories, and direct container registration compatible.
- Add no dependency, static-analysis ignore, baseline, Composer classmap exclusion, or test-only production branch.
- Do not add or run a RoadRunner transport smoke test for this feature.
- Work only in `/Users/torstendittmann/Documents/GitHub/gustav-php/.worktrees/gustav-declarative-service-factories` for framework changes.

---

## File map

Framework production files:

- Create `src/Attribute/Factory.php`: public immutable factory metadata.
- Create `src/Service/FactoryRegistration.php`: startup signature compiler and lazy resolver closure.
- Modify `src/Discovery.php`: discover factory classes from service namespaces.
- Modify `src/Application.php`: validate attributed registration conflicts and register factory products by lifetime.

Framework tests and fixtures:

- Create `tests/Unit/Attribute/FactoryTest.php`: attribute defaults and compilation contract.
- Create `tests/Unit/Service/ApplicationFactoryTest.php`: application discovery, products, lifetimes, overrides, and failures.
- Create `tests/FactoryFixtures/Signatures/*`: one PSR-4 fixture per valid or invalid signature.
- Create `tests/FactoryFixtures/ValidApplication/*`: typed configuration, products, dependency, and factories.
- Create `tests/FactoryFixtures/AdditionalServices/*`: configured namespace discovery.
- Create `tests/FactoryFixtures/DuplicateApplication/*`: duplicate factories.
- Create `tests/FactoryFixtures/CollisionApplication/*`: service/factory collision.
- Create `tests/FactoryFixtures/InvalidLifetimeApplication/*`: singleton/scoped lifetime rejection.
- Create `tests/FactoryFixtures/ProviderApplication/*`: provider-last compatibility.

Documentation repository:

- Modify `src/services.md`: prefer an invokable `#[Factory]` for third-party objects and retain providers as the advanced escape hatch.

---

### Task 1: Compile declarative factory metadata

**Files:**

- Create: `src/Attribute/Factory.php`
- Create: `src/Service/FactoryRegistration.php`
- Create: `tests/Unit/Attribute/FactoryTest.php`
- Create: `tests/FactoryFixtures/Signatures/ValidFactory.php`
- Create: `tests/FactoryFixtures/Signatures/MissingInvokeFactory.php`
- Create: `tests/FactoryFixtures/Signatures/InvokeParameterFactory.php`
- Create: `tests/FactoryFixtures/Signatures/MissingReturnFactory.php`
- Create: `tests/FactoryFixtures/Signatures/BuiltinReturnFactory.php`
- Create: `tests/FactoryFixtures/Signatures/NullableReturnFactory.php`
- Create: `tests/FactoryFixtures/Signatures/UnionReturnFactory.php`
- Create: `tests/FactoryFixtures/Signatures/IntersectionReturnFactory.php`
- Create: `tests/FactoryFixtures/Signatures/AbstractFactory.php`
- Create: `tests/FactoryFixtures/Signatures/ServiceFactory.php`
- Create: `tests/FactoryFixtures/Signatures/ProviderFactory.php`
- Create: `tests/FactoryFixtures/Signatures/Product.php`
- Create: `tests/FactoryFixtures/Signatures/OtherProduct.php`
- Create: `tests/FactoryFixtures/Signatures/ProductImplementation.php`

**Interfaces:**

- Produces: `new Attribute\Factory(Lifetime $lifetime = Lifetime::Scoped)`.
- Produces: `Factory::getLifetime(): Lifetime`.
- Produces: `FactoryRegistration::compile(class-string $factory): FactoryRegistration`.
- Produces readonly metadata: `service`, `factory`, and `lifetime`.
- Produces: `FactoryRegistration::resolver(): Closure(Container): object`.

- [ ] **Step 1: Add failing attribute and compiler tests**

Create `tests/Unit/Attribute/FactoryTest.php` with this contract:

~~~php
use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\{FactoryRegistration, Lifetime};
use GustavPHP\Tests\FactoryFixtures\Signatures\{
    AbstractFactory,
    BuiltinReturnFactory,
    IntersectionReturnFactory,
    InvokeParameterFactory,
    MissingInvokeFactory,
    MissingReturnFactory,
    NullableReturnFactory,
    Product,
    ProviderFactory,
    ServiceFactory,
    UnionReturnFactory,
    ValidFactory,
};

it('uses execution scope as the default factory product lifetime', function () {
    expect((new Factory())->getLifetime())->toBe(Lifetime::Scoped)
        ->and((new Factory(Lifetime::Singleton))->getLifetime())->toBe(Lifetime::Singleton);
});

it('compiles an invokable class into one product registration', function () {
    $registration = FactoryRegistration::compile(ValidFactory::class);

    expect($registration->service)->toBe(Product::class)
        ->and($registration->factory)->toBe(ValidFactory::class)
        ->and($registration->lifetime)->toBe(Lifetime::Transient);
});

it('rejects invalid declarative factory signatures', function (string $class, string $message) {
    expect(fn () => FactoryRegistration::compile($class))
        ->toThrow(LogicException::class, $message);
})->with([
    [MissingInvokeFactory::class, '__invoke'],
    [InvokeParameterFactory::class, 'must accept no parameters'],
    [MissingReturnFactory::class, 'must declare a return type'],
    [BuiltinReturnFactory::class, 'class or interface return type'],
    [NullableReturnFactory::class, 'cannot be nullable'],
    [UnionReturnFactory::class, 'class or interface return type'],
    [IntersectionReturnFactory::class, 'class or interface return type'],
    [AbstractFactory::class, 'must be instantiable'],
    [ServiceFactory::class, 'cannot also declare #[Service]'],
    [ProviderFactory::class, 'cannot also implement'],
]);
~~~

`Product` and `OtherProduct` are interfaces, while `ProductImplementation`
implements both so the union and intersection signatures are valid PHP. Each
factory fixture is one focused class in its matching PSR-4 file.
`ValidFactory` uses `#[Factory(Lifetime::Transient)]`, declares `Product`, and
returns `ProductImplementation`; each invalid fixture expresses exactly the
named signature without runtime conditionals.

- [ ] **Step 2: Run the focused tests and confirm RED**

Run:

~~~bash
composer test -- tests/Unit/Attribute/FactoryTest.php
~~~

Expected: FAIL because `Attribute\Factory` and `Service\FactoryRegistration` do not exist.

- [ ] **Step 3: Implement the attribute**

Create `src/Attribute/Factory.php`:

~~~php
<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use GustavPHP\Gustav\Service\Lifetime;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Factory
{
    public function __construct(private Lifetime $lifetime = Lifetime::Scoped)
    {
    }

    public function getLifetime(): Lifetime
    {
        return $this->lifetime;
    }
}
~~~

- [ ] **Step 4: Implement signature compilation and lazy resolution**

Create `src/Service/FactoryRegistration.php`. `compile()` must:

1. Reflect the supplied class and require it to be instantiable.
2. Require exactly one `Attribute\Factory`.
3. Reject `Attribute\Service` and `Provider` overlap.
4. Require public, non-static, zero-parameter `__invoke()`.
5. Require a non-built-in `ReflectionNamedType` that does not allow null.
6. Require `class_exists($service) || interface_exists($service)`.
7. Return immutable product metadata.

The resolver reuses constructor autowiring:

~~~php
public function resolver(): Closure
{
    $factory = $this->factory;

    return static function (Container $services) use ($factory): object {
        $instance = $services->make($factory);

        return $instance();
    };
}
~~~

Use `LogicException` messages beginning with `Service factory '{$factory}'`
so startup failures identify their declaration.

- [ ] **Step 5: Run focused tests and static analysis**

Run:

~~~bash
composer test -- tests/Unit/Attribute/FactoryTest.php
composer check
~~~

Expected: the factory tests pass and PHPStan reports no errors.

- [ ] **Step 6: Format and commit**

Run:

~~~bash
composer format
git add src/Attribute/Factory.php src/Service/FactoryRegistration.php tests/Unit/Attribute/FactoryTest.php tests/FactoryFixtures/Signatures
git commit -m "feat: compile declarative service factories"
~~~

### Task 2: Discover and resolve factory products

**Files:**

- Modify: `src/Discovery.php`
- Modify: `src/Application.php`
- Create: `tests/Unit/Service/ApplicationFactoryTest.php`
- Create: `tests/FactoryFixtures/ValidApplication/Config/FactorySettings.php`
- Create: `tests/FactoryFixtures/ValidApplication/Products/FactoryContract.php`
- Create: `tests/FactoryFixtures/ValidApplication/Products/ConfiguredProduct.php`
- Create: `tests/FactoryFixtures/ValidApplication/Products/SingletonProduct.php`
- Create: `tests/FactoryFixtures/ValidApplication/Products/ScopedProduct.php`
- Create: `tests/FactoryFixtures/ValidApplication/Products/TransientProduct.php`
- Create: `tests/FactoryFixtures/ValidApplication/Products/FailingProduct.php`
- Create: `tests/FactoryFixtures/ValidApplication/Services/FactoryDependency.php`
- Create: `tests/FactoryFixtures/ValidApplication/Services/ConfiguredProductFactory.php`
- Create: `tests/FactoryFixtures/ValidApplication/Services/SingletonProductFactory.php`
- Create: `tests/FactoryFixtures/ValidApplication/Services/ScopedProductFactory.php`
- Create: `tests/FactoryFixtures/ValidApplication/Services/TransientProductFactory.php`
- Create: `tests/FactoryFixtures/ValidApplication/Services/FailingProductFactory.php`
- Create: `tests/FactoryFixtures/ValidApplication/Services/LoggerFactory.php`
- Create: `tests/FactoryFixtures/ValidApplication/Routes/FactoryScopeRoute.php`
- Create: `tests/FactoryFixtures/ValidApplication/Routes/FailingFactoryRoute.php`
- Create: `tests/FactoryFixtures/ValidApplication/Commands/FactoryScopeCommand.php`
- Create: `tests/FactoryFixtures/AdditionalServices/Products/AdditionalProduct.php`
- Create: `tests/FactoryFixtures/AdditionalServices/AdditionalProductFactory.php`

**Interfaces:**

- Consumes `FactoryRegistration::compile()` and `FactoryRegistration::resolver()`.
- Produces `Discovery::discoverFactories(): iterable<FactoryRegistration>`.
- Produces lazy container definitions keyed by factory return type.

- [ ] **Step 1: Write failing application tests**

Create this helper in `ApplicationFactoryTest.php`:

~~~php
function createFactoryApplication(array $serviceNamespaces = []): Application
{
    return new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\FactoryFixtures\\ValidApplication',
        serviceNamespaces: $serviceNamespaces,
        environment: Environment::fromArray(['FACTORY_PREFIX' => 'configured']),
    ));
}
~~~

Add these behaviors:

~~~php
it('discovers a factory and autowires typed configuration and services', function () {
    $services = createFactoryApplication()->services();
    $services->build();
    $scope = $services->createScope();

    $product = $scope->get(FactoryContract::class);

    expect($product)->toBeInstanceOf(ConfiguredProduct::class)
        ->and($product->value)->toBe('configured:dependency');
});

it('honors every factory product lifetime lazily', function () {
    SingletonProductFactory::$calls = 0;
    ScopedProductFactory::$calls = 0;
    TransientProductFactory::$calls = 0;

    $services = createFactoryApplication()->services();
    $services->build();
    expect(SingletonProductFactory::$calls)->toBe(0);

    $first = $services->createScope();
    $second = $services->createScope();

    expect($first->get(SingletonProduct::class))->toBe($second->get(SingletonProduct::class))
        ->and($first->get(ScopedProduct::class))->toBe($first->get(ScopedProduct::class))
        ->and($first->get(ScopedProduct::class))->not->toBe($second->get(ScopedProduct::class))
        ->and($first->get(TransientProduct::class))->not->toBe($first->get(TransientProduct::class))
        ->and(SingletonProductFactory::$calls)->toBe(1)
        ->and(ScopedProductFactory::$calls)->toBe(2)
        ->and(TransientProductFactory::$calls)->toBe(2);
});

it('discovers factories from configured service namespaces', function () {
    $services = createFactoryApplication([
        'GustavPHP\\Tests\\FactoryFixtures\\AdditionalServices',
    ])->services();
    $services->build();

    expect($services->createScope()->get(AdditionalProduct::class))
        ->toBeInstanceOf(AdditionalProduct::class);
});

it('lets an application factory replace a framework default', function () {
    $services = createFactoryApplication()->services();
    $services->build();

    expect($services->createScope()->get(LoggerInterface::class))
        ->toBeInstanceOf(NullLogger::class);
});

it('releases scoped factory products after requests and commands', function () {
    $application = createFactoryApplication();

    $first = $application->handle(new ServerRequest('GET', '/factory-scope'));
    $second = $application->handle(new ServerRequest('GET', '/factory-scope'));

    /** @var array{id:int} $firstPayload */
    $firstPayload = json_decode((string) $first->getBody(), true, 512, JSON_THROW_ON_ERROR);
    /** @var array{id:int} $secondPayload */
    $secondPayload = json_decode((string) $second->getBody(), true, 512, JSON_THROW_ON_ERROR);

    expect($firstPayload['id'])->not->toBe($secondPayload['id']);

    FactoryScopeCommand::$productIds = [];
    $tester = new ApplicationTester(createFactoryApplication()->console());
    $tester->run(['command' => 'factory:scope']);
    $tester->run(['command' => 'factory:scope']);

    expect(FactoryScopeCommand::$productIds[0])
        ->not->toBe(FactoryScopeCommand::$productIds[1]);
});

it('keeps lazy factory failures production safe and releases the scope', function () {
    $application = createFactoryApplication();

    $failed = $application->handle(new ServerRequest('GET', '/factory-failure'));
    $next = $application->handle(new ServerRequest('GET', '/factory-scope'));

    expect($failed->getStatusCode())->toBe(500)
        ->and((string) $failed->getBody())->not->toContain('private factory failure')
        ->and($next->getStatusCode())->toBe(200);
});
~~~

`FactorySettings` is readonly `#[Config]` metadata mapping `$prefix` with `#[Env('FACTORY_PREFIX')]`. `ConfiguredProductFactory` returns `FactoryContract` and combines the setting with constructor-injected `FactoryDependency`. Lifetime factories increment public static `$calls` only from `__invoke()`.
`LoggerFactory` returns `LoggerInterface` with a `NullLogger`, proving attributed
factories can replace framework defaults. The route and command record the
monotonic ID assigned by `ScopedProductFactory`; the failing route is the only
consumer of `FailingProduct`, whose factory throws `private factory failure`.

- [ ] **Step 2: Run the application test and confirm RED**

Run:

~~~bash
composer test -- tests/Unit/Service/ApplicationFactoryTest.php
~~~

Expected: FAIL because factory products are not discovered.

- [ ] **Step 3: Add factory discovery**

Add to `Discovery`:

~~~php
/** @return iterable<Service\FactoryRegistration> */
public static function discoverFactories(): iterable
{
    foreach (self::discoverClasses('Services', 'serviceNamespaces') as $class) {
        $reflection = new ReflectionClass($class);
        if ($reflection->getAttributes(Attribute\Factory::class) === []) {
            continue;
        }

        yield Service\FactoryRegistration::compile($class);
    }
}
~~~

- [ ] **Step 4: Register lazy products by lifetime**

In `Application::__construct()`, collect factories after typed configuration has loaded. Register each resolver before providers:

~~~php
$resolver = $factory->resolver();

match ($factory->lifetime) {
    Lifetime::Singleton => $this->services->singleton($factory->service, $resolver),
    Lifetime::Scoped => $this->services->scoped($factory->service, $resolver),
    Lifetime::Transient => $this->services->transient($factory->service, $resolver),
};
~~~

- [ ] **Step 5: Run focused regression tests**

Run:

~~~bash
composer test -- tests/Unit/Service/ApplicationFactoryTest.php tests/Unit/Service/ContainerTest.php
composer check
~~~

Expected: both suites pass and PHPStan reports no errors.

- [ ] **Step 6: Format and commit**

Run:

~~~bash
composer format
git add src/Discovery.php src/Application.php tests/Unit/Service/ApplicationFactoryTest.php tests/FactoryFixtures/ValidApplication tests/FactoryFixtures/AdditionalServices
git commit -m "feat: discover factory products"
~~~

### Task 3: Enforce registration and lifetime safety

**Files:**

- Modify: `src/Application.php`
- Modify: `tests/Unit/Service/ApplicationFactoryTest.php`
- Modify: `tests/Unit/Service/ContainerTest.php`
- Create: `tests/FactoryFixtures/DuplicateApplication/Products/DuplicateProduct.php`
- Create: `tests/FactoryFixtures/DuplicateApplication/Services/FirstFactory.php`
- Create: `tests/FactoryFixtures/DuplicateApplication/Services/SecondFactory.php`
- Create: `tests/FactoryFixtures/DuplicateServiceApplication/Products/DuplicateContract.php`
- Create: `tests/FactoryFixtures/DuplicateServiceApplication/Services/FirstService.php`
- Create: `tests/FactoryFixtures/DuplicateServiceApplication/Services/SecondService.php`
- Create: `tests/FactoryFixtures/CollisionApplication/Products/CollisionContract.php`
- Create: `tests/FactoryFixtures/CollisionApplication/Services/CollisionService.php`
- Create: `tests/FactoryFixtures/CollisionApplication/Services/CollisionFactory.php`
- Create: `tests/FactoryFixtures/InvalidLifetimeApplication/Products/InvalidSingletonProduct.php`
- Create: `tests/FactoryFixtures/InvalidLifetimeApplication/Services/ScopedDependency.php`
- Create: `tests/FactoryFixtures/InvalidLifetimeApplication/Services/InvalidSingletonFactory.php`
- Create: `tests/FactoryFixtures/ProviderApplication/Products/ProviderContract.php`
- Create: `tests/FactoryFixtures/ProviderApplication/Products/FactoryProduct.php`
- Create: `tests/FactoryFixtures/ProviderApplication/Products/ProviderProduct.php`
- Create: `tests/FactoryFixtures/ProviderApplication/Services/ProductFactory.php`
- Create: `tests/FactoryFixtures/ProviderApplication/Services/OverrideProvider.php`

**Interfaces:**

- Consumes discovered `Registration` and `FactoryRegistration` lists.
- Produces deterministic conflict errors before attributed definitions mutate the container.
- Preserves providers as the final programmatic override layer.

- [ ] **Step 1: Write failing safety tests**

Append:

~~~php
it('rejects duplicate attributed products deterministically', function () {
    new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\FactoryFixtures\\DuplicateApplication',
    ));
})->throws(LogicException::class, 'FirstFactory');

it('rejects service and factory declarations for one identifier', function () {
    new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\FactoryFixtures\\CollisionApplication',
    ));
})->throws(LogicException::class, 'CollisionService');

it('rejects duplicate attributed service identifiers deterministically', function () {
    new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\FactoryFixtures\\DuplicateServiceApplication',
    ));
})->throws(LogicException::class, 'FirstService');

it('prevents singleton factory products from capturing scoped dependencies', function () {
    $application = new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\FactoryFixtures\\InvalidLifetimeApplication',
    ));
    $services = $application->services();
    $services->build();

    $services->createScope()->get(InvalidSingletonProduct::class);
})->throws(LogicException::class, 'requires an active application scope');

it('keeps providers as the final explicit override', function () {
    $application = new Application(new Configuration(
        mode: Mode::Production,
        namespace: 'GustavPHP\\Tests\\FactoryFixtures\\ProviderApplication',
    ));
    $services = $application->services();
    $services->build();

    expect($services->createScope()->get(ProviderContract::class))
        ->toBeInstanceOf(ProviderProduct::class);
});
~~~

Also add a direct container regression where a callable registered for `ContainerTestContract::class` returns `stdClass`; resolving it must throw `InvalidArgumentException`. This covers the runtime product check reused by declarative factories without adding statically invalid fixture code.

- [ ] **Step 2: Run focused tests and confirm RED**

Run:

~~~bash
composer test -- tests/Unit/Service/ApplicationFactoryTest.php tests/Unit/Service/ContainerTest.php
~~~

Expected: conflict tests fail because discovery order currently decides registration.

- [ ] **Step 3: Validate attributed registrations before mutation**

Collect discovered services and factories into lists. Normalize each declaration to `{service, declaration}`, sort by service and then class, and reject a second declaration for one service ID with:

~~~php
sprintf(
    "Service '%s' is declared by both %s and %s",
    $service,
    $firstDeclaration,
    $secondDeclaration,
);
~~~

Validate before binding either list. Include two duplicate `#[Service]` declarations in this rule. Framework defaults are outside the application declaration set, so one attributed definition can still replace a default. Providers still execute after factories.

- [ ] **Step 4: Run focused and full tests**

Run:

~~~bash
composer test -- tests/Unit/Service/ApplicationFactoryTest.php tests/Unit/Service/ContainerTest.php
composer test
composer check
~~~

Expected: all tests pass.

- [ ] **Step 5: Format and commit**

Run:

~~~bash
composer format
git add src/Application.php tests/Unit/Service/ApplicationFactoryTest.php tests/Unit/Service/ContainerTest.php tests/FactoryFixtures/DuplicateApplication tests/FactoryFixtures/DuplicateServiceApplication tests/FactoryFixtures/CollisionApplication tests/FactoryFixtures/InvalidLifetimeApplication tests/FactoryFixtures/ProviderApplication
git commit -m "feat: validate factory registrations"
~~~

### Task 4: Document the preferred integration path

**Files:**

- Create linked worktree/branch: `docs/declarative-service-factories` from current documentation `origin/main`.
- Modify: `src/services.md`.

**Interfaces:**

- Documents the exact `#[Factory]` API from Tasks 1-3.
- Keeps `Provider::register(Container $services): void` as the advanced escape hatch.

- [ ] **Step 1: Create and prepare a clean documentation worktree**

After confirming the target branch/path are absent and fetching `origin`, run:

~~~bash
git worktree add /Users/torstendittmann/Documents/GitHub/gustav-php/.worktrees/docs-declarative-service-factories -b docs/declarative-service-factories origin/main
bun install --frozen-lockfile
~~~

- [ ] **Step 2: Replace the imperative primary example**

Update `src/services.md` so third-party objects use:

~~~php
use App\Config\DatabaseConfig;
use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\Lifetime;

#[Factory(lifetime: Lifetime::Singleton)]
final readonly class DatabaseFactory
{
    public function __construct(private DatabaseConfig $config) {}

    public function __invoke(): PDO
    {
        return new PDO($this->config->url);
    }
}
~~~

Explain return-type inference, constructor injection, lazy construction, all three product lifetimes, startup signature validation, duplicate declarations, and singleton/scoped dependency safety. Retain providers below it for dynamic registrations that cannot be expressed as one typed factory.

- [ ] **Step 3: Format, verify, and commit documentation**

Run:

~~~bash
bun run fmt
bun run fmt:check
bun run check
bun run build
git diff --check
git add src/services.md
git commit -m "docs: document declarative service factories"
~~~

Expected: every command exits zero.

### Task 5: Verify and publish both draft PRs

**Files:**

- Review all framework changes against the spec and plan.
- Review the documentation branch against the implemented API.

**Interfaces:**

- Produces one draft framework PR and one linked draft documentation PR.

- [ ] **Step 1: Run complete framework verification**

From the framework worktree:

~~~bash
composer format
composer dump-autoload --optimize --strict-psr
composer test
composer check
composer lint
composer audit --format=plain
composer validate --strict --no-check-publish
git diff --check
git status --short --branch
~~~

Expected: all commands exit zero and only committed intentional changes exist.

- [ ] **Step 2: Inspect the final framework diff**

Run:

~~~bash
git diff --stat origin/main...HEAD
git diff origin/main...HEAD -- src tests docs/superpowers
~~~

Confirm no database integration, method producer, external dependency, ignored analysis error, or transport test was added.

- [ ] **Step 3: Push and create the draft framework PR**

Create `/tmp/gustav-declarative-service-factories-pr.md` with the observed
verification output and design decisions, then run:

~~~bash
git push -u origin feat/declarative-service-factories
gh pr create --draft --base main --head feat/declarative-service-factories --title "Add declarative service factories" --body-file /tmp/gustav-declarative-service-factories-pr.md
~~~

The body records the invokable-class decision, lazy lifetimes, deterministic collisions, provider compatibility, absence of dependencies/transport changes, and exact verification results.

- [ ] **Step 4: Re-run documentation verification**

From the documentation worktree:

~~~bash
bun run fmt:check
bun run check
bun run build
git diff --check
git diff --stat origin/main...HEAD
git status --short --branch
~~~

- [ ] **Step 5: Push and create the linked draft documentation PR**

Create `/tmp/gustav-declarative-service-factories-docs-pr.md` containing the
framework PR URL and observed documentation checks, then run:

~~~bash
git push -u origin docs/declarative-service-factories
gh pr create --draft --base main --head docs/declarative-service-factories --title "Document declarative service factories" --body-file /tmp/gustav-declarative-service-factories-docs-pr.md
~~~

- [ ] **Step 6: Report delivery**

Report both PR URLs, branch names, `gh pr checks` status, exact framework and documentation validation, and that no starter PR was needed. Keep the larger three-feature objective active: exception handlers and uploads follow after this PR merges.
