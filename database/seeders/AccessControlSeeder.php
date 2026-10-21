<?php

declare(strict_types=1);
namespace Database\Seeders;
use App\Models\Access\Permission;
use App\Models\Access\Role;
use Illuminate\Database\Seeder;
final class AccessControlSeeder extends Seeder { public function run():void { foreach(['admin'=>['auction_templates.view','auction_templates.create','auction_templates.edit','auction_templates.publish'],'tenant'=>['assignments.view','assignments.create','assignments.edit','properties.view','properties.create','properties.edit','auctions.view','auctions.create','auctions.edit']] as $guard=>$permissions){$role=Role::query()->firstOrCreate(['guard'=>$guard,'name'=>'manager'],['display_name'=>$guard==='admin'?'مدير المنصة':'مدير المستأجر']);foreach($permissions as $name){$permission=Permission::query()->firstOrCreate(['guard'=>$guard,'name'=>$name],['display_name'=>$name]);$role->permissions()->syncWithoutDetaching([$permission->id]);}} } }