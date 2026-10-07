<?php

namespace App\Enums;

enum HitlEventType: string
{
    case NEED_HUMAN = 'NEED_HUMAN';
    case OPERATOR_ASSIGNED = 'OPERATOR_ASSIGNED';
    case OPERATOR_RESPONSE = 'OPERATOR_RESPONSE';
    case TAKE_OVER = 'TAKE_OVER';
    case STATUS_CHANGE = 'STATUS_CHANGE';
    case RESOLVED = 'RESOLVED';
}
