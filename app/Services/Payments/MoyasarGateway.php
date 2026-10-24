<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MoyasarGateway implements PaymentGateway
{
    public function fetchPayment(string $paymentId): array
    {
        $response = Http::timeout((int) config('services.moyasar.timeout', 15))
            ->withOptions(['verify' => (bool) config('services.moyasar.verify_ssl', true)])
            ->withBasicAuth($this->secretKey(), '')
            ->get(rtrim((string) config('services.moyasar.base_url'), '/').'/payments/'.$paymentId);

        if (! $response->successful()) {
            throw new RuntimeException('تعذر التحقق من نتيجة الدفع الإلكتروني.');
        }

        return $response->json() ?? [];
    }

    private function secretKey(): string
    {
        $mode = (string) config('services.moyasar.mode', 'test');
        $key = (string) config('services.moyasar.secret_key', '');
        $prefix = $mode === 'live' ? 'sk_live_' : 'sk_test_';

        if (! in_array($mode, ['test', 'live'], true) || ! str_starts_with($key, $prefix)) {
            throw new RuntimeException('إعدادات التحقق من الدفع الإلكتروني غير مكتملة أو غير متطابقة.');
        }

        return $key;
    }
}
