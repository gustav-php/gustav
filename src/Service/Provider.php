<?php

namespace GustavPHP\Gustav\Service;

interface Provider
{
    public function register(Container $services): void;
}
