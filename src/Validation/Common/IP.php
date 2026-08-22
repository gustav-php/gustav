<?php

namespace GustavPHP\Gustav\Validation\Common;

use GustavPHP\Gustav\Validation\{RuleViolation, Validation};
use InvalidArgumentException;

class IP extends Validation
{
    public function __construct(
        protected bool $onlyV4 = false,
        protected bool $onlyV6 = false
    ) {
        if ($this->onlyV4 && $this->onlyV6) {
            throw new InvalidArgumentException('Cannot specify both onlyV4 and onlyV6');
        }
    }

    public function getViolation(mixed $value): ?RuleViolation
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_IP, match (true) {
            $this->onlyV4 => FILTER_FLAG_IPV4,
            $this->onlyV6 => FILTER_FLAG_IPV6,
            default => 0,
        }) === false) {
            return new RuleViolation('invalid_ip', 'IP address is invalid');
        }

        return null;
    }
}
