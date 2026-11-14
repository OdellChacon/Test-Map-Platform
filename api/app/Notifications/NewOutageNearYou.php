<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOutageNearYou extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $reportId,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $radiusKm,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->reportId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_km' => $this->radiusKm,
            'message' => 'A new outage was reported in an area you follow.',
        ];
    }
}
