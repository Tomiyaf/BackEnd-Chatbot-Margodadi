<?php

namespace App\Enums;

enum SenderType: string
{
    case USER = 'USER';
    case BOT = 'BOT';
    case OPERATOR = 'OPERATOR';
    case SYSTEM = 'SYSTEM';
}
