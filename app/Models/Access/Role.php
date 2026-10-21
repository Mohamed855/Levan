<?php

declare(strict_types=1);
namespace App\Models\Access;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
final class Role extends Model { protected $fillable=['guard','name','display_name']; public function permissions():BelongsToMany{return $this->belongsToMany(Permission::class);} }