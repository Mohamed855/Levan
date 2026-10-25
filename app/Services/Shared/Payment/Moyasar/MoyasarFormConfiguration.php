<?php

namespace App\Services\shared\Payment\Moyasar;

use App\Exceptions\Payment\PaymentInitiationException;

class MoyasarFormConfiguration
{
    public function getPublishableKey(): string
    {
        $mode = $this->getMode();
        $publishableKey = (string) config('services.moyasar.publishable', '');
        $expectedPrefix = $mode === 'live' ? 'pk_live_' : 'pk_test_';

        if (! str_starts_with($publishableKey, $expectedPrefix)) {
            throw new PaymentInitiationException('إعدادات الدفع الإلكتروني غير مكتملة أو لا تطابق بيئة الدفع الحالية.');
        }

        return $publishableKey;
    }

    public function getMode(): string
    {
        $mode = (string) config('services.moyasar.mode', 'test');

        if (! in_array($mode, ['test', 'live'], true)) {
            throw new PaymentInitiationException('وضع الدفع الإلكتروني غير صالح.');
        }

        return $mode;
    }

    public function amountInHalala(float $amount): int
    {
        $amountInHalala = (int) round($amount * 100);

        if ($amountInHalala < 100) {
            throw new PaymentInitiationException('المبلغ المطلوب للدفع الإلكتروني أقل من الحد الأدنى المسموح.');
        }

        return $amountInHalala;
    }
}
