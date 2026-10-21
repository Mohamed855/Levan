<?php

declare(strict_types=1);
namespace App\Models\Access;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
final class Permission extends Model { protected $fillable=['guard','name','display_name']; public function roles():BelongsToMany{return $this->belongsToMany(Role::class);} }