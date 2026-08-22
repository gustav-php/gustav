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
prints its logs if the contract fails.

## Documentation

- https://gustav-php.github.io
