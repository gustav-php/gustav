<?php

namespace GustavPHP\Gustav\Service;

enum Lifetime
{
    case Scoped;
    case Singleton;
    case Transient;
}
