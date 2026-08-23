<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Get};
use GustavPHP\Gustav\Controller;

#[ControllerAttribute]
final class UntypedResponseController extends Controller\Base
{
    #[Get('/invalid-response/untyped')]
    public function invalid()
    {
        return $this->json([]);
    }
}
