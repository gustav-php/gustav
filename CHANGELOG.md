# Changelog

All notable changes to Gustav are documented here. Gustav follows
[Semantic Versioning](https://semver.org/) from version 1.0.

## [Unreleased]

## [1.0.0-RC1] - 2026-08-24

### Added

- A PSR-15 HTTP kernel with declarative controllers, HTTP method attributes,
  middleware, named routes, and URL generation.
- Typed request binding for body, query, path, header, cookie, request, and
  authenticated-user inputs, including immutable DTO hydration and structured
  validation errors.
- Typed response serialization with inferred JSON responses and native
  `.phtml` views.
- Constructor injection, application service lifetimes, declarative services,
  factories, and explicit service providers.
- Typed configuration, application commands, PSR-14 events, and application
  exception handlers.
- Authentication, server-side sessions, CSRF protection, request IDs,
  structured logging, and production-safe error responses.
- Direct in-process request handling and a focused RoadRunner transport
  contract.

### Changed

- Application projects now use `Configuration::forProject()` and a shared
  `app/bootstrap.php` for HTTP and CLI entry points.
- RoadRunner HTTP support now targets version 4.
- CI covers PHP 8.2 through PHP 8.5.

### Removed

- PHP-DI; Gustav now provides its own focused service container.
- Latte; native `.phtml` templates are now the default view implementation.
- The legacy event base classes and static event manager.

See [UPGRADING.md](UPGRADING.md) before moving an existing 0.x application to
1.0.

[Unreleased]: https://github.com/gustav-php/gustav/compare/1.0.0-RC1...HEAD
[1.0.0-RC1]: https://github.com/gustav-php/gustav/compare/0.0.43...1.0.0-RC1
