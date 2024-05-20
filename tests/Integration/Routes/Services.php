<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;
use GustavPHP\Tests\Integration\Services\{Nested, Simple};

class Services extends Controller\Base
{
    public function __construct(
        protected Simple $simple,
        protected Nested $nested
    ) {
    }

    #[Route('/services/nested')]
    public function returnNested(): Controller\Response
    {
        return $this->plaintext($this->nested->getTestValue());
    }

    #[Route('/services/simple')]
    public function returnSimple(): Controller\Response
    {
        return $this->plaintext($this->simple->getTestValue());
    }
}
