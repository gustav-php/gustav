<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;

final class UnsupportedResponseController extends Controller\Base
{
    #[Route('/invalid-response/unsupported')]
    public function invalid(): mixed
    {
        return [];
    }
}
