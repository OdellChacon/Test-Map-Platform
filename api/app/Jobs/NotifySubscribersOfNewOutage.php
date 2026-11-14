<?php

namespace App\Jobs;

use App\Models\OutageSubscription;
use App\Notifications\NewOutageNearYou;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifySubscribersOfNewOutage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $reportId,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $radiusKm,
        public readonly int $reporterId,
    ) {}

    public function handle(): void
    {
        $pointWkt = "SRID=4326;POINT({$this->longitude} {$this->latitude})";

        $subscribers = OutageSubscription::query()
            ->near($pointWkt)
            ->where('user_id', '!=', $this->reporterId)
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id');

        foreach ($subscribers as $subscriber) {
            $subscriber->notify(new NewOutageNearYou(
                $this->reportId,
                $this->latitude,
                $this->longitude,
                $this->radiusKm,
            ));
        }
    }
}
