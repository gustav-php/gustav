<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Get};
use GustavPHP\Gustav\Controller;
use Psr\Http\Message\ResponseInterface;

#[ControllerAttribute]
final class AmbiguousResponseController extends Controller\Base
{
    #[Get('/invalid-response/union')]
    public function invalid(): Controller\Response|ResponseInterface
    {
        return $this->json([]);
    }
}
