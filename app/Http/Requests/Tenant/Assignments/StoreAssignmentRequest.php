<?php

declare(strict_types=1);
namespace App\Http\Requests\Tenant\Assignments;
use App\Http\Requests\Concerns\WizardFormRequest;
use Illuminate\Validation\Rule;
final class StoreAssignmentRequest extends WizardFormRequest { public function authorize(): bool { return $this->user('tenant') !== null; } public function rules(): array { return ['assignment_number'=>['required','string','max:100',Rule::unique('assignments','assignment_number')->whereNull('deleted_at')],'name'=>['required','string','min:3','max:255'],'issuing_entity'=>['required','string','max:255'],'city'=>['required','string','max:100'],'district'=>['nullable','string','max:255'],'category'=>['required',Rule::in(['lands','villas','commercial_buildings','educational','industrial'])],'start_date'=>['required','date'],'end_date'=>['required','date','after_or_equal:start_date'],'notes'=>['required','string','max:1000'],'status'=>['nullable',Rule::in(['active','under_review','suspended','auctioned'])],'is_active'=>['nullable','boolean'],'property_ids'=>['nullable','array'],'property_ids.*'=>['integer','distinct']]; } public function attributes(): array { return ['assignment_number'=>'رقم السند','name'=>'اسم السند','issuing_entity'=>'الجهة المصدرة','city'=>'المدينة','start_date'=>'تاريخ البداية','end_date'=>'تاريخ النهاية']; } }
