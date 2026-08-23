<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Get, Request};
use GustavPHP\Gustav\Controller;
use Psr\Http\Message\ServerRequestInterface;

#[ControllerAttribute('/request')]
class Requests extends Controller\Base
{
    #[Get]
    public function get(#[Request] ServerRequestInterface $request): Controller\Response
    {
        return $this->json((array) $request);
    }
}
