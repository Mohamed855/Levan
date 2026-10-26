<?php

declare(strict_types=1);

namespace App\Models\Admin\Notifications;

use Illuminate\Database\Eloquent\Model;

class NotificationDelivery extends Model
{
    protected $connection = 'mysql';

    protected $table = 'dashboard_notification_deliveries';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['payload' => 'array', 'delivered_at' => 'datetime'];
}
