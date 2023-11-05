<?php

$client = new GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:5173']);

describe('response', function () use ($client) {
    it('can be plain text', function () use ($client) {
        $response = $client->request('GET', '/responses/plaintext');
        expect($response->getBody()->getContents())->toBe('lorem ipsum');
        expect($response->getStatusCode())->toBe(200);
    });
    
    it('can be html', function () use ($client) {
        $response = $client->request('GET', '/responses/html');
        expect($response->getBody()->getContents())->toBe('<h1>lorem ipsum</h1>');
        expect($response->getStatusCode())->toBe(200);
    });
    
    it('can be xml', function () use ($client) {
        $response = $client->request('GET', '/responses/xml');
        expect($response->getBody()->getContents())->toBe('<root><lorem>ipsum</lorem></root>');
        expect($response->getStatusCode())->toBe(200);
    });
    
    it('can be json', function () use ($client) {
        $response = $client->request('GET', '/responses/json');
        expect($response->getBody()->getContents())->toBe('{"string":"lorem ipsum","number":123,"boolean":true,"null":null,"array":["lorem","ipsum","dolor","sit","amet"],"object":{"lorem":"ipsum","dolor":"sit","amet":"consectetur"}}');
        expect($response->getStatusCode())->toBe(200);
    });
    
    it('can be a redirect', function () use ($client) {
        $response = $client->request('GET', '/responses/redirect');
        expect($response->getBody()->getContents())->toBe('lorem ipsum');
        expect($response->getStatusCode())->toBe(200);
    });
});
