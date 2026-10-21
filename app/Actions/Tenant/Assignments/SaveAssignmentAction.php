<?php

declare(strict_types=1);
namespace App\Actions\Tenant\Assignments;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class SaveAssignmentAction { /** @param array<string,mixed> $data */ public function create(User $user,array $data): Assignment { return $this->save(new Assignment(),$user,$data); } /** @param array<string,mixed> $data */ public function update(Assignment $assignment,User $user,array $data): Assignment { return $this->save($assignment,$user,$data); } /** @param array<string,mixed> $data */ private function save(Assignment $assignment,User $user,array $data): Assignment { return DB::transaction(function() use($assignment,$user,$data): Assignment { $ids=array_values(array_unique(array_map('intval',(array)($data['property_ids']??[])))); if($ids!==[] && Property::query()->whereKey($ids)->whereNotNull('assignment_id')->where('assignment_id','!=',$assignment->id ?: 0)->exists()) throw ValidationException::withMessages(['property_ids'=>'بعض العقارات مرتبطة بسند آخر.']); unset($data['property_ids']); $assignment->fill($data+['created_by'=>$assignment->created_by ?: $user->id]); $assignment->save(); if($ids!==[]) Property::query()->whereKey($ids)->update(['assignment_id'=>$assignment->id]); return $assignment; }); } }
