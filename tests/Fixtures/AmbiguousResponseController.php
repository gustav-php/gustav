<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;
use Psr\Http\Message\ResponseInterface;

final class AmbiguousResponseController extends Controller\Base
{
    #[Route('/invalid-response/union')]
    public function invalid(): Controller\Response|ResponseInterface
    {
        return $this->json([]);
    }
}
