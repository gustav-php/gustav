<?php

use GustavPHP\Gustav\Attribute\Factory;
use GustavPHP\Gustav\Service\{FactoryRegistration, Lifetime};
use GustavPHP\Tests\FactoryFixtures\Signatures\{AbstractFactory, BuiltinReturnFactory, IntersectionReturnFactory, InvokeParameterFactory, MissingInvokeFactory, MissingReturnFactory, NullableReturnFactory, Product, ProviderFactory, ServiceFactory, UnionReturnFactory, ValidFactory};

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
    'built-in return type' => [BuiltinReturnFactory::class, 'class or interface return type'],
    'nullable return type' => [NullableReturnFactory::class, 'cannot be nullable'],
    'union return type' => [UnionReturnFactory::class, 'class or interface return type'],
    'intersection return type' => [IntersectionReturnFactory::class, 'class or interface return type'],
    'abstract factory' => [AbstractFactory::class, 'must be instantiable'],
    'service factory' => [ServiceFactory::class, 'cannot also declare #[Service]'],
    'provider factory' => [ProviderFactory::class, 'cannot also implement'],
]);
