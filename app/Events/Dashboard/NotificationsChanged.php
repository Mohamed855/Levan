<?php

declare(strict_types=1);

namespace App\Events\Dashboard;

use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class NotificationsChanged implements ShouldBroadcastNow
{
    use InteractsWithBroadcasting;

    public function __construct(public readonly string $channel)
    {
        $this->broadcastVia('reverb');
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel($this->channel)];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.notifications.changed';
    }

    public function broadcastWith(): array
    {
        return ['refresh' => true];
    }
}
