<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{JsonResponse, Route};
use GustavPHP\Gustav\Controller;

final class InvalidJsonResponseController extends Controller\Base
{
    #[Route('/invalid-response/json')]
    #[JsonResponse]
    public function invalid(): Controller\Response
    {
        return $this->json([]);
    }
}
