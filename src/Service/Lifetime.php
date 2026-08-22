<?php

namespace GustavPHP\Gustav\Service;

enum Lifetime
{
    case Request;
    case Singleton;
    case Transient;
}
