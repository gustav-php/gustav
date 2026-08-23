<?php

namespace GustavPHP\Gustav\Http\Binding;

use Psr\Http\Message\ServerRequestInterface;

final class BindingContext
{
    /** @var array<array-key,mixed> */
    private array $body = [];
    private bool $bodyParsed = false;

    /**
     * @param array<string,string> $params
     */
    public function __construct(
        public readonly ServerRequestInterface $request,
        private readonly array $params,
        private readonly RequestBodyParser $bodyParser = new RequestBodyParser(),
    ) {
    }

    /**
     * @return array<array-key,mixed>
     */
    public function body(): array
    {
        if (!$this->bodyParsed) {
            $this->body = $this->bodyParser->parse($this->request);
            $this->bodyParsed = true;
        }

        return $this->body;
    }

    /**
     * @return array<string,string>
     */
    public function params(): array
    {
        return $this->params;
    }
}
