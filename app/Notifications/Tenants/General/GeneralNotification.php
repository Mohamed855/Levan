<?php

namespace App\Notifications\Tenants\General;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $message;
    protected $type;
    protected $icon;
    protected $url;

    public function __construct($title, $message, $type = 'info', $icon = 'ti-bell', $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->icon = $icon;
        $this->url = $url;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'icon' => $this->icon,
            'url' => $this->url,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id ?? uniqid(),
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'icon' => $this->icon,
            'url' => $this->url,
            'created_at' => now()->diffForHumans(),
        ]);
    }

    public function broadcastType()
    {
        return 'notification.created';
    }
}