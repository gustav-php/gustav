<?php

namespace GustavPHP\Tests\Fixtures\Services;

class ProviderImplementation implements ProviderContract
{
    public function value(): string
    {
        return 'provider';
    }
}
