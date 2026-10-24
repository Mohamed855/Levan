<?php

declare(strict_types=1);

namespace App\Services\Web;

use RuntimeException;

final class MoyasarFormConfiguration
{
    public function publishableKey(): string
    {
        $mode = $this->mode();
        $key = (string) config('services.moyasar.publishable_key', '');
        $prefix = $mode === 'live' ? 'pk_live_' : 'pk_test_';

        if (! str_starts_with($key, $prefix)) {
            throw new RuntimeException('إعدادات بوابة الدفع الإلكتروني غير مكتملة أو لا تطابق البيئة الحالية.');
        }

        return $key;
    }

    public function mode(): string
    {
        $mode = (string) config('services.moyasar.mode', 'test');
        if (! in_array($mode, ['test', 'live'], true)) {
            throw new RuntimeException('وضع بوابة الدفع الإلكتروني غير صالح.');
        }

        return $mode;
    }

    public function amountInHalala(float $amount): int
    {
        $halala = (int) round($amount * 100);
        if ($halala < 100) {
            throw new RuntimeException('المبلغ المطلوب أقل من الحد الأدنى للدفع الإلكتروني.');
        }

        return $halala;
    }
}
