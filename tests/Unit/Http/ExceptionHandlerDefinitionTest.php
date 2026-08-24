<?php

use GustavPHP\Gustav\Http\{ExceptionHandlerDefinition, ResponseHandler};
use GustavPHP\Tests\ExceptionHandlerFixtures\Signatures\{
    AbstractHandler,
    BuiltinParameterHandler,
    BuiltinReturnHandler,
    DomainFailure,
    DtoReturnHandler,
    DuplicateAttributeHandler,
    HttpExceptionTargetHandler,
    InterfaceParameterHandler,
    MissingAttributeHandler,
    MissingInvokeHandler,
    MissingParameterTypeHandler,
    MissingReturnHandler,
    MixedParameterHandler,
    MultipleParametersHandler,
    NonPublicInvokeHandler,
    NullableParameterHandler,
    NullableReturnHandler,
    ObjectParameterHandler,
    ReferenceParameterHandler,
    UnionParameterHandler,
    UnionReturnHandler,
    UnknownParameterHandler,
    UnknownReturnHandler,
    UnrelatedParameterHandler,
    ValidHandler,
    VariadicParameterHandler,
    ZeroParametersHandler
};

it('compiles one typed exception into explicit response metadata', function () {
    $definition = ExceptionHandlerDefinition::compile(ValidHandler::class);

    expect($definition->handler)->toBe(ValidHandler::class)
        ->and($definition->exception)->toBe(DomainFailure::class)
        ->and($definition->responseHandler)->toBeInstanceOf(ResponseHandler::class);
});

it('rejects invalid exception handler signatures during compilation', function (string $handler, string $message) {
    set_error_handler(
        static fn (int $severity, string $warning): bool => $severity === E_WARNING
            && str_contains($warning, 'must have public visibility'),
    );

    try {
        expect(fn () => ExceptionHandlerDefinition::compile($handler))
            ->toThrow(LogicException::class, $message);
    } finally {
        restore_error_handler();
    }
})->with([
    'abstract handler' => [AbstractHandler::class, 'must be instantiable'],
    'missing attribute' => [MissingAttributeHandler::class, 'exactly one #[ExceptionHandler]'],
    'duplicate attribute' => [DuplicateAttributeHandler::class, 'exactly one #[ExceptionHandler]'],
    'missing invoke' => [MissingInvokeHandler::class, 'public __invoke() method'],
    'non-public invoke' => [NonPublicInvokeHandler::class, 'public non-static __invoke() method'],
    'zero parameters' => [ZeroParametersHandler::class, 'exactly one exception parameter'],
    'multiple parameters' => [MultipleParametersHandler::class, 'exactly one exception parameter'],
    'reference parameter' => [ReferenceParameterHandler::class, 'regular value parameter'],
    'variadic parameter' => [VariadicParameterHandler::class, 'regular value parameter'],
    'missing parameter type' => [MissingParameterTypeHandler::class, 'one exception class'],
    'string parameter' => [BuiltinParameterHandler::class, 'one exception class'],
    'object parameter' => [ObjectParameterHandler::class, 'one exception class'],
    'mixed parameter' => [MixedParameterHandler::class, 'one exception class'],
    'union parameter' => [UnionParameterHandler::class, 'one exception class'],
    'nullable parameter' => [NullableParameterHandler::class, 'cannot be nullable'],
    'unknown parameter' => [UnknownParameterHandler::class, 'does not exist'],
    'unrelated parameter' => [UnrelatedParameterHandler::class, 'must implement Throwable'],
    'HTTP exception parameter' => [HttpExceptionTargetHandler::class, 'cannot target framework HTTP exceptions'],
    'Throwable interface parameter' => [InterfaceParameterHandler::class, 'must declare an exception class or Throwable'],
    'missing return type' => [MissingReturnHandler::class, 'one explicit response type'],
    'array return type' => [BuiltinReturnHandler::class, 'Response, ResponseInterface, or View'],
    'union return type' => [UnionReturnHandler::class, 'one explicit response type'],
    'nullable return type' => [NullableReturnHandler::class, 'non-null response object'],
    'DTO return type' => [DtoReturnHandler::class, 'Response, ResponseInterface, or View'],
    'unknown return type' => [UnknownReturnHandler::class, 'Response, ResponseInterface, or View'],
]);
