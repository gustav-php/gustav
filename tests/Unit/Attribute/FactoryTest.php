<?php

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\{FactoryRegistration, Lifetime};
use GustavPHP\Tests\FactoryFixtures\Signatures\{AbstractFactory, ArrayReturnFactory, BuiltinReturnFactory, DuplicateAttributeFactory, IntersectionReturnFactory, InvokeParameterFactory, MissingInvokeFactory, MissingReturnFactory, MixedReturnFactory, NeverReturnFactory, NonPublicInvokeFactory, NullableReturnFactory, ObjectReturnFactory, Product, ProviderFactory, ServiceFactory, UnattributedFactory, UnionReturnFactory, UnknownReturnFactory, ValidFactory, VoidReturnFactory};

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
    'missing invoke method' => [MissingInvokeFactory::class, '__invoke'],
    'invoke parameter' => [InvokeParameterFactory::class, 'must accept no parameters'],
    'missing return type' => [MissingReturnFactory::class, 'must declare a return type'],
    'string return type' => [BuiltinReturnFactory::class, 'class or interface return type'],
    'array return type' => [ArrayReturnFactory::class, 'class or interface return type'],
    'object return type' => [ObjectReturnFactory::class, 'class or interface return type'],
    'mixed return type' => [MixedReturnFactory::class, 'class or interface return type'],
    'void return type' => [VoidReturnFactory::class, 'class or interface return type'],
    'never return type' => [NeverReturnFactory::class, 'class or interface return type'],
    'unknown return type' => [UnknownReturnFactory::class, 'does not exist'],
    'nullable return type' => [NullableReturnFactory::class, 'cannot be nullable'],
    'union return type' => [UnionReturnFactory::class, 'class or interface return type'],
    'intersection return type' => [IntersectionReturnFactory::class, 'class or interface return type'],
    'abstract factory' => [AbstractFactory::class, 'must be instantiable'],
    'missing factory attribute' => [UnattributedFactory::class, 'exactly one #[Factory]'],
    'duplicate factory attribute' => [DuplicateAttributeFactory::class, 'exactly one #[Factory]'],
    'service factory' => [ServiceFactory::class, 'cannot also declare #[Service]'],
    'provider factory' => [ProviderFactory::class, 'cannot also implement'],
]);

it('rejects a non-public invoke method', function () {
    set_error_handler(
        static fn (int $severity, string $message): bool => $severity === E_WARNING
            && str_contains($message, 'must have public visibility'),
    );

    try {
        expect(fn () => FactoryRegistration::compile(NonPublicInvokeFactory::class))
            ->toThrow(LogicException::class, 'public non-static __invoke');
    } finally {
        restore_error_handler();
    }
});
