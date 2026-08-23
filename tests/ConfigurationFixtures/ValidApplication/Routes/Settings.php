<?php

namespace GustavPHP\Tests\ConfigurationFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;
use GustavPHP\Tests\ConfigurationFixtures\ValidApplication\Config\ApplicationSettings;

final class Settings extends Controller\Base
{
    public function __construct(private readonly ApplicationSettings $settings)
    {
    }

    #[Route('/configuration/identity')]
    public function identity(): int
    {
        return spl_object_id($this->settings);
    }

    /** @return array{name:string,debug:bool,port:int} */
    #[Route('/configuration')]
    public function show(): array
    {
        return [
            'name' => $this->settings->name,
            'debug' => $this->settings->debug,
            'port' => $this->settings->port,
        ];
    }
}
