<?php
declare(strict_types=1);
namespace App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
final class ControlDomain extends Model { protected $fillable=['domain','is_active']; protected function casts():array{return ['is_active'=>'boolean'];} }
