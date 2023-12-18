<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{
    Body,
    Cookie,
    Header,
    Query,
    Route
};
use GustavPHP\Gustav\Controller;

class Params extends Controller\Base
{
    #[Route('/params/body')]
    public function body(
        #[Body] array $all,
        #[Body('required')] string $required,
        #[Body('optional')] string $optional = 'default',
    ): Controller\Response {
        return $this->json([
            'required' => $required,
            'optional' => $optional,
            'all' => $all
        ]);
    }
    #[Route('/params/cookie')]
    public function cookie(
        #[Cookie] array $all,
        #[Cookie('a-session-console')] string $required,
        #[Cookie('optional')] string $optional = 'default',
    ): Controller\Response {
        return $this->json([
            'required' => $required,
            'optional' => $optional,
            'all' => $all
        ]);
    }

    #[Route('/params/header')]
    public function header(
        #[Header] array $all,
        #[Header('required')] string $required,
        #[Header('optional')] string $optional = 'default',
    ): Controller\Response {
        return $this->json([
            'required' => $required,
            'optional' => $optional,
            'all' => $all ?? ''
        ]);
    }

    #[Route('/params/query')]
    public function query(
        #[Query] array $all,
        #[Query('required')] string $required,
        #[Query('optional')] string $optional = 'default',
    ): Controller\Response {
        return $this->json([
            'required' => $required,
            'optional' => $optional,
            'all' => $all
        ]);
    }
}
