<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;

class Responses extends Controller\Base
{
    #[Route('/responses/html')]
    public function returnHtml(): Controller\Response
    {
        return $this->html('<h1>lorem ipsum</h1>');
    }

    #[Route('/responses/json')]
    public function returnJson(): Controller\Response
    {
        return $this->json([
            'string' => 'lorem ipsum',
            'number' => 123,
            'boolean' => true,
            'null' => null,
            'array' => [
                'lorem',
                'ipsum',
                'dolor',
                'sit',
                'amet'
            ],
            'object' => [
                'lorem' => 'ipsum',
                'dolor' => 'sit',
                'amet' => 'consectetur'
            ]
        ]);
    }
    #[Route('/responses/plaintext')]
    public function returnPlaintext(): Controller\Response
    {
        return $this->plaintext('lorem ipsum');
    }

    #[Route('/responses/redirect')]
    public function returnRedirect(): Controller\Response
    {
        return $this->redirect('/responses/plaintext', 301);
    }

    #[Route('/responses/xml')]
    public function returnXml(): Controller\Response
    {
        return $this->xml('<root><lorem>ipsum</lorem></root>');
    }
}
