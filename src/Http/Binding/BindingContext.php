<?php

namespace GustavPHP\Gustav\Http\Binding;

use Psr\Http\Message\ServerRequestInterface;

final class BindingContext
{
    /** @var array<array-key,mixed> */
    private array $body = [];
    private bool $bodyParsed = false;

    /** @var array<string,string> */
    private array $params = [];
    private bool $paramsResolved = false;

    /**
     * @param array<string,int> $placeholders
     */
    public function __construct(
        public readonly ServerRequestInterface $request,
        private readonly array $placeholders,
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
        if (!$this->paramsResolved) {
            $parts = explode('/', trim($this->request->getUri()->getPath(), '/'));
            foreach ($this->placeholders as $name => $index) {
                if (array_key_exists($index, $parts)) {
                    $this->params[$name] = $parts[$index];
                }
            }
            $this->paramsResolved = true;
        }

        return $this->params;
    }
}
