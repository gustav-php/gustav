<?php

namespace GustavPHP\Tests\Integration\DTO;

enum ResponseState: string
{
    case Active = 'active';
    case Archived = 'archived';
}
