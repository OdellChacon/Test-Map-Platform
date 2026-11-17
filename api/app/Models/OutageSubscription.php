<?php

namespace App\Models;

use App\Traits\HasGeographicLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutageSubscription extends Model
{
    use HasGeographicLocation;

    protected $fillable = [
        'user_id',
        'radius_km',
    ];

    protected $casts = [
        'radius_km' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNear(Builder $query, string $pointWkt, float $reportRadiusKm = 0): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location, ST_GeogFromText(?), (radius_km + ?) * 1000)',
            [$pointWkt, $reportRadiusKm]
        );
    }
}
