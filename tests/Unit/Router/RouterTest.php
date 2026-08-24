<?php

use GustavPHP\Gustav\Http\Exception\HttpException;
use GustavPHP\Gustav\Router\{Method, RouteCompiler, Router};
use GustavPHP\Tests\RouterFixtures\{AmbiguousController, DuplicateCsrfController, DuplicateNameController, ValidController};

function compiledRouter(): Router
{
    return new Router(RouteCompiler::compile(ValidController::class));
}

it('matches static routes before parameter routes', function () {
    $router = compiledRouter();

    expect($router->match(Method::GET, '/blog')->route->handler)->toBe('index')
        ->and($router->match(Method::GET, '/blog/authors')->route->handler)->toBe('authors')
        ->and($router->match(Method::GET, '/blog/42')->route->handler)->toBe('show');
});

it('returns decoded parameters with a route match', function () {
    $match = compiledRouter()->match(Method::GET, '/blog/hello%20world/comments/first');

    expect($match->route->handler)->toBe('comment')
        ->and($match->parameters)->toBe([
            'post' => 'hello world',
            'comment' => 'first',
        ]);
});

it('matches the declared HTTP method and lets HEAD fall back to GET', function () {
    $router = compiledRouter();

    expect($router->match(Method::POST, '/blog')->route->handler)->toBe('create')
        ->and($router->match(Method::HEAD, '/blog')->route->handler)->toBe('index')
        ->and(array_map(
            fn (Method $method): string => $method->value,
            $router->allowedMethods('/blog/authors'),
        ))->toBe(['GET', 'HEAD', 'OPTIONS']);
});

it('compiles CSRF protection only for unsafe controller routes', function () {
    $router = compiledRouter();

    expect($router->match(Method::POST, '/blog')->route->csrfProtected)->toBeTrue()
        ->and($router->match(Method::GET, '/blog')->route->csrfProtected)->toBeFalse()
        ->and(Method::OPTIONS->isSafe())->toBeTrue()
        ->and(Method::DELETE->isSafe())->toBeFalse();
});

it('distinguishes missing paths from unsupported methods', function () {
    $router = compiledRouter();

    try {
        $router->match(Method::GET, '/missing');
        throw new LogicException('Missing path should throw');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(404);
    }

    try {
        $router->match(Method::DELETE, '/blog/authors');
        throw new LogicException('Unsupported method should throw');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(405)
            ->and($exception->getHeaders())->toBe(['Allow' => 'GET, HEAD, OPTIONS']);
    }
});

it('generates encoded paths and query strings for named routes', function () {
    $url = compiledRouter()->generate(
        'blog.show',
        ['post' => 'hello world'],
        ['page' => 2, 'filter' => 'recent'],
    );

    expect($url)->toBe('/blog/hello%20world?page=2&filter=recent');
});

it('rejects invalid named-route parameters', function (array $parameters, string $message) {
    expect(fn () => compiledRouter()->generate('blog.show', $parameters))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing' => [[], 'Missing route parameters: post'],
    'unknown' => [['post' => 1, 'extra' => 2], 'Unknown route parameters: extra'],
]);

it('rejects ambiguous route patterns during compilation', function () {
    new Router(RouteCompiler::compile(AmbiguousController::class));
})->throws(InvalidArgumentException::class, 'conflicts with');

it('rejects duplicate route names during compilation', function () {
    new Router(RouteCompiler::compile(DuplicateNameController::class));
})->throws(InvalidArgumentException::class, "Route name 'duplicate' is declared by both");

it('rejects repeated CSRF metadata during compilation', function () {
    RouteCompiler::compile(DuplicateCsrfController::class);
})->throws(LogicException::class, 'cannot repeat the #[Csrf] attribute');
