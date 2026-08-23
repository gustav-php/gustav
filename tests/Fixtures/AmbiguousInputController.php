<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Get, Query};
use GustavPHP\Gustav\Controller;

#[ControllerAttribute]
class AmbiguousInputController extends Controller\Base
{
    #[Get('/invalid-union')]
    public function invalid(#[Query('value')] int|string $value): Controller\Response
    {
        return $this->json(['value' => $value]);
    }
}
