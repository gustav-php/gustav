<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Get};
use GustavPHP\Gustav\Controller;
use GustavPHP\Tests\Integration\Services\{Nested, Simple};

#[ControllerAttribute('/services')]
class Services extends Controller\Base
{
    public function __construct(
        protected Simple $simple,
        protected Nested $nested
    ) {
    }

    #[Get('/nested')]
    public function returnNested(): Controller\Response
    {
        return $this->plaintext($this->nested->getTestValue());
    }

    #[Get('/simple')]
    public function returnSimple(): Controller\Response
    {
        return $this->plaintext($this->simple->getTestValue());
    }
}
