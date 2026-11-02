<?php

declare(strict_types=1);

namespace App\Http\Requests\Shared\Wizard;

use App\Services\Shared\Wizard\WizardDraftUploads;

class StoreWizardDraftRequest extends AuthenticatedWizardDraftRequest
{
    public function rules(): array
    {
        return [
            'resource_key' => ['required', 'string', 'max:255'],
            'step' => ['required', 'integer', 'min:1', 'max:100'],
            'target_path' => ['nullable', 'string', 'max:2048'],
        ] + app(WizardDraftUploads::class)->rules($this);
    }

    public function attributes(): array
    {
        $uploads = match ($this->input('resource_key')) {
            'admin.user-create', 'tenant.user-create' => ['image' => 'الصورة الشخصية'],
            'admin.bank-create' => ['icon' => 'أيقونة البنك'],
            'admin.subscription-create' => ['receipt_upload' => 'إيصال الدفع / الحوالة (مستند)', 'payment_proof' => 'إثبات الدفع'],
            'tenant.assignment-create' => [
                'assignment_image' => 'صورة الإسناد', 'assignment_letter' => 'خطاب الإسناد',
                'general_documents' => 'مستندات عامة', 'general_documents.*' => 'مستندات عامة',
                'additional_attachments' => 'مرفقات إضافية', 'additional_attachments.*' => 'مرفقات إضافية',
            ],
            'tenant.property-create' => [
                'main_image' => 'الصورة الرئيسية للعقار', 'deed_document' => 'صك الملكية', 'cadastral_plan' => 'مخطط العقار',
                'coordinates_statement' => 'كشف الإحداثيات', 'building_permit' => 'رخصة البناء', 'assignment_letter_image' => 'صورة الإسناد',
                'property_images' => 'صور إضافية للمعرض', 'property_images.*' => 'صور إضافية للمعرض',
                'additional_documents' => 'مستندات إضافية', 'additional_documents.*' => 'مستندات إضافية',
            ],
            'tenant.auction-create' => [
                'report_image' => 'صورة غلاف التقرير', 'cover_image' => 'صورة غلاف المزاد',
                'attachments' => 'ملفات إضافية', 'attachments.*' => 'ملفات إضافية',
                'marketing_evidence' => 'رفع أدلة ومستندات التسويق (لقطات شاشة أو ملفات PDF)',
                'marketing_evidence.*' => 'رفع أدلة ومستندات التسويق (لقطات شاشة أو ملفات PDF)',
                'board_images_before.*.*' => 'إثبات تركيب اللوحة', 'board_images_after.*.*' => 'إثبات فك اللوحة',
            ],
            'tenant.ticket-create' => ['attachments' => 'المرفقات', 'attachments.*' => 'المرفقات'],
            default => [],
        };

        return [...parent::attributes(), ...$uploads];
    }
}
