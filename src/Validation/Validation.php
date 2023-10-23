<?php

namespace GustavPHP\Gustav\Validation;

abstract class Validation
{
    abstract public function validate(mixed $value): true;
}
