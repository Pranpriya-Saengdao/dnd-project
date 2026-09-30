<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerMoved implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $playerId,
        public int $x,
        public int $y
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('game'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'player.moved';
    }
}
