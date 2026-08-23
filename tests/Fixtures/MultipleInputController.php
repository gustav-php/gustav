<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Get, Header, Query};
use GustavPHP\Gustav\Controller;

#[ControllerAttribute]
class MultipleInputController extends Controller\Base
{
    #[Get('/invalid-sources')]
    public function invalid(
        #[Query('value')]
        #[Header('X-Value')]
        string $value,
    ): Controller\Response {
        return $this->json(['value' => $value]);
    }
}
