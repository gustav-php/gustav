<?php

namespace GustavPHP\Gustav;

use Latte\Engine;

class View
{
    protected static ?Engine $engine = null;
    public static function render(string $template, array $params = []): string
    {
        return self::getEngine()->renderToString($template, $params);
    }

    protected static function getEngine(): Engine
    {
        if (self::$engine === null) {
            self::$engine = new Engine();
            self::$engine->setTempDirectory(Application::$configuration->cache . DIRECTORY_SEPARATOR . 'latte');
            self::$engine->setautoRefresh();
        }
        return self::$engine;
    }
}
