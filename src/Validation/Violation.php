<?php

namespace GustavPHP\Gustav\Validation;

final readonly class Violation
{
    public function __construct(
        public string $source,
        public string $path,
        public string $code,
        public string $message,
    ) {
    }

    /**
     * @return array{source:string,path:string,code:string,message:string}
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'path' => $this->path,
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
