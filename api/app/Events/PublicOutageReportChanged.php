<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublicOutageReportChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $action,
        public readonly int $reportId,
        public readonly array $report = [],
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('outages');
    }

    public function broadcastAs(): string
    {
        return 'report.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'id' => $this->reportId,
            'report' => $this->report,
        ];
    }
}
