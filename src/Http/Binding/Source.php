<?php

namespace GustavPHP\Gustav\Http\Binding;

enum Source: string
{
    case AuthUser = 'auth_user';
    case Body = 'body';
    case Cookie = 'cookie';
    case Header = 'header';
    case Param = 'param';
    case Query = 'query';
    case Request = 'request';
}
