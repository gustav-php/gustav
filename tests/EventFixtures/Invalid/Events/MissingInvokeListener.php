<?php

namespace GustavPHP\Tests\EventFixtures\Invalid\Events;

use GustavPHP\Gustav\Attribute\Listener;

#[Listener]
final readonly class MissingInvokeListener
{
}
