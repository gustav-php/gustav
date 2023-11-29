<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{Request, Route};
use GustavPHP\Gustav\Controller;
use Psr\Http\Message\ServerRequestInterface;

class Requests extends Controller\Base
{
    #[Route('/request')]
    public function get(#[Request] ServerRequestInterface $request): Controller\Response
    {
        return $this->json((array) $request);
    }
}
