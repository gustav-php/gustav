<?php

namespace GustavPHP\Gustav\Middleware;

enum Lifecycle: string
{
    case After = 'after';
    case Before = 'before';
    case Error = 'error';
}
