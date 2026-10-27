<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Admin\Settings\Settings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

abstract class BaseMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Common office settings cache
     */
    protected ?Settings $officeSettings = null;

    /**
     * Resolve office data for emails
     */
    protected function getOfficeData(): array
    {
        if ($this->officeSettings === null) {
            try {
                $this->officeSettings = Settings::first();
            } catch (\Throwable $e) {
                $this->officeSettings = null;
            }
        }

        $appUrl = rtrim((string) config('app.url', 'https://levan.sa'), '/');
        $logoUrl = $this->officeSettings?->image 
            ? url('storage/' . $this->officeSettings->image) 
            : url('static/brand/logos/lot/trans-profile-white.png');

        return [
            'brandName'          => 'لوت Levan',
            'brandTagline'       => 'منصة إدارة المزادات والممتلكات العقارية الذكية',
            'brandLogo'          => $logoUrl,
            'officeName'         => $this->officeSettings?->office_name ?? config('app.name', 'لوت Levan'),
            'officeAddress'      => $this->officeSettings?->office_address ?? '',
            'commercialRegister' => $this->officeSettings?->commercial_register ?? '',
            'taxNumber'          => $this->officeSettings?->tax_number ?? '',
            'officePhone'        => $this->officeSettings?->phone_number ?? '',
            'officeEmail'        => $this->officeSettings?->email ?? config('mail.from.address', 'info@levan.sa'),
            'appUrl'             => $appUrl,
        ];
    }

    /**
     * Build view with shared office data
     */
    protected function viewWithOffice(string $view, array $data = []): static
    {
        return $this->view($view, array_merge($this->getOfficeData(), $data));
    }
}
