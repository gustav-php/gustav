<?php

namespace GustavPHP\Tests\Integration\DTO;

enum InputStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
