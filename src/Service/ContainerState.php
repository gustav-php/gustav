<?php

namespace GustavPHP\Gustav\Service;

use ReflectionClass;

/** @internal */
final class ContainerState
{
    public bool $built = false;

    /** @var array<string, Definition> */
    public array $definitions = [];

    /** @var array<class-string, ReflectionClass<object>> */
    public array $reflectors = [];

    /** @var array<string, mixed> */
    public array $singletons = [];
}
