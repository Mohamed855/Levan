<?php

declare(strict_types=1);

namespace App\Mail\Tenants;

use App\Mail\BaseMailable;
use App\Models\Admin\Tenants\Tenant;

final class TenantWelcomeMail extends BaseMailable
{
    public function __construct(
        public readonly Tenant $tenant,
        public readonly string $loginUrl,
        public readonly string $loginEmail,
        public readonly string $creationSource = 'subscription',
        public readonly ?string $temporaryPassword = null,
        public readonly ?int $trialDays = null,
        public readonly ?string $trialEndDate = null,
        public readonly ?string $password = null,
    ) {
        $this->onQueue('email');
    }

    public function build(): static
    {
        $subject = match ($this->creationSource) {
            'trial' => 'مرحباً بك في لوت LOT — تم تفعيل حسابك التجريبي وبيانات الدخول',
            'superadmin' => 'مرحباً بك في لوت LOT — تم إنشاء مساحة العمل وبيانات الدخول الخاصة بمنشأتك',
            default => 'مرحباً بك في لوت LOT — تم تفعيل مساحة العمل وبيانات الدخول الخاصة بمنشأتك',
        };

        $plainPassword = $this->password ?? $this->temporaryPassword;

        return $this->subject($subject)
            ->viewWithOffice('emails.tenants.welcome', [
                'tenant'            => $this->tenant,
                'companyName'       => $this->tenant->company_name ?? 'المنشأة الكريمة',
                'customerName'      => $this->tenant->primary_contact_name ?? 'عزيزنا العميل',
                'loginEmail'        => $this->loginEmail,
                'loginUrl'          => $this->loginUrl,
                'creationSource'    => $this->creationSource,
                'temporaryPassword' => $plainPassword,
                'password'          => $plainPassword,
                'trialDays'         => $this->trialDays,
                'trialEndDate'      => $this->trialEndDate,
            ]);
    }
}
