<?php

namespace GustavPHP\Gustav\Security;

use GustavPHP\Gustav\Http\Binding\RequestBodyParser;
use GustavPHP\Gustav\Http\Exception\CsrfException;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

final readonly class CsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CsrfTokenManager $tokens,
        private RequestBodyParser $bodyParser,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeader(CsrfTokenManager::HEADER);
        if ($header !== [] && (count($header) !== 1 || trim($header[0]) === '')) {
            throw new CsrfException();
        }
        $submitted = $header === [] ? null : trim($header[0]);

        $parsed = $request->getParsedBody();
        if ($submitted === null) {
            $body = $this->bodyParser->parse($request);
            $submitted = $body[CsrfTokenManager::FIELD] ?? null;
            unset($body[CsrfTokenManager::FIELD]);
            $request = $request->withParsedBody($body);
        } elseif (is_array($parsed) || is_object($parsed)) {
            $body = is_array($parsed) ? $parsed : get_object_vars($parsed);
            unset($body[CsrfTokenManager::FIELD]);
            $request = $request->withParsedBody($body);
        }

        if (!$this->tokens->isValid($submitted)) {
            throw new CsrfException();
        }

        return $handler->handle($request);
    }
}
