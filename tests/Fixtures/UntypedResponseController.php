<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;

final class UntypedResponseController extends Controller\Base
{
    #[Route('/invalid-response/untyped')]
    public function invalid()
    {
        return $this->json([]);
    }
}
