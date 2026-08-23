<?php

namespace GustavPHP\Tests\EventFixtures\ValidApplication\Events;

interface RecordableEvent
{
    public function record(string $listener, int $listenerId, int $dependencyId): void;
}
