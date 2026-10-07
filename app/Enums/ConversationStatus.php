<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case OPEN = 'OPEN';
    case PENDING = 'PENDING';
    case ASSIGNED = 'ASSIGNED';
    case RESOLVED = 'RESOLVED';
}
