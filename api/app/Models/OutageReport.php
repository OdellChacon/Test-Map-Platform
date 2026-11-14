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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Active)
            ->where('expires_at', '>', now());
    }

    public function scopeWithinBoundingBox(Builder $query, float $southWestLat, float $southWestLng, float $northEastLat, float $northEastLng): Builder
    {
        return $query->whereRaw(
            'location && ST_MakeEnvelope(?, ?, ?, ?, 4326)::geography',
            [$southWestLng, $southWestLat, $northEastLng, $northEastLat]
        );
    }
}
