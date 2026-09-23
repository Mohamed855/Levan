<?php

namespace App\Services\Tenants\Communication\MessageLog;

use App\Models\Tenants\Communication\MessageLog\MessageLog;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class MessageLogService
{
    /*
    |--------------------------------------------------------------------------
    | سجل رسالة
    |--------------------------------------------------------------------------
    */
    public function logMessage(array $data): MessageLog
    {
        $logData = $this->prepareLogData($data);

        return MessageLog::create($logData);
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث حالة الرسالة
    |--------------------------------------------------------------------------
    */
    public function updateMessageStatus(int $messageLogId, string $status, array $additionalData = []): bool
    {
        $messageLog = MessageLog::find($messageLogId);

        if (!$messageLog) {
            return false;
        }

        $updateData = ['status' => $status];

        switch ($status) {
            case 'sent':
                $updateData['sent_at'] = now();
                break;
            case 'failed':
                $updateData['failed_at'] = now();
                break;
            case 'processing':
                break;
        }

        $updateData = array_merge($updateData, $additionalData);

        return $messageLog->update($updateData);
    }

    /*
    |--------------------------------------------------------------------------
    | سجل رسالة للعميل
    |--------------------------------------------------------------------------
    */
    public function logCustomerMessage(
        string $messageContent,
        array $customerIds,
        string $status = 'pending',
        ?int $templateId = null,
        array $additionalData = []
    ): MessageLog {
        $recipientsData = $this->getCustomersWithAllPhonesForLog($customerIds);

        $data = array_merge([
            'message_content' => $messageContent,
            'message_type' => 'sms',
            'target_model' => 'customers',
            'recipient_ids' => $customerIds,
            'recipients_data' => $recipientsData,
            'recipients_count' => count($recipientsData),
            'status' => $status,
            'template_id' => $templateId,
        ], $additionalData);

        return $this->logMessage($data);
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على العملاء مع جميع أرقام الهواتف
    |--------------------------------------------------------------------------
    */
    private function getCustomersWithAllPhonesForLog(array $customerIds): array
    {
        $customers = Customer::whereIn('id', $customerIds)
            ->with(['phones' => function ($query) {
                $query->select('customer_id', 'phone', 'type', 'notes')
                    ->whereNotNull('phone')
                    ->where('phone', '!=', '');
            }])
            ->select('id', 'name', 'phone')
            ->get();

        $recipientsData = [];

        foreach ($customers as $customer) {
            if (!empty($customer->phone)) {
                $recipientsData[] = [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'type' => 'customer',
                    'phone_source' => 'primary',
                    'phone_type' => 'primary'
                ];
            }

            foreach ($customer->phones as $phoneRecord) {
                if (!empty($phoneRecord->phone) && $phoneRecord->phone !== $customer->phone) {
                    $recipientsData[] = [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $phoneRecord->phone,
                        'type' => 'customer',
                        'phone_source' => 'additional', // مصدر الرقم
                        'phone_type' => $phoneRecord->type ?? 'mobile',
                        'phone_notes' => $phoneRecord->notes ?? null
                    ];
                }
            }
        }

        return $recipientsData;
    }

    /*
    |--------------------------------------------------------------------------
    | سجل رسالة عامة
    |--------------------------------------------------------------------------
    */
    public function logGeneralMessage(
        string $messageContent,
        array $recipients,
        string $targetModel,
        string $status = 'pending',
        ?int $templateId = null,
        array $additionalData = []
    ): MessageLog {
        $data = array_merge([
            'message_content' => $messageContent,
            'message_type' => 'sms',
            'target_model' => $targetModel,
            'recipient_ids' => array_column($recipients, 'id'),
            'recipients_data' => $recipients,
            'recipients_count' => count($recipients),
            'status' => $status,
            'template_id' => $templateId,
        ], $additionalData);

        return $this->logMessage($data);
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على إحصائيات الرسائل
    |--------------------------------------------------------------------------
    */
    public function getMessageStats(array $filters = []): array
    {
        $query = MessageLog::query();

        $this->applyFilters($query, $filters);

        return [
            'total' => $query->count(),
            'sent' => (clone $query)->sent()->count(),
            'failed' => (clone $query)->failed()->count(),
            'pending' => (clone $query)->pending()->count(),
            'processing' => (clone $query)->where('status', 'processing')->count(),
            'success_rate' => $this->calculateSuccessRate($query),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على الرسائل الأخيرة مع إحصائيات الأرقام المتعددة
    |--------------------------------------------------------------------------
    */
    public function getRecentMessages(array $filters = []): Collection
    {
        $query = MessageLog::query();

        $this->applyFilters($query, $filters);

        $limit = $filters['limit'] ?? 10;

        return $query->with(['sender:id,name'])
            ->select([
                'id',
                'sender_id',
                'message_content',
                'recipients_count',
                'sent_count',
                'failed_count',
                'status',
                'created_at',
                'recipients_data',
                'target_model'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($message) {
                // إضافة إحصائيات مفصلة للأرقام المتعددة
                $recipientsData = $message->recipients_data ?? [];
                $message->phone_breakdown = $this->analyzeRecipientsPhones($recipientsData);
                return $message;
            });
    }

    /*
    |--------------------------------------------------------------------------
    | تحليل أرقام المستلمين
    |--------------------------------------------------------------------------
    */
    private function analyzeRecipientsPhones(array $recipientsData): array
    {
        $analysis = [
            'total_phones' => count($recipientsData),
            'primary_phones' => 0,
            'additional_phones' => 0,
            'unique_customers' => 0,
            'customers_with_multiple_phones' => 0
        ];

        $customerPhoneCounts = [];

        foreach ($recipientsData as $recipient) {
            $customerId = $recipient['id'] ?? null;
            $phoneSource = $recipient['phone_source'] ?? 'unknown';

            if ($customerId) {
                if (!isset($customerPhoneCounts[$customerId])) {
                    $customerPhoneCounts[$customerId] = 0;
                }
                $customerPhoneCounts[$customerId]++;
            }

            if ($phoneSource === 'primary') {
                $analysis['primary_phones']++;
            } elseif ($phoneSource === 'additional') {
                $analysis['additional_phones']++;
            }
        }

        $analysis['unique_customers'] = count($customerPhoneCounts);
        $analysis['customers_with_multiple_phones'] = count(
            array_filter($customerPhoneCounts, fn($count) => $count > 1)
        );

        return $analysis;
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على إحصائيات بحسب التاريخ
    |--------------------------------------------------------------------------
    */
    public function getStatsbyDate(string $period = 'week', array $filters = []): array
    {
        $query = MessageLog::query();
        $this->applyFilters($query, $filters);

        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfWeek(),
        };

        $data = $query->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent')
            ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            ->selectRaw('SUM(recipients_count) as total_recipients') // إجمالي المستلمين
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $data->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على أفضل القوالب استخداماً
    |--------------------------------------------------------------------------
    */
    public function getTopTemplates(int $limit = 10): array
    {
        return MessageLog::whereNotNull('template_id')
            ->selectRaw('template_id, template_name, COUNT(*) as usage_count')
            ->selectRaw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count')
            ->selectRaw('SUM(recipients_count) as total_recipients') // إجمالي المستلمين
            ->groupBy('template_id', 'template_name')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | تحضير بيانات السجل
    |--------------------------------------------------------------------------
    */
    private function prepareLogData(array $data): array
    {
        $prepared = [
            'sender_id' => $data['sender_id'] ?? Auth::id(),
            'sender_type' => $data['sender_type'] ?? 'user',
            'message_content' => $data['message_content'],
            'message_type' => $data['message_type'] ?? 'sms',
            'platform' => $data['platform'] ?? '4jawaly',
            'target_model' => $data['target_model'] ?? null,
            'recipient_ids' => $data['recipient_ids'] ?? [],
            'recipients_data' => $data['recipients_data'] ?? [],
            'recipients_count' => $data['recipients_count'] ?? 0,
            'status' => $data['status'] ?? 'pending',
            'template_id' => $data['template_id'] ?? null,
            'batch_id' => $data['batch_id'] ?? Str::uuid(),
            'reference_number' => $data['reference_number'] ?? $this->generateReferenceNumber(),
            'metadata' => $data['metadata'] ?? [],
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ];

        // إضافة معلومات إضافية من البيانات المرسلة
        if (isset($data['sent_count'])) $prepared['sent_count'] = $data['sent_count'];
        if (isset($data['failed_count'])) $prepared['failed_count'] = $data['failed_count'];
        if (isset($data['customers_reached'])) $prepared['customers_reached'] = $data['customers_reached'];
        if (isset($data['total_cost'])) $prepared['total_cost'] = $data['total_cost'];
        if (isset($data['response_data'])) $prepared['response_data'] = $data['response_data'];
        if (isset($data['status_message'])) $prepared['status_message'] = $data['status_message'];

        // إضافة اسم القالب إذا وجد
        if ($prepared['template_id']) {
            $template = MessageTemplate::find($prepared['template_id']);
            $prepared['template_name'] = $template?->name;
        }

        return $prepared;
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر على الاستعلام
    |--------------------------------------------------------------------------
    */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['message_type'])) {
            $query->where('message_type', $filters['message_type']);
        }

        if (!empty($filters['target_model'])) {
            $query->where('target_model', $filters['target_model']);
        }

        if (!empty($filters['sender_id'])) {
            $query->where('sender_id', $filters['sender_id']);
        }

        if (!empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('message_content', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('template_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('reference_number', 'like', '%' . $filters['search'] . '%');
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حساب معدل النجاح
    |--------------------------------------------------------------------------
    */
    private function calculateSuccessRate($query): float
    {
        $total = $query->count();
        $sent = (clone $query)->sent()->count();

        return $total > 0 ? round(($sent / $total) * 100, 2) : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | توليد رقم مرجعي فريد
    |--------------------------------------------------------------------------
    */
    private function generateReferenceNumber(): string
    {
        return 'MSG-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    /*
    |--------------------------------------------------------------------------
    | تنظيف الرسائل القديمة
    |--------------------------------------------------------------------------
    */
    public function cleanupOldMessages(int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return MessageLog::where('created_at', '<', $cutoffDate)
            ->where('status', '!=', 'pending')
            ->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | أرشفة الرسائل القديمة
    |--------------------------------------------------------------------------
    */
    public function archiveOldMessages(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return MessageLog::where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['sent', 'failed'])
            ->update(['deleted_at' => now()]);
    }
}
