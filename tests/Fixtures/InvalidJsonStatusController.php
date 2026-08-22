<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{JsonResponse, Route};
use GustavPHP\Gustav\Controller;

final class InvalidJsonStatusController extends Controller\Base
{
    #[Route('/invalid-response/status')]
    #[JsonResponse(status: 99)]
    public function invalid(): array
    {
        return [];
    }
}
