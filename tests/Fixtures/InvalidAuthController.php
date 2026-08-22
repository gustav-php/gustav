<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{AuthUser, Route};
use GustavPHP\Gustav\Controller;

class InvalidAuthController extends Controller\Base
{
    #[Route('/invalid-auth')]
    public function invalid(#[AuthUser] string $identity): Controller\Response
    {
        return $this->plaintext($identity);
    }
}
