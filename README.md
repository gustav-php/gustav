# Gustav - PHP Framework

Gustav is a PHP framework for building web applications. It is designed to be simple, object-oriented and using the latest features of PHP.

## Installation

Before creating your first GustavPHP project, you should ensure that your local machine has [PHP](https://www.php.net/) and [Composer](https://getcomposer.org/) installed.

After you have installed PHP and Composer, you may create a new GustavPHP project via the `create-project` command:

```bash
composer create-project gustav-php/starter --ask
```

## Usage

After the project has been created, start GustavPHP's local development server using the `dev` command:

```bash
php gustav dev
```

Controllers are plain constructor-injected classes. `#[Controller]` supplies a
shared path prefix and concise HTTP method attributes declare handlers:

```php
use GustavPHP\Gustav\Attribute\{Controller, Get, Param};

#[Controller('/dogs')]
final readonly class DogsController
{
    #[Get('/{dog}', name: 'dogs.show')]
    public function show(#[Param('dog')] int $id): DogOutput
    {
        // ...
    }
}
```

Routes, request binders, response handlers, and middleware metadata are
compiled once during startup. Inject `Router\UrlGeneratorInterface` to generate
paths for named routes.

Application commands are discovered from `src/Commands` and use typed
arguments, options, validation, and constructor injection. List every built-in
and project command with:

```bash
php gustav list
```

Typed event objects are dispatched through PSR-14. Invokable `#[Listener]`
classes under `src/Events` are discovered automatically, receive constructor
dependencies, and live only for the current request or command scope.

## Development

Run the fast in-process test suite with:

```bash
composer test
```

Run the focused RoadRunner boundary contract locally with:

```bash
composer test:transport
```

The transport command downloads the ignored local RoadRunner binary when it is
missing, selects free ports, starts and stops the worker automatically, and
prints its logs if the contract fails. It covers JSON binding, malformed JSON,
request-scope isolation, structured `5xx` reporting, request IDs, and worker
recovery after a failed request.

## Documentation

- https://gustav-php.github.io
