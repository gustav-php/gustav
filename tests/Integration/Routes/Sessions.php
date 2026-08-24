<?php

namespace GustavPHP\Tests\Integration\Routes;

use GustavPHP\Gustav\Attribute\{Body, Controller, Csrf, Get, Post};
use GustavPHP\Gustav\Security\CsrfTokenManager;
use GustavPHP\Gustav\Session;
use RuntimeException;

#[Controller('/sessions')]
#[Csrf]
final class Sessions
{
    public function __construct(
        private readonly Session $session,
        private readonly CsrfTokenManager $csrf,
    ) {
    }

    /** @return array{notice:mixed} */
    #[Get('/flash')]
    public function flash(): array
    {
        return ['notice' => $this->session->getFlash('notice')];
    }

    /** @return array{value:mixed} */
    #[Get('/value')]
    public function getValue(): array
    {
        return ['value' => $this->session->get('value')];
    }

    /** @return array{ok:true} */
    #[Post('/invalidate')]
    public function invalidate(): array
    {
        $this->session->invalidate();

        return ['ok' => true];
    }

    /** @return array{old:string,new:string,value:mixed} */
    #[Post('/regenerate')]
    public function regenerate(): array
    {
        $old = $this->session->id();
        $this->session->regenerate();

        return [
            'old' => $old,
            'new' => $this->session->id(),
            'value' => $this->session->get('value'),
        ];
    }

    /** @return array{stored:string} */
    #[Post('/flash')]
    public function setFlash(#[Body('message')] string $message): array
    {
        $this->session->flash('notice', $message);

        return ['stored' => $message];
    }

    /** @return array{value:string,token_present:bool} */
    #[Post('/value')]
    public function setValue(
        #[Body] array $body,
        #[Body('value')] string $value,
    ): array {
        $this->session->put('value', $value);

        return [
            'value' => $value,
            'token_present' => array_key_exists(CsrfTokenManager::FIELD, $body),
        ];
    }

    /** @return array{ok:true} */
    #[Post('/fail')]
    public function storeThenFail(): array
    {
        $this->session->put('value', 'should-not-commit');

        throw new RuntimeException('private session failure');
    }

    /** @return array{token:string,session_id:string} */
    #[Get('/token')]
    public function token(): array
    {
        return [
            'token' => $this->csrf->token(),
            'session_id' => $this->session->id(),
        ];
    }
}
