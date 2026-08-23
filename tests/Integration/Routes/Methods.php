<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{Controller as ControllerAttribute, Delete, Get, Options, Patch, Post, Put};
use GustavPHP\Gustav\Controller;

#[ControllerAttribute('/methods')]
class Methods extends Controller\Base
{
    #[Delete]
    public function delete(): Controller\Response
    {
        return $this->plaintext('DELETE');
    }
    #[Get]
    public function get(): Controller\Response
    {
        return $this->plaintext('GET');
    }

    #[Options]
    public function options(): Controller\Response
    {
        return $this->plaintext('OPTIONS');
    }

    #[Patch]
    public function patch(): Controller\Response
    {
        return $this->plaintext('PATCH');
    }

    #[Post]
    public function post(): Controller\Response
    {
        return $this->plaintext('POST');
    }

    #[Put]
    public function put(): Controller\Response
    {
        return $this->plaintext('PUT');
    }
}
