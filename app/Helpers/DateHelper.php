<?php

use App\Helpers\Helpers;
use Carbon\Carbon;
use Carbon\CarbonInterface;

if (! function_exists('system_date_format')) {
    function system_date_format(): string
    {
        return 'd-m-Y';
    }
}

if (! function_exists('system_datetime_format')) {
    function system_datetime_format(): string
    {
        return 'd-m-Y h:i A';
    }
}

if (! function_exists('parse_system_date')) {
    function parse_system_date(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_int($value) || (is_string($value) && ctype_digit(trim($value)))) {
            return Carbon::createFromTimestamp((int) trim((string) $value));
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $trimmed) === 1) {
                return Carbon::createFromFormat('!Y/m/d', $trimmed);
            }

            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $trimmed) === 1) {
                return Carbon::createFromFormat('!d-m-Y', $trimmed);
            }

            return Carbon::parse($trimmed);
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('system_date')) {
    function system_date(mixed $value, string $fallback = '-'): string
    {
        $date = parse_system_date($value);

        return $date ? $date->format(system_date_format()) : $fallback;
    }
}

if (! function_exists('system_datetime')) {
    function system_datetime(mixed $value, string $fallback = '-', bool $arabicMeridiem = true): string
    {
        $date = parse_system_date($value);

        if (! $date) {
            return $fallback;
        }

        if ($arabicMeridiem) {
            return $date->copy()->locale('ar')->translatedFormat(system_datetime_format());
        }

        return $date->format(system_datetime_format());
    }
}

if (! function_exists('system_datetime_display')) {
    function system_datetime_display(mixed $date, mixed $time = null, string $fallback = '-'): string
    {
        $parsedDate = parse_system_date($date);
        if (! $parsedDate) {
            return $fallback;
        }

        $formattedDate = $parsedDate->format(system_date_format());

        $formattedTime = '';
        if ($time !== null && $time !== '') {
            $parsedTime = parse_system_date($time);
            $formattedTime = $parsedTime
                ? $parsedTime->copy()->locale('ar')->translatedFormat('h:i A')
                : (string) $time;
        } else {
            $hasTime = false;
            if ($date instanceof DateTimeInterface) {
                $hasTime = ($parsedDate->hour !== 0 || $parsedDate->minute !== 0 || $parsedDate->second !== 0);
            } elseif (is_string($date) && preg_match('/\d{1,2}:\d{2}/', $date) === 1) {
                $hasTime = true;
            }

            if ($hasTime) {
                $formattedTime = $parsedDate->copy()->locale('ar')->translatedFormat('h:i A');
            }
        }

        if ($formattedTime === '') {
            return '<div>'.e($formattedDate).'</div>';
        }

        return '<div>'.e($formattedDate).'</div><small class="text-muted">'.e($formattedTime).'</small>';
    }
}

if (! function_exists('riyal_icon')) {
    function riyal_icon(bool $dark = true): string
    {
        return Helpers::riyalIcon($dark);
    }
}

if (! function_exists('format_price')) {
    function format_price(mixed $amount, int $decimals = 2, bool $dark = true, string $fallback = '-'): string
    {
        return Helpers::formatPrice($amount, $decimals, $dark, $fallback);
    }
}
