<?php

namespace GustavPHP\Gustav\Session;

enum SameSite: string
{
    case Lax = 'Lax';
    case None = 'None';
    case Strict = 'Strict';
}
