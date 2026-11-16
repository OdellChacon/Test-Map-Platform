<?php

namespace App\Models;

use App\Enums\ReportDurationType;
use App\Enums\ReportStatus;
use App\Traits\HasGeographicLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutageReport extends Model
{
    use HasFactory;
    use HasGeographicLocation;

    protected $fillable = [
        'user_id',
        'radius_km',
        'duration_type',
        'duration_hours',
        'expires_at',
        'closed_at',
        'status',
        'description',
        'address',
    ];

    protected $casts = [
        'radius_km' => 'decimal:2',
        'duration_type' => ReportDurationType::class,
        'duration_hours' => 'integer',
        'expires_at' => 'datetime',
        'closed_at' => 'datetime',
        'status' => ReportStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(OutageReportConfirmation::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getEffectiveStatusAttribute(): ReportStatus
    {
        if ($this->status === ReportStatus::Active && $this->isExpired()) {
            return ReportStatus::Expired;
        }

        return $this->status;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Active)
            ->where('expires_at', '>', now());
    }

    public function scopeWithinBoundingBox(Builder $query, float $southWestLat, float $southWestLng, float $northEastLat, float $northEastLng): Builder
    {
        $maxRadiusKm = (float) config('outage.max_report_radius_km');
        $midLat = ($southWestLat + $northEastLat) / 2;

        $latMarginDegrees = $maxRadiusKm / 111.0;
        $lngMarginDegrees = $maxRadiusKm / (111.0 * max(cos(deg2rad($midLat)), 0.01));

        return $query->whereRaw(
            'location && ST_MakeEnvelope(?, ?, ?, ?, 4326)::geography',
            [
                $southWestLng - $lngMarginDegrees,
                max($southWestLat - $latMarginDegrees, -90),
                $northEastLng + $lngMarginDegrees,
                min($northEastLat + $latMarginDegrees, 90),
            ]
        );
    }
}
