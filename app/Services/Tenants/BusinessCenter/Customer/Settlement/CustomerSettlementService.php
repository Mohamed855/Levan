<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Settlement;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Settlement\CustomerSettlement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomerSettlementService
{
    /**
     * إنشاء تسويات متعددة للعميل
     */
    public function createMultipleSettlements(Customer $customer, array $settlementsData): array
    {
        $createdSettlements = [];

        foreach ($settlementsData as $settlementData) {
            if (!empty($settlementData['settlement_date']) && !empty($settlementData['settlement_amount'])) {
                $createdSettlements[] = $this->createSingleSettlement($customer, $settlementData);
            }
        }

        return $createdSettlements;
    }

    /**
     * إنشاء تسوية واحدة
     */
    public function createSingleSettlement(Customer $customer, array $data): CustomerSettlement
    {
        $filePath = null;
        $fileType = null;
        $fileSize = null;

        // رفع الملف إذا وُجد
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $fileData = $this->storeFile($data['file'], $customer->id);
            $filePath = $fileData['path'];
            $fileType = $fileData['type'];
            $fileSize = $fileData['size'];
        }

        return CustomerSettlement::create([
            'customer_id' => $customer->id,
            'created_by' => Auth::id(),
            'settlement_date' => $data['settlement_date'],
            'settlement_amount' => $data['settlement_amount'],
            'settlement_number' => $data['settlement_number'],
            'settlement_type' => $data['settlement_type'],
            'file_path' => $filePath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
        ]);
    }

    /**
     * تحديث تسوية
     */
    public function updateSettlement(CustomerSettlement $settlement, array $data): CustomerSettlement
    {
        // تحديث البيانات الأساسية
        $settlement->update([
            'settlement_date' => $data['settlement_date'],
            'settlement_amount' => $data['settlement_amount'],
            'settlement_number' => $data['settlement_number'],
            'settlement_type' => $data['settlement_type'],
        ]);

        // تحديث الملف إذا تم رفع ملف جديد
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            // حذف الملف القديم
            if ($settlement->file_path && Storage::disk('tenant_public')->exists($settlement->file_path)) {
                Storage::disk('tenant_public')->delete($settlement->file_path);
            }

            // رفع الملف الجديد
            $fileData = $this->storeFile($data['file'], $settlement->customer_id);
            $settlement->update([
                'file_path' => $fileData['path'],
                'file_type' => $fileData['type'],
                'file_size' => $fileData['size'],
            ]);
        }

        return $settlement;
    }

    /**
     * حذف تسوية (حذف ناعم)
     */
    public function deleteSettlement(CustomerSettlement $settlement): bool
    {
        return $settlement->delete();
    }

    /**
     * جلب تسويات العميل
     */
    public function getCustomerSettlements(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return CustomerSettlement::where('customer_id', $customerId)
            ->with(['createdBy:id,name'])
            ->orderBy('settlement_date', 'desc')
            ->get();
    }

    /**
     * تخزين الملف
     */
    private function storeFile(UploadedFile $file, int $customerId): array
    {
        $directory = "customer_settlements/{$customerId}";
        $extension = $file->getClientOriginalExtension();
        $filename = 'settlement_' . time() . '_' . Str::random(8) . '.' . $extension;

        $path = $file->storeAs($directory, $filename, 'public');

        return [
            'path' => $path,
            'type' => strtolower($extension),
            'size' => $file->getSize(),
        ];
    }

    /**
     * أنواع التسويات المتاحة
     */
    public function getSettlementTypes(): array
    {
        return [
            'cash_with_update' => 'كاش بتحديث',
            'cash_without_update' => 'كاش بدون تحديث',
            'purchase_debt_with_update' => 'شراء مديونية بتحديث',
            'purchase_debt_without_update' => 'شراء مديونية بدون تحديث',
        ];
    }

    /**
     * إحصائيات بسيطة للتسويات
     */
    public function getSimpleStatistics(int $customerId): array
    {
        $settlements = CustomerSettlement::where('customer_id', $customerId);

        return [
            'total_count' => $settlements->count(),
            'active_count' => $settlements->whereNull('deleted_at')->count(),
            'total_amount' => $settlements->whereNull('deleted_at')->sum('settlement_amount'),
            'with_files' => $settlements->whereNull('deleted_at')->whereNotNull('file_path')->count(),
        ];
    }
}
