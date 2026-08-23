<?php

namespace GustavPHP\Gustav\View;

use GustavPHP\Gustav\View;
use GustavPHP\Gustav\View\Exception\{ViewNotFoundException, ViewRenderingException};

final class PhpViewRenderer implements ViewRendererInterface
{
    private ?string $resolvedRoot = null;

    public function __construct(private readonly ?string $root)
    {
    }

    public function render(View $view): string
    {
        return (new Template($this))->render($view->template, $view->data);
    }

    /** @internal */
    public function resolve(string $template): string
    {
        $root = $this->resolvedRoot();
        $logicalName = $this->logicalName($template);
        $path = realpath($root . DIRECTORY_SEPARATOR . $logicalName);

        if ($path === false || !is_file($path) || !is_readable($path)) {
            throw new ViewNotFoundException("View '{$template}' was not found");
        }

        $prefix = $root === DIRECTORY_SEPARATOR
            ? $root
            : $root . DIRECTORY_SEPARATOR;
        if (!str_starts_with($path, $prefix)) {
            throw new ViewNotFoundException(
                "View '{$template}' must stay within the configured directory",
            );
        }

        return $path;
    }

    private function logicalName(string $template): string
    {
        if (
            str_starts_with($template, '/')
            || str_starts_with($template, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $template) === 1
            || str_contains($template, '\\')
        ) {
            throw new ViewNotFoundException(
                "View '{$template}' must be a relative logical name",
            );
        }

        $segments = explode('/', $template);
        if (in_array('.', $segments, true) || in_array('..', $segments, true)) {
            throw new ViewNotFoundException(
                "View '{$template}' must stay within the configured directory",
            );
        }
        if (
            in_array('', $segments, true)
            || preg_match('/^[A-Za-z0-9_.\/-]+$/D', $template) !== 1
        ) {
            throw new ViewNotFoundException("View '{$template}' is not a valid logical name");
        }

        $extension = pathinfo($template, PATHINFO_EXTENSION);
        if ($extension === '') {
            return $template . '.php';
        }
        if ($extension !== 'php') {
            throw new ViewNotFoundException("View '{$template}' must use the .php extension");
        }

        return $template;
    }

    private function resolvedRoot(): string
    {
        if ($this->root === null || trim($this->root) === '') {
            throw new ViewRenderingException('View directory is not configured');
        }
        if ($this->resolvedRoot !== null) {
            return $this->resolvedRoot;
        }

        $root = realpath($this->root);
        if ($root === false || !is_dir($root)) {
            throw new ViewRenderingException("View directory '{$this->root}' does not exist");
        }

        return $this->resolvedRoot = rtrim($root, '/\\') ?: DIRECTORY_SEPARATOR;
    }
}
