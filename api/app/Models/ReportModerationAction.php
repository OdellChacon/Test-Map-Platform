<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportModerationAction extends Model
{
    protected $fillable = [
        'outage_report_id',
        'moderator_id',
        'action',
        'reason',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(OutageReport::class, 'outage_report_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
