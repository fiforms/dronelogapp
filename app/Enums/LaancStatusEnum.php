<?php

namespace App\Enums;

enum LaancStatusEnum: string
{
    case Received = 'received';
    case NotNeeded = 'not_needed';
    case Na = 'na';
}
