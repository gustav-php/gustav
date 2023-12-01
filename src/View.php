<?php

namespace GustavPHP\Gustav;

use Latte\{Engine, RuntimeException};
use LogicException;
use Throwable;

class View
{
    /**
     * The Latte engine.
     * @var null|Engine
     */
    protected static ?Engine $engine = null;

    /**
     * Render a template.
     *
     * @param string $template
     * @param array<mixed> $params
     * @return string
     * @throws LogicException
     * @throws RuntimeException
     * @throws Throwable
     */
    public static function render(string $template, array $params = []): string
    {
        if (Application::$configuration->views) {
            $template = Application::$configuration->views . DIRECTORY_SEPARATOR . $template;
        }
        return self::getEngine()->renderToString($template, $params);
    }

    /**
     * Get the Latte engine.
     *
     * @return Engine
     */
    protected static function getEngine(): Engine
    {
        if (self::$engine === null) {
            self::$engine = new Engine();
            if (Application::isProduction()) {
                self::$engine
                    ->setTempDirectory(Application::$configuration->cache)
                    ->setAutoRefresh(false);
            }
        }
        return self::$engine;
    }
}
