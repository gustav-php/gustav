<?php

namespace GustavPHP\Gustav\Config;

final readonly class Violation
{
    /**
     * @param class-string $configuration
     */
    public function __construct(
        public string $configuration,
        public string $property,
        public string $variable,
        public string $code,
        public string $message,
    ) {
    }

    /**
     * @return array{
     *     configuration:class-string,
     *     property:string,
     *     variable:string,
     *     code:string,
     *     message:string
     * }
     */
    public function toArray(): array
    {
        return [
            'configuration' => $this->configuration,
            'property' => $this->property,
            'variable' => $this->variable,
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
