<?php

namespace GustavPHP\Tests\ConfigurationFixtures\ValidApplication\Routes;

use GustavPHP\Gustav\Attribute\{Controller, Get};
use GustavPHP\Tests\ConfigurationFixtures\ValidApplication\Config\ApplicationSettings;

#[Controller('/configuration')]
final class Settings
{
    public function __construct(private readonly ApplicationSettings $settings)
    {
    }

    #[Get('/identity')]
    public function identity(): int
    {
        return spl_object_id($this->settings);
    }

    /** @return array{name:string,debug:bool,port:int} */
    #[Get]
    public function show(): array
    {
        return [
            'name' => $this->settings->name,
            'debug' => $this->settings->debug,
            'port' => $this->settings->port,
        ];
    }
}
