<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $message,
        public string $sender = 'Player',
        public string $type = 'chat',
        public ?string $team = null,
        public ?string $icon = null,
        public ?string $time = null,
        public ?int $roomId = null,
    ) {
        $this->time = $this->time ?? date('H:i');
    }

    public function broadcastOn(): array
    {
        if ($this->roomId) {
            return [
                new Channel("room.{$this->roomId}"),
            ];
        }

        return [
            new Channel('chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }
}
