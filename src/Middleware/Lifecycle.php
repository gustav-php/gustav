<?php

namespace GustavPHP\Gustav\Middleware;

enum Lifecycle
{
    case After;
    case Before;
    case Error;
}
