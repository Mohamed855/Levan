<?php

declare(strict_types=1);
namespace App\Actions\Tenant\Properties;
use App\Models\Tenant\Property;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
final class SavePropertyAction { /** @param array<string,mixed> $data */ public function create(User $user,array $data): Property { return $this->save(new Property(),$user,$data); } /** @param array<string,mixed> $data */ public function update(Property $property,User $user,array $data): Property { return $this->save($property,$user,$data); } /** @param array<string,mixed> $data */ private function save(Property $property,User $user,array $data): Property { return DB::transaction(function()use($property,$user,$data):Property{ $boundaries=$data['boundaries'];$owners=$data['owners'];$units=$data['units']??[];unset($data['boundaries'],$data['owners'],$data['units']);$property->fill($data+['created_by'=>$property->created_by ?: $user->id]);$property->save();$property->boundaries()->delete();$property->owners()->delete();$property->units()->delete();foreach($boundaries as $i=>$row)$property->boundaries()->create($row+['sort_order'=>$i]);foreach($owners as $i=>$row)$property->owners()->create($row+['sort_order'=>$i]);foreach($units as $i=>$row)$property->units()->create($row+['sort_order'=>$i]);return $property;});} }