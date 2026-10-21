<?php

declare(strict_types=1);
namespace App\Http\Requests\Tenant\Assignments;
use Illuminate\Validation\Rule;
final class UpdateAssignmentRequest extends StoreAssignmentRequest { public function rules(): array { $assignment=$this->route('assignment'); return array_merge(parent::rules(), ['assignment_number'=>['required','string','max:100',Rule::unique('assignments','assignment_number')->ignore($assignment->id)->whereNull('deleted_at')]]); } }