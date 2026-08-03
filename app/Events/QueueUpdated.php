<?php

namespace App\Events;

use App\Models\QueueEntry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QueueUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('queue');
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    public static function snapshot(): self
    {
        $current = QueueEntry::current();
        $entries = QueueEntry::orderBy('position')->get()
            ->map(fn (QueueEntry $entry) => [
                'id' => $entry->id,
                'code' => $entry->code,
                'position' => $entry->position,
                'status' => $entry->status,
            ])
            ->values()
            ->toArray();

        return new self([
            'current' => $current?->code,
            'entries' => $entries,
        ]);
    }
}
