<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class Csrf
{
}
