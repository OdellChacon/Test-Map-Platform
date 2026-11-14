<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class OutageSubscription extends Model
{
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

    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->addSelect([
            '*',
            DB::raw('ST_Y(location::geometry) as latitude'),
            DB::raw('ST_X(location::geometry) as longitude'),
        ]);
    }

    public function scopeNear(Builder $query, string $pointWkt): Builder
    {
        return $query->whereRaw(
            'ST_DWithin(location, ST_GeogFromText(?), radius_km * 1000)',
            [$pointWkt]
        );
    }

    public function setCoordinates(float $latitude, float $longitude): void
    {
        DB::statement(
            'UPDATE outage_subscriptions SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$longitude, $latitude, $this->id]
        );
    }

    public static function createWithCoordinates(array $attributes, float $latitude, float $longitude): self
    {
        return DB::transaction(function () use ($attributes, $latitude, $longitude) {
            $subscription = static::query()->create($attributes);
            $subscription->setCoordinates($latitude, $longitude);

            return $subscription->refresh();
        });
    }
}
