<?php

namespace GustavPHP\Gustav\Middleware;

enum Lifecycle
{
    case Before;
    case After;
    case Error;
}