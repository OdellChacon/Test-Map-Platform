<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutageReportConfirmation extends Model
{
    protected $fillable = [
        'outage_report_id',
        'user_id',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(OutageReport::class, 'outage_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
