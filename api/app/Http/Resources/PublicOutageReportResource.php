<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicOutageReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'radius_km' => (float) $this->radius_km,
            'duration_type' => $this->duration_type->value,
            'description' => $this->description,
            'address' => $this->address,
            'confirmations_count' => $this->confirmations_count ?? $this->confirmations()->count(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
