<?php

namespace GustavPHP\Gustav;

use Latte\Engine;
use Latte\RuntimeException;

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
     * @param array $params
     * @return string
     * @throws \LogicException
     * @throws RuntimeException
     * @throws \Throwable
     */
    public static function render(string $template, array $params = []): string
    {
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
            self::$engine->setTempDirectory(Application::$configuration->cache);
            self::$engine->setautoRefresh();
        }
        return self::$engine;
    }
}
