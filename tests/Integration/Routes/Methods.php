<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;
use GustavPHP\Gustav\Router\Method;

class Methods extends Controller\Base
{
    #[Route('/methods', Method::DELETE)]
    public function delete(): Controller\Response
    {
        return $this->plaintext('DELETE');
    }
    #[Route('/methods')]
    public function get(): Controller\Response
    {
        return $this->plaintext('GET');
    }

    #[Route('/methods', Method::OPTIONS)]
    public function options(): Controller\Response
    {
        return $this->plaintext('OPTIONS');
    }

    #[Route('/methods', Method::PATCH)]
    public function patch(): Controller\Response
    {
        return $this->plaintext('PATCH');
    }

    #[Route('/methods', Method::POST)]
    public function post(): Controller\Response
    {
        return $this->plaintext('POST');
    }

    #[Route('/methods', Method::PUT)]
    public function put(): Controller\Response
    {
        return $this->plaintext('PUT');
    }
}
