<?php

namespace GustavPHP\Tests\Fixtures\QuietLogging\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Gustav\Service\Lifetime;
use Psr\Log\{LoggerInterface, NullLogger};

#[Service(as: LoggerInterface::class, lifetime: Lifetime::Singleton)]
final class QuietLogger extends NullLogger
{
}
