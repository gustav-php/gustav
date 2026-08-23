<?php

namespace GustavPHP\Tests\Fixtures;

use GustavPHP\Gustav\Attribute\{AuthUser, Controller as ControllerAttribute, Get};
use GustavPHP\Gustav\Controller;

#[ControllerAttribute]
class InvalidAuthController extends Controller\Base
{
    #[Get('/invalid-auth')]
    public function invalid(#[AuthUser] string $identity): Controller\Response
    {
        return $this->plaintext($identity);
    }
}
