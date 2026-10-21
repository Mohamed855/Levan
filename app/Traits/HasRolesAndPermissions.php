<?php

declare(strict_types=1);
namespace App\Traits;
use App\Models\Access\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
trait HasRolesAndPermissions { public function roles():BelongsToMany{return $this->belongsToMany(Role::class,'role_user','user_id','role_id')->wherePivot('guard',$this->permissionGuard());} public function permissionGuard():string{return $this->getTable()==='admin_users'?'admin':'tenant';} public function assignRole(Role $role): void { abort_unless($role->guard === $this->permissionGuard(), 422); $this->roles()->syncWithoutDetaching([$role->id => ['guard'=>$this->permissionGuard()]]); } public function hasPermissionTo(string $permission):bool{return $this->roles()->whereHas('permissions',fn($q)=>$q->where('name',$permission)->where('guard',$this->permissionGuard()))->exists();} }
