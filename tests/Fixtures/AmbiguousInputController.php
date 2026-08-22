<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{Query, Route};
use GustavPHP\Gustav\Controller;

class AmbiguousInputController extends Controller\Base
{
    #[Route('/invalid-union')]
    public function invalid(#[Query('value')] int|string $value): Controller\Response
    {
        return $this->json(['value' => $value]);
    }
}
