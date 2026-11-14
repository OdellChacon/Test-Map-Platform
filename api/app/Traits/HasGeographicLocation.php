<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasGeographicLocation
{
    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->addSelect([
            '*',
            DB::raw('ST_Y(location::geometry) as latitude'),
            DB::raw('ST_X(location::geometry) as longitude'),
        ]);
    }

    public function setCoordinates(float $latitude, float $longitude): void
    {
        DB::statement(
            'UPDATE '.$this->getTable().' SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326) WHERE id = ?',
            [$longitude, $latitude, $this->id]
        );
    }

    public static function createWithCoordinates(array $attributes, float $latitude, float $longitude): self
    {
        return DB::transaction(function () use ($attributes, $latitude, $longitude) {
            $model = static::query()->create($attributes);
            $model->setCoordinates($latitude, $longitude);

            return $model->refresh();
        });
    }
}
