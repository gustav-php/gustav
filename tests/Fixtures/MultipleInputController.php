<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Header, Query, Route};
use GustavPHP\Gustav\Controller;

class MultipleInputController extends Controller\Base
{
    #[Route('/invalid-sources')]
    public function invalid(
        #[Query('value')]
        #[Header('X-Value')]
        string $value,
    ): Controller\Response {
        return $this->json(['value' => $value]);
    }
}
