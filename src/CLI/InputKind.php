<?php

namespace GustavPHP\Gustav\CLI;

enum InputKind: string
{
    case Argument = 'argument';
    case Option = 'option';
}
