<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Attachment;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Attachment\CustomerAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomerAttachmentService
{

    /*
    |--------------------------------------------------------------------------
    | إنشاء مرفقات متعددة للعميل
    |--------------------------------------------------------------------------
    */
    public function createMultipleAttachments(Customer $customer, array $attachmentsData): array
    {
        $createdAttachments = [];

        foreach ($attachmentsData as $attachmentData) {
            if (isset($attachmentData['file']) && $attachmentData['file'] instanceof UploadedFile) {
                $createdAttachments[] = $this->createSingleAttachment($customer, $attachmentData);
            }
        }

        return $createdAttachments;
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء مرفق واحد
    |--------------------------------------------------------------------------
    */
    public function createSingleAttachment(Customer $customer, array $data): CustomerAttachment
    {
        $file = $data['file'];
        $filePath = $this->storeFile($file, $customer->id);

        return CustomerAttachment::create([
            'customer_id' => $customer->id,
            'created_by' => Auth::id(),
            'description' => $data['description'] ?? null,
            'file_path' => $filePath,
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'file_size' => $file->getSize(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | حذف مرفق (حذف ناعم)
    |--------------------------------------------------------------------------
    */
    public function deleteAttachment(CustomerAttachment $attachment): bool
    {
        return $attachment->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | الحذف النهائي للمرفق
    |--------------------------------------------------------------------------
    */
    public function forceDeleteAttachment(CustomerAttachment $attachment): bool
    {
        if (Storage::exists($attachment->file_path)) {
            Storage::delete($attachment->file_path);
        }

        return $attachment->forceDelete();
    }

    /*
    |--------------------------------------------------------------------------
    | جلب مرفقات العميل (مبسط للنظام الجديد)
    |--------------------------------------------------------------------------
    */
    public function getCustomerAttachments(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerAttachment::where('customer_id', $customerId)
            ->with(['createdBy:id,name'])
            ->latest()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | تخزين الملف مع تحسينات
    |--------------------------------------------------------------------------
    */
    private function storeFile(UploadedFile $file, int $customerId): string
    {
        $directory = "customer_attachments/{$customerId}";
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $cleanName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $originalName);
        $cleanName = trim($cleanName);
        if (empty($cleanName)) {
            $cleanName = 'file';
        }

        $filename = $cleanName . '_' . time() . '_' . Str::random(6) . '.' . $extension;

        $path = $file->storeAs($directory, $filename, 'tenant_public');

        if (!Storage::disk('tenant_public')->exists($path)) {
            throw new \Exception('فشل في حفظ الملف');
        }

        return $path;
    }

    /*
    |--------------------------------------------------------------------------
    | جلب أنواع الملفات المدعومة
    |--------------------------------------------------------------------------
    */
    public function getSupportedFileTypes(): array
    {
        return [
            'pdf' => 'PDF',
            'doc' => 'Word Document',
            'docx' => 'Word Document',
            'xls' => 'Excel',
            'xlsx' => 'Excel',
            'jpg' => 'صورة',
            'jpeg' => 'صورة',
            'png' => 'صورة',
            'gif' => 'صورة',
            'zip' => 'ملف مضغوط',
            'rar' => 'ملف مضغوط',
            'txt' => 'ملف نصي',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من نوع الملف
    |--------------------------------------------------------------------------
    */
    public function isValidFileType(string $fileType): bool
    {
        return array_key_exists(strtolower($fileType), $this->getSupportedFileTypes());
    }

    /*
    |--------------------------------------------------------------------------
    | تنسيق حجم الملف
    |--------------------------------------------------------------------------
    */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | إحصائيات مبسطة للمرفقات
    |--------------------------------------------------------------------------
    */
    public function getSimpleStatistics(int $customerId): array
    {
        $attachments = CustomerAttachment::where('customer_id', $customerId);

        return [
            'total_count' => $attachments->count(),
            'total_size' => $this->formatFileSize($attachments->sum('file_size')),
            'active_count' => $attachments->whereNull('deleted_at')->count(),
            'deleted_count' => $attachments->whereNotNull('deleted_at')->count(),
        ];
    }
}
