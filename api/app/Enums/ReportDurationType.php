<?php

namespace App\Enums;

enum ReportDurationType: string
{
    case Fixed = 'fixed';
    case UntilClosed = 'until_closed';
}
