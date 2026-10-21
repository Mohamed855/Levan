<?php
declare(strict_types=1);
namespace App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
final class Coupon extends Model { use SoftDeletes; protected $fillable=['name','code','discount_type','discount_value','max_discount','expires_at','usage_limit','used_count','is_active','created_by']; protected function casts():array{return ['discount_value'=>'decimal:2','max_discount'=>'decimal:2','expires_at'=>'datetime','is_active'=>'boolean'];} }
