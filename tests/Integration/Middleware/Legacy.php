<?php

namespace GustavPHP\Tests\Integration\Middleware;

use GustavPHP\Gustav\Middleware\Base;
use Psr\Http\Message\ServerRequestInterface;

class Legacy extends Base
{
    public function handle(ServerRequestInterface $request): ServerRequestInterface
    {
        $trace = $request->getAttribute('middleware-trace', []);
        $trace[] = 'legacy';

        return $request->withAttribute('middleware-trace', $trace);
    }
}
