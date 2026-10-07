<?php

namespace App\Enums;

enum ResearchSessionStatus: string
{
    case STARTED = 'STARTED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case ABANDONED = 'ABANDONED';
}
