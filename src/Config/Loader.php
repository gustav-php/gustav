<?php

namespace GustavPHP\Gustav\Config;

use GustavPHP\Gustav\Config\Exception\ConfigurationException;

/** @internal */
final readonly class Loader
{
    public function __construct(private Environment $environment)
    {
    }

    /**
     * @param iterable<class-string> $classes
     * @return array<class-string,object>
     */
    public function load(iterable $classes): array
    {
        $unique = [];
        foreach ($classes as $class) {
            $unique[$class] = true;
        }
        $classes = array_keys($unique);
        sort($classes);

        $configurations = [];
        $violations = [];
        foreach ($classes as $class) {
            try {
                $configurations[$class] = (new Hydrator($class))->hydrate($this->environment);
            } catch (ConfigurationException $exception) {
                array_push($violations, ...$exception->getViolations());
            }
        }

        if ($violations !== []) {
            throw new ConfigurationException($violations);
        }

        return $configurations;
    }
}
