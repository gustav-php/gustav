<?php

namespace GustavPHP\Tests\Transport;

use JsonException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

const HOST = '127.0.0.1';

/**
 * @param array<int, int> $excluded
 */
function availablePort(array $excluded = []): int
{
    do {
        $errorCode = 0;
        $errorMessage = '';
        $socket = @stream_socket_server('tcp://' . HOST . ':0', $errorCode, $errorMessage);
        if ($socket === false) {
            throw new RuntimeException("Unable to reserve a local port: {$errorMessage} ({$errorCode})");
        }

        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        if ($address === false || !str_contains($address, ':')) {
            throw new RuntimeException('Unable to determine the reserved local port');
        }
        $port = (int) substr($address, strrpos($address, ':') + 1);
    } while (in_array($port, $excluded, true));

    return $port;
}

/**
 * @return array<string, mixed>
 */
function decodeJson(string $body, string $context): array
{
    try {
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new RuntimeException("{$context} was not valid JSON", previous: $exception);
    }

    if (!is_array($decoded)) {
        throw new RuntimeException("{$context} was not a JSON object");
    }

    return $decoded;
}

function roadRunnerBinary(string $root): string
{
    $filename = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
    $binary = $root . DIRECTORY_SEPARATOR . $filename;
    if (is_file($binary)) {
        return $binary;
    }

    echo "RoadRunner binary is missing; downloading it once...\n";
    $installer = new Process([
        PHP_BINARY,
        $root . '/vendor/bin/rr',
        'get-binary',
        '--no-config',
    ], $root);
    $installer->setTimeout(120);
    $installer->mustRun();

    if (!is_file($binary)) {
        throw new RuntimeException("RoadRunner installer did not create {$binary}");
    }

    return $binary;
}

/**
 * @param array<string, string> $headers
 * @return array{status: int, body: string, headers: array<int, string>}
 */
function request(
    int $port,
    string $method,
    string $path,
    ?string $body = null,
    array $headers = [],
): array {
    $headerLines = ['Accept: application/json', 'Connection: close'];
    foreach ($headers as $name => $value) {
        $headerLines[] = "{$name}: {$value}";
    }
    $options = [
        'method' => $method,
        'ignore_errors' => true,
        'timeout' => 2,
        'header' => $headerLines,
    ];
    if ($body !== null) {
        $options['content'] = $body;
    }

    $responseBody = @file_get_contents(
        'http://' . HOST . ":{$port}{$path}",
        false,
        stream_context_create(['http' => $options]),
    );
    if (function_exists('http_get_last_response_headers')) {
        /** @var callable(): null|array<int, string> $getHeaders */
        $getHeaders = 'http_get_last_response_headers';
        $responseHeaders = $getHeaders() ?? [];
    } else {
        $responseHeaders = $http_response_header ?? [];
    }
    if ($responseBody === false || $responseHeaders === []) {
        throw new RuntimeException("RoadRunner did not respond to {$method} {$path}");
    }
    if (!preg_match('/^HTTP\/\S+\s+(\d{3})/', $responseHeaders[0], $matches)) {
        throw new RuntimeException('RoadRunner returned an invalid HTTP status line');
    }

    return [
        'status' => (int) $matches[1],
        'body' => $responseBody,
        'headers' => $responseHeaders,
    ];
}

/**
 * @param array{status: int, body: string, headers: array<int, string>} $response
 */
function assertStatus(array $response, int $expected, string $context): void
{
    if ($response['status'] !== $expected) {
        throw new RuntimeException(
            "{$context} returned {$response['status']}; expected {$expected}. Body: {$response['body']}",
        );
    }
}

/**
 * @param array{status: int, body: string, headers: array<int, string>} $response
 */
function responseHeader(array $response, string $name): string
{
    foreach ($response['headers'] as $header) {
        if (!str_starts_with(strtolower($header), strtolower($name) . ':')) {
            continue;
        }

        return trim(substr($header, strlen($name) + 1));
    }

    return '';
}

/**
 * @param array{status: int, body: string, headers: array<int, string>} $response
 */
