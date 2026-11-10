<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\ViewErrorBag;

final class FlashFeedback
{
    /**
     * Map Laravel status keys to user-facing feedback messages.
     */
    private const STATUS_MESSAGES = [
        'verification-link-sent' => 'تم إرسال رابط تحقق جديد إلى عنوان البريد الإلكتروني الذي قدمته أثناء التسجيل.',
        'password-updated' => 'تم تحديث كلمة المرور بنجاح.',
    ];

    /**
     * Resolve body data-attributes consumed by the global toast notification script.
     *
     * @return array<string, string>
     */
    public static function attributes(?ViewErrorBag $errors = null): array
    {
        $resolvedErrors = $errors ?? (Session::get('errors') instanceof ViewErrorBag ? Session::get('errors') : null);
        $hasErrors = $resolvedErrors !== null && $resolvedErrors->any();

        $success = self::normalizeMessage(Session::get('success'));
        if ($success === '') {
            $status = self::normalizeMessage(Session::get('status'));
            if ($status !== '') {
                $success = self::STATUS_MESSAGES[$status] ?? $status;
            }
        }

        $error = self::normalizeMessage(Session::get('error'));
        if ($error === '' && $hasErrors) {
            $error = self::normalizeMessage($resolvedErrors->first());
        }

        return [
            'data-flash-success' => $success,
            'data-flash-error' => $error,
            'data-flash-info' => self::normalizeMessage(Session::get('info')),
            'data-flash-warning' => self::normalizeMessage(Session::get('warning')),
            'data-has-validation-errors' => $hasErrors ? 'true' : 'false',
        ];
    }

    private static function normalizeMessage(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }
}
