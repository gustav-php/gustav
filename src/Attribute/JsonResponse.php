<?php

namespace GustavPHP\Gustav\Attribute;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class JsonResponse
{
    /**
     * @param array<string,string|array<string>> $headers
     */
    public function __construct(
        public int $status = 200,
        public array $headers = [],
    ) {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException('JSON response status must be between 100 and 599');
        }
    }
}
