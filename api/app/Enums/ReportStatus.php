<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Expired = 'expired';
}
