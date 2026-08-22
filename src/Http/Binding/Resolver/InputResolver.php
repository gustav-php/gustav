<?php

namespace GustavPHP\Gustav\Http\Binding\Resolver;

use GustavPHP\Gustav\Http\Binding\{BindingContext, Resolution, Source};

interface InputResolver
{
    public function getPath(): string;

    public function getSource(): Source;

    public function resolve(BindingContext $context): Resolution;
}
