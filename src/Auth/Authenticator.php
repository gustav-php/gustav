<?php

namespace GustavPHP\Gustav\Auth;

use Psr\Http\Message\ServerRequestInterface;

interface Authenticator
{
    public function authenticate(ServerRequestInterface $request): Identity;
}
