<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Get};
use GustavPHP\Gustav\Controller;

#[ControllerAttribute]
final class UnsupportedResponseController extends Controller\Base
{
    #[Get('/invalid-response/unsupported')]
    public function invalid(): mixed
    {
        return [];
    }
}
