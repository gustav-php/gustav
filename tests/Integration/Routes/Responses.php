<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\Route;
use GustavPHP\Gustav\Controller;
use GustavPHP\Tests\Integration\DTO\{CircularOutput, DogOutput, OwnerOutput, ResponseState, UninitializedOutput, UnsupportedOutput};
use GustavPHP\Tests\Integration\Serializers\LegacyOutput;

class Responses extends Controller\Base
{
    #[Route('/responses/circular')]
    public function circular(): Controller\Response
    {
        $output = new CircularOutput('root');
        $output->next = $output;

        return $this->json($output);
    }

    #[Route('/responses/direct-collection')]
    public function directCollection(): array
    {
        return [
            'dogs' => [$this->dog(1), $this->dog(2)],
            'state' => ResponseState::Active,
            'empty' => null,
        ];
    }

    #[Route('/responses/direct-dto')]
    public function directDto(): DogOutput
    {
        return $this->dog(1);
    }

    #[Route('/responses/direct-enum')]
    public function directEnum(): ResponseState
    {
        return ResponseState::Active;
    }

    #[Route('/responses/direct-false')]
    public function directFalse(): bool
    {
        return false;
    }

    #[Route('/responses/direct-null')]
    public function directNull(): ?DogOutput
    {
        return null;
    }

    #[Route('/responses/dto-helper')]
    public function dtoHelper(): Controller\Response
    {
        return $this->json($this->dog(1));
    }

    #[Route('/responses/dto-helper-created')]
    public function dtoHelperCreated(): Controller\Response
    {
        return $this->json(
            $this->dog(1),
            status: 201,
            headers: ['X-Response-Mode' => 'explicit'],
        );
    }

    #[Route('/responses/legacy-serializer')]
    public function legacySerializer(): Controller\Response
    {
        $output = new LegacyOutput();
        $output->extra = 'included';

        return $this->serialize($output);
    }

    #[Route('/responses/html')]
    public function returnHtml(): Controller\Response
    {
        return $this->html('<h1>lorem ipsum</h1>');
    }

    #[Route('/responses/json')]
    public function returnJson(): Controller\Response
    {
        return $this->json([
            'string' => 'lorem ipsum',
            'number' => 123,
            'boolean' => true,
            'null' => null,
            'array' => [
                'lorem',
                'ipsum',
                'dolor',
                'sit',
                'amet'
            ],
            'object' => [
                'lorem' => 'ipsum',
                'dolor' => 'sit',
                'amet' => 'consectetur'
            ]
        ]);
    }

    #[Route('/responses/plaintext')]
    public function returnPlaintext(): Controller\Response
    {
        return $this->plaintext('lorem ipsum');
    }

    #[Route('/responses/redirect')]
    public function returnRedirect(): Controller\Response
    {
        return $this->redirect('/responses/plaintext', 301);
    }

    #[Route('/responses/xml')]
    public function returnXml(): Controller\Response
    {
        return $this->xml('<root><lorem>ipsum</lorem></root>');
    }

    #[Route('/responses/uninitialized')]
    public function uninitialized(): UninitializedOutput
    {
        return new UninitializedOutput();
    }

    #[Route('/responses/unsupported')]
    public function unsupported(): Controller\Response
    {
        return $this->json(new UnsupportedOutput());
    }

    private function dog(int $id): DogOutput
    {
        return new DogOutput(
            id: $id,
            name: "Dog {$id}",
            state: ResponseState::Active,
            nickname: null,
            owner: new OwnerOutput('Ada', 'owner-secret'),
            watchers: [
                new OwnerOutput('Grace', 'watcher-secret'),
                new OwnerOutput('Linus', 'watcher-secret'),
            ],
            labels: ['friendly', 0, false],
            rating: 1.0,
            internalNote: 'do not expose',
        );
    }
}
