<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;
final class FaqRequest extends FormRequest { public function authorize(): bool { return $this->user('admin')?->can('faqs.edit') ?? false; } public function rules(): array { return ['question'=>['required','string','max:255'],'answer'=>['required','string','max:5000'],'is_active'=>['nullable','boolean']]; } public function attributes(): array{return ['question'=>'السؤال','answer'=>'الإجابة'];} }
