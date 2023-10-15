<?php

namespace GustavPHP\Gustav;

use Latte\Engine;

class View
{
    static protected ?Engine $engine = null;
    static protected function getEngine(): Engine
    {
        if (self::$engine === null) {
            self::$engine = new Engine();
            self::$engine->setTempDirectory(__DIR__ . '/../cache');
            self::$engine->setautoRefresh();
        }
        return self::$engine;
    }
    static public function render(string $template, array $params = []): string
    {
        return self::getEngine()->renderToString($template, $params);
    }
}
