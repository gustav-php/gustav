<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Get};
use GustavPHP\Gustav\Controller;

#[ControllerAttribute]
final class NullableResponseController extends Controller\Base
{
    #[Get('/invalid-response/nullable')]
    public function invalid(): ?Controller\Response
    {
        return null;
    }
}
