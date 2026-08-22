<?php

use GustavPHP\Gustav\Attribute\Serializer\Exclude;
use GustavPHP\Gustav\Serializer\{Manager, SerializationException};
use GustavPHP\Tests\Fixtures\UnbackedResponseState;

it('normalizes DTOs, mixed collections, and float precision', function () {
    $dto = new class ('Ada', 'secret') {
        public function __construct(
            public readonly string $name,
            #[Exclude]
            public readonly string $secret,
        ) {
        }
    };

    expect(Manager::encode([
        'dto' => $dto,
        'values' => ['kept', 0, false, null],
        'decimal' => 1.0,
    ]))->toBe('{"dto":{"name":"Ada"},"values":["kept",0,false,null],"decimal":1.0}');
});

it('preserves JSON object shape for empty DTOs', function () {
    expect(Manager::encode(new class () {}))->toBe('{}');
});

it('honors JsonSerializable and recursively normalizes its result', function () {
    $value = new class () implements JsonSerializable {
        public function jsonSerialize(): mixed
        {
            return ['nested' => new class ('value') {
                public function __construct(public readonly string $name)
                {
                }
            }];
        }
    };

    expect(Manager::encode($value))->toBe('{"nested":{"name":"value"}}');
});

it('substitutes invalid UTF-8 instead of emitting an invalid response', function () {
    expect(Manager::encode(['invalid' => "\xB1"]))->toBe('{"invalid":"\\ufffd"}');
});

it('rejects closures', function () {
    Manager::encode(fn (): null => null);
})->throws(SerializationException::class);

it('rejects unsupported JSON values', function (mixed $value) {
    Manager::encode($value);
})->with([
    'non-finite float' => [INF],
    'unbacked enum' => [UnbackedResponseState::Ready],
])->throws(SerializationException::class);

it('rejects self-referencing arrays at the serialization depth limit', function () {
    $value = [];
    $value['self'] = &$value;

    Manager::encode($value);
})->throws(SerializationException::class, 'Maximum JSON serialization depth exceeded');
