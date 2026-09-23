<?php

namespace App\Http\Requests\Tenants\BusinessCenter\Customer\Attachment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أو تحديد الصلاحيات المناسبة
    }

    public function rules(): array
    {
        return [
            'attachments' => 'required|array|min:1|max:10', // حد أقصى 10 مرفقات
            'attachments.*.description' => 'nullable|string|max:1000', // زيادة الحد الأقصى
            'attachments.*.file' => [
                'required',
                'file',
                'max:20480', // 20MB بدلاً من 10MB
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip,rar,txt,ppt,pptx'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            // رسائل المصفوفة الرئيسية
            'attachments.required' => 'يجب إضافة مرفق واحد على الأقل',
            'attachments.array' => 'تنسيق المرفقات غير صحيح',
            'attachments.min' => 'يجب إضافة مرفق واحد على الأقل',
            'attachments.max' => 'لا يمكن إضافة أكثر من :max مرفقات في المرة الواحدة',

            // رسائل الوصف
            'attachments.*.description.string' => 'وصف المرفق يجب أن يكون نص',
            'attachments.*.description.max' => 'وصف المرفق لا يجب أن يتجاوز :max حرف',

            // رسائل الملف
            'attachments.*.file.required' => 'المرفق مطلوب',
            'attachments.*.file.file' => 'يجب أن يكون المرفق ملف صحيح',
            'attachments.*.file.max' => 'حجم المرفق لا يجب أن يتجاوز :max كيلوبايت (20 ميجابايت)',
            'attachments.*.file.mimes' => 'نوع المرفق غير مدعوم. الأنواع المدعومة: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP, RAR, TXT',
        ];
    }

    public function attributes(): array
    {
        return [
            'attachments' => 'المرفقات',
            'attachments.*.description' => 'وصف المرفق',
            'attachments.*.file' => 'المرفق',
        ];
    }

    /**
     * الحصول على رسائل خطأ مخصصة
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $attachments = $this->input('attachments', []);

            foreach ($attachments as $index => $attachment) {
                if (isset($attachment['file'])) {
                    $file = $attachment['file'];

                    // التحقق من نوع MIME إضافي
                    if ($file && method_exists($file, 'getMimeType')) {
                        $allowedMimes = [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif',
                            'application/zip',
                            'application/x-rar-compressed',
                            'text/plain'
                        ];

                        if (!in_array($file->getMimeType(), $allowedMimes)) {
                            $validator->errors()->add(
                                "attachments.{$index}.file",
                                'نوع الملف غير مدعوم'
                            );
                        }
                    }
                }
            }
        });
    }
}
