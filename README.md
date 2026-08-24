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

Inject `Router\UrlGeneratorInterface` when you need to generate paths for named
routes.

Application commands are discovered from `src/Commands` and use typed
arguments, options, validation, and constructor injection. List every built-in
and project command with:

```bash
php gustav list
```

Typed event objects are dispatched through PSR-14. Invokable `#[Listener]`
classes under `src/Events` are discovered automatically and may receive
constructor dependencies.

## Documentation

- https://gustav-php.github.io

See [CONTRIBUTING.md](CONTRIBUTING.md) to work on the framework itself.

## Versioning

Gustav follows [Semantic Versioning](https://semver.org/) from version 1.0.
Public and protected APIs that are not marked `@internal`, together with
documented configuration and command behavior, are covered by that promise.
Breaking changes are reserved for major releases.
