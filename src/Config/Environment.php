<?php

namespace GustavPHP\Gustav\Config;

use GustavPHP\Gustav\Config\Exception\ConfigurationException;
use InvalidArgumentException;
use Symfony\Component\Dotenv\Dotenv;
use Throwable;

final readonly class Environment
{
    /**
     * @param array<string,string> $variables
     */
    private function __construct(private array $variables)
    {
    }

    /**
     * Create an isolated environment, primarily for tests.
     *
     * @param array<string,string> $variables
     */
    public static function fromArray(array $variables): self
    {
        foreach ($variables as $name => $value) {
            if (!is_string($name) || $name === '') {
                throw new InvalidArgumentException('Environment variable names must be non-empty strings');
            }
            if (!is_string($value)) {
                throw new InvalidArgumentException("Environment variable '{$name}' must be a string");
            }
        }

        return new self($variables);
    }

    public function get(string $name): ?string
    {
        return $this->variables[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->variables);
    }

    /**
     * Load .env and .env.local from a project directory. The optional system
     * map exists so tests can avoid changing process-global state.
     *
     * @param null|array<string,string> $system
     */
    public static function load(string $root, ?array $system = null): self
    {
        $root = self::normalizeRoot($root);
        $contents = [];
        $names = [];

        foreach (['.env', '.env.local'] as $name) {
            $path = ($root === DIRECTORY_SEPARATOR ? $root : $root . DIRECTORY_SEPARATOR) . $name;
            if (!file_exists($path)) {
                continue;
            }
            if (!is_file($path) || !is_readable($path)) {
                throw self::fileException($name, 'Environment file is not readable', 'unreadable_environment_file');
            }
            $content = file_get_contents($path);
            if ($content === false) {
                throw self::fileException($name, 'Environment file is not readable', 'unreadable_environment_file');
            }
            $contents[] = $content;
            $names[] = $name;
        }

        $variables = [];
        if ($contents !== []) {
            try {
                /** @var array<string,string> $variables */
                $variables = (new Dotenv())->parse(
                    implode("\n", $contents),
                    implode(', ', $names),
                );
            } catch (Throwable) {
                throw self::fileException(
                    implode(', ', $names),
                    'Environment file could not be parsed',
                    'invalid_environment_file',
                );
            }
        }

        $system ??= self::systemVariables();
        self::fromArray($system);

        return new self([...$variables, ...$system]);
    }

    public static function system(): self
    {
        return new self(self::systemVariables());
    }

    private static function fileException(string $name, string $message, string $code): ConfigurationException
    {
        return new ConfigurationException([
            new Violation(
                configuration: self::class,
                property: 'file',
                variable: $name,
                code: $code,
                message: $message,
            ),
        ]);
    }

    private static function normalizeRoot(string $root): string
    {
        if ($root === '') {
            throw new InvalidArgumentException('Project root must be a non-empty path');
        }

        $normalized = rtrim($root, '/\\');

        return $normalized === '' ? DIRECTORY_SEPARATOR : $normalized;
    }

    /** @return array<string,string> */
    private static function systemVariables(): array
    {
        $variables = [];
        foreach ($_ENV as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $variables[$name] = $value;
            }
        }

        $process = getenv();
        if (is_array($process)) {
            foreach ($process as $name => $value) {
                $variables[$name] = $value;
            }
        }

        return $variables;
    }
}
