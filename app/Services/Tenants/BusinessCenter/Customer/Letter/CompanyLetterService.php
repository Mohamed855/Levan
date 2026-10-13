<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Letter;

use App\Mail\CompanyLetterMail;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * خدمة إدارة خطابات الشركات
 */
class CompanyLetterService
{
    /*
    |--------------------------------------------------------------------------
    | إنشاء خطاب جديد
    |--------------------------------------------------------------------------
    */
    public function createLetter(array $data): CompanyLetter
    {
        return DB::transaction(function () use ($data) {
            return CompanyLetter::create($data);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على استعلام الخطابات مع العلاقات
    |--------------------------------------------------------------------------
    */
    public function getLettersQuery()
    {
        return CompanyLetter::query()
            ->with([
                'customer:id,name,account_number,company_id,portfolio_id',
                'customer.company:id,name',
                'customer.portfolio:id,name',
                'customerFollow:id,contact_result_id,created_by',
                'customerFollow.contactResult:id,name',
                'customerFollow.createdBy:id,name',
                'collection:id,receipt_number,collected_amount',
                'sentBy:id,name'
            ])
            ->select([
                'id',
                'customer_id',
                'customer_follow_id',
                'collection_id',
                'action_type',
                'status',
                'sent_by',
                'sent_at',
                'created_at'
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر
    |--------------------------------------------------------------------------
    */
    public function applyFilters($query, array $filters)
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (!empty($filters['company_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('company_id', $filters['company_id']);
            });
        }

        if (!empty($filters['portfolio_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('portfolio_id', $filters['portfolio_id']);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على إحصائيات الخطابات
    |--------------------------------------------------------------------------
    */
    public function getStatistics(): array
    {
        $baseQuery = CompanyLetter::query();

        return [
            'total' => (clone $baseQuery)->count(),
            'ready_to_send' => (clone $baseQuery)->readyToSend()->count(),
            'sent' => (clone $baseQuery)->sent()->count(),
            'failed' => (clone $baseQuery)->failed()->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'by_action_type' => [
                'payment_full' => (clone $baseQuery)->where('action_type', 'payment_full')->count(),
                'payment_partial' => (clone $baseQuery)->where('action_type', 'payment_partial')->count(),
                'document_request' => (clone $baseQuery)->where('action_type', 'document_request')->count(),
                'recovery_request' => (clone $baseQuery)->where('action_type', 'recovery_request')->count(),
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على قوالب البريد حسب النوع
    |--------------------------------------------------------------------------
    */
    public function getTemplatesByType(string $actionType)
    {
        $category = match ($actionType) {
            'payment_full', 'payment_partial' => 'customers',
            'document_request' => 'company',
            'recovery_request' => 'company',
            default => 'general'
        };

        return MessageTemplate::active()
            ->where('category', $category)
            ->where('channel', 'email')
            ->select('id', 'name', 'content')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | معاينة القالب مع استبدال المتغيرات
    |--------------------------------------------------------------------------
    */
    public function previewTemplate(CompanyLetter $letter, int $templateId): array
    {
        $template = MessageTemplate::findOrFail($templateId);
        $customer = $letter->customer()->with([
            'company',
            'portfolio',
            'nationalityCountry',
            'relationshipManager',
            'customerStatus',
            'dynamicValues.portfolioField',
            'company.authorizedPeople' // إضافة هذه العلاقة لجلب المفوضين
        ])->first();

        $content = $template->content;

        // ═══ متغيرات العميل ═══
        $content = str_replace('{customer_name}', $customer->name ?? '', $content);
        $content = str_replace('{account_number}', $customer->account_number ?? '', $content);
        $content = str_replace('{identity_number}', $this->maskIdentityNumber($customer->identity_number ?? ''), $content);
        $content = str_replace('{phone}', $customer->phone ?? '', $content);
        $content = str_replace('{company_name}', $customer->company->name ?? '', $content);
        $content = str_replace('{portfolio_name}', $customer->portfolio->name ?? '', $content);
        $content = str_replace('{formatted_debt_amount}', number_format($customer->remaining_debt ?? 0, 2), $content);

        // ═══ متغيرات خاصة بالسداد ═══
        if ($letter->collection) {
            $content = str_replace('{receipt_number}', $letter->collection->receipt_number ?? '', $content);
            $content = str_replace('{collected_amount}', number_format($letter->collection->collected_amount ?? 0, 2), $content);
            $content = str_replace('{collection_date}', $letter->collection->collection_date ?? '', $content);
        }

        // ═══ متغيرات المفوضين ═══
        $content = $this->processAuthorizedPeopleVariables($content, $customer->company);

        // ═══ متغيرات التاريخ ═══
        $content = str_replace('{date}', now()->format('Y-m-d'), $content);
        $content = str_replace('{time}', now()->format('H:i'), $content);
        $content = str_replace('{datetime}', now()->format('Y-m-d H:i'), $content);

        // ═══ التأكد من عدم وجود متغيرات غير مستبدلة ═══
        $content = $this->removeUnprocessedVariables($content);

        return [
            'success' => true,
            'preview' => $content,
            'subject' => $template->name
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | معالجة متغيرات المفوضين
    |--------------------------------------------------------------------------
    */
    private function processAuthorizedPeopleVariables(string $content, ?Company $company): string
    {
        if (!$company) {
            return $this->removeAuthorizedPeopleVariables($content);
        }

        $authorizedPeople = $company->authorizedPeople()->orderBy('id')->get();

        if ($authorizedPeople->count() >= 1) {
            $person = $authorizedPeople[0];
            $content = str_replace('{authorized_1_name}', $person->name ?? '', $content);
            $content = str_replace('{authorized_1_phone}', $person->phone ?? '', $content);
            $content = str_replace('{authorized_1_email}', $person->email ?? '', $content);
            $content = str_replace('{authorized_1_position}', $person->position ?? '', $content);
        } else {
            $content = $this->removeAuthorizedPeopleVariables($content, 1);
        }

        if ($authorizedPeople->count() >= 2) {
            $person = $authorizedPeople[1];
            $content = str_replace('{authorized_2_name}', $person->name ?? '', $content);
            $content = str_replace('{authorized_2_phone}', $person->phone ?? '', $content);
            $content = str_replace('{authorized_2_email}', $person->email ?? '', $content);
            $content = str_replace('{authorized_2_position}', $person->position ?? '', $content);
        } else {
            $content = $this->removeAuthorizedPeopleVariables($content, 2);
        }

        if ($authorizedPeople->count() >= 3) {
            $person = $authorizedPeople[2];
            $content = str_replace('{authorized_3_name}', $person->name ?? '', $content);
            $content = str_replace('{authorized_3_phone}', $person->phone ?? '', $content);
            $content = str_replace('{authorized_3_email}', $person->email ?? '', $content);
            $content = str_replace('{authorized_3_position}', $person->position ?? '', $content);
        } else {
            $content = $this->removeAuthorizedPeopleVariables($content, 3);
        }

        return $content;
    }


    /*
    |--------------------------------------------------------------------------
    | إزالة متغيرات المفوضين غير المعالجة
    |--------------------------------------------------------------------------
    */
    private function removeAuthorizedPeopleVariables(string $content, ?int $authorizedNumber = null): string
    {
        if ($authorizedNumber === null) {
            $patterns = [
                '/\{authorized_\d+_name\}/',
                '/\{authorized_\d+_phone\}/',
                '/\{authorized_\d+_email\}/',
                '/\{authorized_\d+_position\}/',
            ];

            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }
        } else {
            $content = str_replace("{authorized_{$authorizedNumber}_name}", '', $content);
            $content = str_replace("{authorized_{$authorizedNumber}_phone}", '', $content);
            $content = str_replace("{authorized_{$authorizedNumber}_email}", '', $content);
            $content = str_replace("{authorized_{$authorizedNumber}_position}", '', $content);
        }

        return $content;
    }

    /*
    |--------------------------------------------------------------------------
    | إزالة أي متغيرات غير مستبدلة
    |--------------------------------------------------------------------------
    */
    private function removeUnprocessedVariables(string $content): string
    {
        return preg_replace('/\{[^}]+\}/', '', $content);
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال البريد
    |--------------------------------------------------------------------------
    */
    public function sendEmail(CompanyLetter $letter, array $data): array
    {
        return DB::transaction(function () use ($letter, $data) {
            try {
                $template = MessageTemplate::findOrFail($data['template_id']);
                $preview = $this->previewTemplate($letter, $data['template_id']);

                Mail::to($data['recipient_email'])->send(new CompanyLetterMail($preview));

                $letter->update([
                    'status'             => 'sent',
                    'email_template_id'  => $data['template_id'],
                    'sent_by'            => Auth::id(),
                    'sent_at'            => now(),
                    'sent_to'            => $data['recipient_email'],
                    'email_subject'      => $preview['subject'],
                    'email_body'         => $preview['preview'],
                ]);

                return [
                    'success' => true,
                    'message' => 'تم إرسال البريد بنجاح'
                ];
            } catch (\Exception $e) {
                $letter->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'message' => 'فشل إرسال البريد: ' . $e->getMessage()
                ];
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | إخفاء رقم الهوية (آخر 4 أرقام فقط)
    |--------------------------------------------------------------------------
    */
    private function maskIdentityNumber(string $identity): string
    {
        if (strlen($identity) > 4) {
            return str_repeat('*', strlen($identity) - 4) . substr($identity, -4);
        }
        return $identity;
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على تفاصيل الخطاب الكاملة
    |--------------------------------------------------------------------------
    */
    public function getLetterDetails(int $letterId): CompanyLetter
    {
        return CompanyLetter::with([
            'customer:id,name,account_number,identity_number,phone,company_id,portfolio_id',
            'customer.company:id,name',
            'customer.portfolio:id,name',
            'customerFollow:id,contact_result_id,contact_method_id,notes,created_by,created_at',
            'customerFollow.contactResult:id,name',
            'customerFollow.contactMethod:id,name',
            'customerFollow.createdBy:id,name',
            'customerFollow.fullPaymentDetails',
            'customerFollow.partialPaymentDetails',
            'customerFollow.documentRequestDetails',
            'customerFollow.documentRequestDetails.documentReason',
            'customerFollow.recoveryDetails',
            'collection:id,receipt_number,collected_amount,collection_date,payment_method_id',
            'collection.paymentMethod:id,name',
            'sentBy:id,name',
            'emailTemplate:id,name'
        ])->findOrFail($letterId);
    }
}
