<?php


function startServer(): void
{
    stopServer();
    $command = 'nohup ' . getcwd() . DIRECTORY_SEPARATOR . 'rr serve -p -c .rr.tests.yaml > /dev/null &';
    exec($command);
    sleep(1);
}

function stopServer(): void
{
    $command = 'nohup ' . getcwd() . DIRECTORY_SEPARATOR . 'rr stop > /dev/null &';
    exec($command);
}

beforeEach(fn () => startServer());
afterEach(fn () => stopServer());

$client = new GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:5173']);

test('can respond with plaintext', function () use ($client) {
    $response = $client->request('GET', '/responses/plaintext');
    expect($response->getBody()->getContents())->toBe('lorem ipsum');
    expect($response->getStatusCode())->toBe(200);
});

test('can respond with html', function () use ($client) {
    $response = $client->request('GET', '/responses/html');
    expect($response->getBody()->getContents())->toBe('<h1>lorem ipsum</h1>');
    expect($response->getStatusCode())->toBe(200);
});

test('can respond with xml', function () use ($client) {
    $response = $client->request('GET', '/responses/xml');
    expect($response->getBody()->getContents())->toBe('<root><lorem>ipsum</lorem></root>');
    expect($response->getStatusCode())->toBe(200);
});

test('can respond with json', function () use ($client) {
    $response = $client->request('GET', '/responses/json');
    expect($response->getBody()->getContents())->toBe('{"string":"lorem ipsum","number":123,"boolean":true,"null":null,"array":["lorem","ipsum","dolor","sit","amet"],"object":{"lorem":"ipsum","dolor":"sit","amet":"consectetur"}}');
    expect($response->getStatusCode())->toBe(200);
});

test('can respond with redirect', function () use ($client) {
    $response = $client->request('GET', '/responses/redirect');
    expect($response->getBody()->getContents())->toBe('lorem ipsum');
    expect($response->getStatusCode())->toBe(200);
});
