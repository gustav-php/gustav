<?php

namespace GustavPHP\Tests\Integration\Services;

use GustavPHP\Gustav\Attribute\Service;
use GustavPHP\Gustav\Service\Lifetime;

#[Service(lifetime: Lifetime::Transient)]
class TransientState
{
}
