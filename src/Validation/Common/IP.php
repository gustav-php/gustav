<?php

namespace GustavPHP\Gustav\Validation\Common;

use Exception;
use GustavPHP\Gustav\Validation\Validation;

class IP extends Validation
{
    public function __construct(
        protected bool $onlyV4 = false,
        protected bool $onlyV6 = false
    ) {
        if ($this->onlyV4 && $this->onlyV6) {
            throw new Exception("Cannot specify both onlyV4 and onlyV6");
        }
    }
    public function validate(mixed $value): true
    {

        if (!filter_var($value, FILTER_VALIDATE_IP, match (true) {
            $this->onlyV4 => FILTER_FLAG_IPV4,
            $this->onlyV6 => FILTER_FLAG_IPV6,
            default => FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6
        })) {
            throw new Exception("Invalid IP address");
        }
        return true;
    }
}
