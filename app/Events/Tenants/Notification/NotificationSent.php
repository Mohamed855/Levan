<?php

namespace App\Events\Tenants\Notification;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    public $userId;

    public function __construct($notification, $userId)
    {
        $this->notification = $notification;
        $this->userId = $userId;

        Log::info('NotificationSent event created', [
            'notification_id' => $notification->id,
            'user_id' => $userId,
            'channel' => 'notifications.'.$userId,
        ]);
    }

    public function broadcastOn()
    {
        $channel = new PrivateChannel('notifications.'.$this->userId);

        // Log::info('Broadcasting on channel', [
        //     'channel_name' => $channel->name
        // ]);

        return $channel;
    }

    public function broadcastAs()
    {
        return 'notification.created';
    }

    public function broadcastWith()
    {
        $data = [
            'id' => $this->notification->id,
            'title' => $this->notification->data['title'] ?? '',
            'message' => $this->notification->data['message'] ?? '',
            'type' => $this->notification->data['type'] ?? 'info',
            'icon' => $this->notification->data['icon'] ?? 'ti-bell',
            'created_at' => $this->notification->created_at->diffForHumans(),
        ];

        // Log::info('Broadcasting notification data', $data);

        return $data;
    }
}
