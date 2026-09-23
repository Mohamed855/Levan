<?php

namespace App\Support;

final class ReportNumberFormatter
{
    public static function format(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if (!is_numeric($value)) {
            return (string) $value;
        }

        $number = (string) $value;

        if (stripos($number, 'e') !== false) {
            $number = sprintf('%.14F', (float) $value);
        }

        if (str_contains($number, '.')) {
            $number = rtrim(rtrim($number, '0'), '.');
        }

        return $number === '-0' ? '0' : $number;
    }
}
