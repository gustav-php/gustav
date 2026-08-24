# Contributing

Contributions are welcome through focused pull requests against `main`.

Install the locked development dependencies with:

```bash
composer install
```

Before opening a pull request, run:

```bash
composer format
composer dump-autoload --optimize --strict-psr
composer test
composer check
composer lint
composer audit --format=plain
composer validate --strict --no-check-publish
git diff --check
```

Changes that cross the RoadRunner process boundary should also run:

```bash
composer test:transport
```

That command downloads the ignored local RoadRunner binary when it is missing,
selects free ports, and starts and stops the worker automatically.

Do not add Composer classmap exclusions, ignored static-analysis errors,
test-only branches in production code, or new analysis baselines. Add tests for
observable behavior changes and keep unrelated refactors out of the pull
request.

Security reports must follow [SECURITY.md](SECURITY.md) instead of using a
public issue.
