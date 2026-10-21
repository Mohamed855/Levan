<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Auction extends Model
{
    use SoftDeletes;
    protected $fillable = ['assignment_id','name','description','start_date','start_time','end_date','end_time','type','category','city','expected_properties_count','supervising_entity','platform_name','platform_url','notes','cover_image','template_versions','template_snapshot','template_bindings','status','is_active','created_by'];
    protected function casts(): array { return ['start_date'=>'date','end_date'=>'date','expected_properties_count'=>'integer','is_active'=>'boolean','template_versions'=>'array','template_snapshot'=>'array','template_bindings'=>'array']; }
    public function assignment(): BelongsTo { return $this->belongsTo(Assignment::class); }
    public function properties(): BelongsToMany { return $this->belongsToMany(Property::class, 'auction_properties')->withPivot('sort_order')->withTimestamps(); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}