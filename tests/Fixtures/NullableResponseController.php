<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;

final class NullableResponseController extends Controller\Base
{
    #[Route('/invalid-response/nullable')]
    public function invalid(): ?Controller\Response
    {
        return null;
    }
}
