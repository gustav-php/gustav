<?php

namespace GustavPHP\Gustav\View;

use BackedEnum;
use GustavPHP\Gustav\View\Exception\ViewRenderingException;
use Stringable;
use Throwable;

final class Template
{
    /** @var list<string> */
    private const RESERVED_VARIABLES = [
        'GLOBALS',
        '_COOKIE',
        '_ENV',
        '_FILES',
        '_GET',
        '_POST',
        '_REQUEST',
        '_SERVER',
        '_SESSION',
        '__gustavData',
        '__gustavPath',
        'model',
        'this',
        'view',
    ];

    /** @var list<string> */
    private array $activeTemplates;

    /** @var null|array{string,array<mixed>|object} */
    private ?array $layout = null;

    /** @var array<string,string> */
    private array $sections = [];

    /** @var list<array{name:string,level:int}> */
    private array $sectionStack = [];

    /** @param list<string> $ancestors */
    public function __construct(
        private readonly PhpViewRenderer $renderer,
        array $ancestors = [],
    ) {
        $this->activeTemplates = $ancestors;
    }

    public function e(mixed $value): string
    {
        return $this->escape($value);
    }

    public function end(): void
    {
        $capture = array_pop($this->sectionStack);
        if ($capture === null) {
            throw new ViewRenderingException('Cannot end a view section that was not started');
        }
        if (ob_get_level() !== $capture['level'] + 1) {
            throw new ViewRenderingException(
                "View section '{$capture['name']}' changed the output buffer unexpectedly",
            );
        }

        $contents = ob_get_clean();
        if ($contents === false) {
            throw new ViewRenderingException("Unable to capture view section '{$capture['name']}'");
        }
        $this->sections[$capture['name']] = $contents;
    }

    public function escape(mixed $value): string
    {
        if ($value instanceof SafeHtml) {
            return $value->value;
        }
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }
        if ($value === null) {
            return '';
        }
        if (!is_scalar($value) && !$value instanceof Stringable) {
            throw new ViewRenderingException('Unable to escape value of type ' . get_debug_type($value));
        }

        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
    }

    /** @param array<mixed>|object $data */
    public function layout(string $template, array|object $data = []): void
    {
        if ($this->layout !== null) {
            throw new ViewRenderingException('A view template can declare only one layout');
        }
        $this->layout = [$template, $data];
    }

    /** @param array<mixed>|object $data */
    public function partial(string $template, array|object $data = []): SafeHtml
    {
        return new SafeHtml(
            (new self($this->renderer, $this->activeTemplates))->render($template, $data),
        );
    }

    public function raw(string|Stringable $html): SafeHtml
    {
        return new SafeHtml((string) $html);
    }

    /** @param array<mixed>|object $data */
    public function render(string $template, array|object $data): string
    {
        $layouts = [];
        while (true) {
            $this->layout = null;
            if (in_array($template, $layouts, true)) {
                throw new ViewRenderingException("Circular view layout detected at '{$template}'");
            }
            $layouts[] = $template;
            $contents = $this->capture($template, $data);
            $layout = $this->consumeLayout();
            if ($layout === null) {
                return $contents;
            }

            $this->sections['content'] = $contents;
            [$template, $data] = $layout;
        }
    }

    public function section(string $name, string|SafeHtml $default = ''): SafeHtml
    {
        $this->assertSectionName($name);
        if (array_key_exists($name, $this->sections)) {
            return new SafeHtml($this->sections[$name]);
        }

        return $default instanceof SafeHtml
            ? $default
            : new SafeHtml($this->escape($default));
    }

    public function start(string $name): void
    {
        $this->assertSectionName($name);
        $level = ob_get_level();
        ob_start();
        $this->sectionStack[] = ['name' => $name, 'level' => $level];
    }

    private function assertSectionName(string $name): void
    {
        if (preg_match('/^[A-Za-z][A-Za-z0-9_.-]*$/D', $name) !== 1) {
            throw new ViewRenderingException("Invalid view section '{$name}'");
        }
    }

    /** @param array<mixed> $data */
    private function assertVariableNames(array $data): void
    {
        foreach (array_keys($data) as $name) {
            if (!is_string($name) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $name) !== 1) {
                $display = is_string($name) ? $name : (string) $name;
                throw new ViewRenderingException("Invalid view variable '{$display}'");
            }
            if (in_array($name, self::RESERVED_VARIABLES, true) || str_starts_with($name, '__gustav')) {
                throw new ViewRenderingException("View variable '{$name}' is reserved");
            }
        }
    }

    /** @param array<mixed>|object $data */
    private function capture(string $template, array|object $data): string
    {
        if (in_array($template, $this->activeTemplates, true)) {
            throw new ViewRenderingException("Circular view partial detected at '{$template}'");
        }

        $level = ob_get_level();
        $sectionDepth = count($this->sectionStack);
        $this->activeTemplates[] = $template;
        ob_start();

        try {
            $this->includeTemplate($this->renderer->resolve($template), $data);

            if (count($this->sectionStack) !== $sectionDepth) {
                $section = end($this->sectionStack);
                $name = $section === false ? 'unknown' : $section['name'];
                throw new ViewRenderingException("View section '{$name}' was not closed");
            }
            if (ob_get_level() !== $level + 1) {
                throw new ViewRenderingException(
                    "View '{$template}' changed the output buffer unexpectedly",
                );
            }

            $contents = ob_get_clean();
            if ($contents === false) {
                throw new ViewRenderingException("Unable to capture view '{$template}'");
            }

            return $contents;
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                $before = ob_get_level();
                if (!@ob_end_clean() || ob_get_level() >= $before) {
                    break;
                }
            }
            $this->sectionStack = array_slice($this->sectionStack, 0, $sectionDepth);

            if (ob_get_level() !== $level) {
                throw new ViewRenderingException(
                    "View '{$template}' could not restore the output buffer",
                    previous: $exception,
                );
            }

            throw $exception;
        } finally {
            array_pop($this->activeTemplates);
        }
    }

    /** @return null|array{string,array<mixed>|object} */
    private function consumeLayout(): ?array
    {
        $layout = $this->layout;
        $this->layout = null;

        return $layout;
    }

    /** @param array<mixed>|object $__gustavData */
    private function includeTemplate(string $__gustavPath, array|object $__gustavData): void
    {
        $view = $this;
        $model = $__gustavData;

        if (is_array($__gustavData)) {
            $this->assertVariableNames($__gustavData);
            extract($__gustavData, EXTR_SKIP);
        }

        require $__gustavPath;
    }
}
