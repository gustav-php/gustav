<?php

namespace GustavPHP\Tests\Integration;

use GuzzleHttp\Client;

function createClient(): Client
{
    return new Client(['base_uri' => 'http://127.0.0.1:5173']);
}
