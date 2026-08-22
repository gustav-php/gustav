<?php

namespace GustavPHP\Gustav\Http\Binding;

use GustavPHP\Gustav\Http\Exception\{MalformedInputException, UnsupportedMediaTypeException, ValidationException};
use GustavPHP\Gustav\Validation\Violation;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;

final class RequestBodyParser
{
    /**
     * @return array<array-key,mixed>
     */
    public function parse(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            return $parsed;
        }
        if (is_object($parsed)) {
            return get_object_vars($parsed);
        }

        $mediaType = $this->mediaType($request->getHeaderLine('Content-Type'));
        $raw = $this->readBody($request);

        if ($this->isJson($mediaType)) {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new MalformedInputException('Malformed JSON body');
            }

            if (!is_array($decoded)) {
                throw new ValidationException([
                    new Violation('body', '', 'invalid_object', 'JSON body must be an object or array'),
                ]);
            }

            return $decoded;
        }

        if ($raw === '') {
            return [];
        }

        if ($mediaType === 'application/x-www-form-urlencoded') {
            parse_str($raw, $data);

            return $data;
        }

        throw new UnsupportedMediaTypeException();
    }

    private function isJson(string $mediaType): bool
    {
        return $mediaType === 'application/json'
            || preg_match('/^application\/[a-z0-9!#$&^_.+-]+\+json$/i', $mediaType) === 1;
    }

    private function mediaType(string $contentType): string
    {
        return strtolower(trim(explode(';', $contentType, 2)[0]));
    }

    private function readBody(ServerRequestInterface $request): string
    {
        $stream = $request->getBody();
        $position = null;

        if ($stream->isSeekable()) {
            $position = $stream->tell();
            $stream->rewind();
        }

        try {
            return $stream->getContents();
        } finally {
            if ($position !== null) {
                $stream->seek($position);
            }
        }
    }
}
