<?php

namespace GustavPHP\Tests\Integration\DTO;

use GustavPHP\Gustav\Attribute\Serializer\Exclude;

final readonly class DogOutput
{
    /**
     * @param list<OwnerOutput> $watchers
     * @param list<string|int|bool> $labels
     */
    public function __construct(
        public int $id,
        public string $name,
        public ResponseState $state,
        public ?string $nickname,
        public OwnerOutput $owner,
        public array $watchers,
        public array $labels,
        public float $rating,
        #[Exclude]
        public string $internalNote,
    ) {
    }
}
