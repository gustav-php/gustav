<?php

namespace GustavPHP\Tests\CommandFixtures\ValidApplication\Commands;

enum RunMode: string
{
    case Fast = 'fast';
    case Safe = 'safe';
}
