<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Events;

final class RecordedEvent implements RecordableEvent
{
    /** @var list<array{listener:string,listenerId:int,dependencyId:int}> */
    public array $records = [];

    public function record(string $listener, int $listenerId, int $dependencyId): void
    {
        $this->records[] = [
            'listener' => $listener,
            'listenerId' => $listenerId,
            'dependencyId' => $dependencyId,
        ];
    }
}
