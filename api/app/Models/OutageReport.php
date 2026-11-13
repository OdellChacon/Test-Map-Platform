<?php

namespace App\Models;

use App\Enums\ReportDurationType;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class OutageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'radius_km',
        'duration_type',
        'duration_hours',
        'expires_at',
        'closed_at',
        'status',
        'description',
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

    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->addSelect([
            '*',
            DB::raw('ST_Y(location::geometry) as latitude'),
            DB::raw('ST_X(location::geometry) as longitude'),
        ]);
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

    public function setCoordinates(float $latitude, float $longitude): void
    {
        DB::statement(
            'UPDATE outage_reports SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$longitude, $latitude, $this->id]
        );
    }

    public static function createWithCoordinates(array $attributes, float $latitude, float $longitude): self
    {
        return DB::transaction(function () use ($attributes, $latitude, $longitude) {
            $report = static::query()->create($attributes);
            $report->setCoordinates($latitude, $longitude);

            return $report->refresh();
        });
    }
}
