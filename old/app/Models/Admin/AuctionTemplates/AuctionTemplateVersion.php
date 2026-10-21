<?php

declare(strict_types=1);

namespace App\Models\Admin\AuctionTemplates;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionTemplateVersion extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'auction_template_versions';

    protected $fillable = [
        'auction_template_id',
        'version',
        'schema',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'published_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AuctionTemplate::class, 'auction_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }
}
