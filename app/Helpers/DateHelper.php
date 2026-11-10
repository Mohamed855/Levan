<?php

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

        if ($value instanceof \DateTimeInterface) {
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
        } catch (\Throwable) {
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