function responseCookiePair(array $response): string
{
    $setCookie = responseHeader($response, 'Set-Cookie');
    if ($setCookie === '' || !str_contains($setCookie, '=')) {
        throw new RuntimeException('Response did not contain a session cookie');
    }

    return explode(';', $setCookie, 2)[0];
}

/**
 * @return array{request: int, singleton: int}
 */
function serviceLifecycle(int $port): array
{
    $response = request($port, 'GET', '/services/lifecycle');
    assertStatus($response, 200, 'Service lifecycle request');
    $body = decodeJson($response['body'], 'Service lifecycle response');
    if (
        ($body['greeting'] ?? null) !== 'configured'
        || ($body['request'] ?? null) !== ($body['middleware'] ?? null)
        || ($body['transientsDiffer'] ?? null) !== true
        || !is_int($body['request'] ?? null)
        || !is_int($body['singleton'] ?? null)
    ) {
        throw new RuntimeException('Service lifecycle response did not satisfy the transport contract');
    }

    return ['request' => $body['request'], 'singleton' => $body['singleton']];
}

$root = dirname(__DIR__, 2);
$server = null;
$failure = null;

try {
    $httpPort = availablePort();
    $rpcPort = availablePort([$httpPort]);
    $server = new Process([
        roadRunnerBinary($root),
        'serve',
        '-c',
        '.rr.yaml',
        '-o',
        'http.address=' . HOST . ":{$httpPort}",
        '-o',
        'rpc.listen=tcp://' . HOST . ":{$rpcPort}",
    ], $root);
    $server->setTimeout(null);
    $server->start();

    $deadline = microtime(true) + 10;
    do {
        if (!$server->isRunning()) {
            throw new RuntimeException('RoadRunner stopped before becoming ready');
        }
        try {
            $ready = request($httpPort, 'GET', '/responses/plaintext');
        } catch (RuntimeException) {
            $ready = null;
        }
        if ($ready !== null && $ready['status'] === 200 && $ready['body'] === 'lorem ipsum') {
            break;
        }
        usleep(100_000);
    } while (microtime(true) < $deadline);
    if ($ready === null || $ready['status'] !== 200 || $ready['body'] !== 'lorem ipsum') {
        throw new RuntimeException('RoadRunner did not become ready within 10 seconds');
    }

    $json = request(
        $httpPort,
        'POST',
        '/params/body-dto',
        '{"email":"ada@example.com","age":"0","active":"false","status":"published","nickname":null}',
        ['Content-Type' => 'application/json'],
    );
    assertStatus($json, 200, 'JSON request');
    $jsonBody = decodeJson($json['body'], 'JSON response');
    if (($jsonBody['age'] ?? null) !== 0 || ($jsonBody['active'] ?? null) !== false) {
        throw new RuntimeException('JSON input was not converted to the declared scalar types');
    }

    $malformed = request(
        $httpPort,
        'POST',
        '/params/body-dto',
        '{"email":',
        ['Content-Type' => 'application/json'],
    );
    assertStatus($malformed, 400, 'Malformed JSON request');

    $csrfSeed = request($httpPort, 'GET', '/sessions/token');
    assertStatus($csrfSeed, 200, 'CSRF token request');
    $csrfBody = decodeJson($csrfSeed['body'], 'CSRF token response');
    $csrfToken = $csrfBody['token'] ?? null;
    if (!is_string($csrfToken)) {
        throw new RuntimeException('CSRF token response did not contain a token');
    }
    $sessionCookie = responseCookiePair($csrfSeed);

    $csrfRejected = request(
        $httpPort,
        'POST',
        '/sessions/value',
        '{"value":"blocked"}',
        [
            'Content-Type' => 'application/json',
            'Cookie' => $sessionCookie,
        ],
    );
    assertStatus($csrfRejected, 403, 'Missing CSRF token request');

    $csrfAccepted = request(
        $httpPort,
        'POST',
        '/sessions/value',
        '{"value":"transport"}',
        [
            'Content-Type' => 'application/json',
            'Cookie' => $sessionCookie,
            'X-CSRF-Token' => $csrfToken,
        ],
    );
    assertStatus($csrfAccepted, 200, 'Valid CSRF JSON request');
    $sessionValue = request(
        $httpPort,
        'GET',
        '/sessions/value',
        headers: ['Cookie' => $sessionCookie],
    );
    assertStatus($sessionValue, 200, 'Session request after rejected CSRF request');
    if ((decodeJson($sessionValue['body'], 'Session value response')['value'] ?? null) !== 'transport') {
        throw new RuntimeException('Session state did not survive the RoadRunner request sequence');
    }

    $view = request($httpPort, 'GET', '/responses/direct-view');
    assertStatus($view, 202, 'Native view request');
    if (
        responseHeader($view, 'Content-Type') !== 'text/html; charset=utf-8'
        || !str_contains($view['body'], '&lt;strong&gt;escaped&lt;/strong&gt;')
        || str_contains($view['body'], '<strong>escaped</strong>')
    ) {
        throw new RuntimeException('Native view response did not satisfy the transport contract');
    }

    $viewFailure = request($httpPort, 'GET', '/responses/view-missing');
    assertStatus($viewFailure, 500, 'Native view failure');
    $afterViewFailure = request($httpPort, 'GET', '/responses/view-helper');
    assertStatus($afterViewFailure, 200, 'View request after rendering failure');

    $first = serviceLifecycle($httpPort);
    assertStatus(request($httpPort, 'GET', '/services/lifecycle/error'), 418, 'Forced service failure');
    $next = serviceLifecycle($httpPort);
    if ($first['request'] === $next['request']) {
        throw new RuntimeException('Request-scoped service was reused after a failed request');
    }
    if ($first['singleton'] !== $next['singleton']) {
        throw new RuntimeException('Singleton service was not preserved by the worker');
    }

    $failed = request(
        $httpPort,
        'GET',
        '/kernel/server-error',
        headers: ['X-Request-ID' => 'transport-request-500'],
    );
    assertStatus($failed, 500, 'Server failure');
    $failedBody = decodeJson($failed['body'], 'Server failure response');
    if (($failedBody['error']['message'] ?? null) !== 'Server Error') {
        throw new RuntimeException('Production server failure response exposed an unsafe message');
    }
    if (responseHeader($failed, 'X-Request-ID') !== 'transport-request-500') {
        throw new RuntimeException('Server failure response did not preserve the request ID');
    }

    $afterFailure = request($httpPort, 'GET', '/responses/plaintext');
    assertStatus($afterFailure, 200, 'Request after server failure');

    $logDeadline = microtime(true) + 3;
    do {
        $serverOutput = $server->getOutput() . $server->getErrorOutput();
        if (
            str_contains($serverOutput, 'transport-request-500')
            && str_contains($serverOutput, 'http.status_code')
        ) {
            break;
        }
        usleep(50_000);
    } while (microtime(true) < $logDeadline);
    if (
        !str_contains($serverOutput, 'transport-request-500')
        || !str_contains($serverOutput, 'http.status_code')
    ) {
        throw new RuntimeException('RoadRunner did not collect the structured server failure log');
    }

    echo "RoadRunner transport contract passed\n";
    echo "  JSON request: 200\n";
    echo "  Malformed JSON: 400\n";
    echo "  Session/CSRF: 200 -> 403 -> 200 -> 200\n";
    echo "  Native views: 202 -> 500 -> 200\n";
    echo "  Worker sequence: 200 -> 418 -> 200\n";
    echo "  Server failure: 500 -> 200 (transport-request-500 logged)\n";
    echo "  Request scope: {$first['request']} -> {$next['request']}\n";
    echo "  Singleton: {$first['singleton']} -> {$next['singleton']}\n";
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    $server?->stop(5);
}

if ($failure !== null) {
    fwrite(STDERR, "RoadRunner transport contract failed: {$failure->getMessage()}\n");
    if ($server !== null) {
        $diagnostics = trim($server->getOutput() . $server->getErrorOutput());
        if ($diagnostics !== '') {
            fwrite(STDERR, "\nRoadRunner output:\n{$diagnostics}\n");
        }
    }

    exit(1);
}
