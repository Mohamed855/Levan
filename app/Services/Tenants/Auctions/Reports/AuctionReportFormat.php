<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use DateTimeImmutable;
use IntlDateFormatter;

final class AuctionReportFormat
{
    /** @var array<int, string> */
    private const ARABIC_MONTHS = [
        1 => 'يناير',
        2 => 'فبراير',
        3 => 'مارس',
        4 => 'أبريل',
        5 => 'مايو',
        6 => 'يونيو',
        7 => 'يوليو',
        8 => 'أغسطس',
        9 => 'سبتمبر',
        10 => 'أكتوبر',
        11 => 'نوفمبر',
        12 => 'ديسمبر',
    ];

    public function number(int|float|null $value, int $precision = 0): string
    {
        if ($value === null) {
            return '-';
        }

        return number_format($value, $precision, '.', ',');
    }

    public function currency(int|float|null $value): string
    {
        return $this->number($value, 2);
    }

    public function date(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return '-';
        }

        return sprintf('%d %s %d', (int) $date->format('j'), self::ARABIC_MONTHS[(int) $date->format('n')], (int) $date->format('Y'));
    }

    public function dateTime(?string $date, ?string $time): string
    {
        $parts = array_filter([
            $this->date($date),
            filled($time) ? trim($time) : null,
        ], static fn (?string $value): bool => filled($value) && $value !== '-');

        return $parts === [] ? '-' : implode(' - ', $parts);
    }

    public function arabicDigits(string $value): string
    {
        return strtr($value, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    }

    public function dateRange(?string $start, ?string $end): string
    {
        if ($this->date($start) !== '-' && $this->date($end) !== '-' && substr($start, 0, 7) === substr($end, 0, 7)) {
            return (new DateTimeImmutable($start))->format('j').' - '.$this->date($end);
        }

        return $this->date($start).' - '.$this->date($end);
    }

    public function time(?string $value): string
    {
        if (blank($value)) {
            return '-';
        }
        $time = DateTimeImmutable::createFromFormat(strlen($value) > 5 ? '!H:i:s' : '!H:i', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($time === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return '-';
        }

        return 'الساعة '.$time->format('h:i').' '.((int) $time->format('G') < 12 ? 'صباحًا' : 'مساءً');
    }

    public function duration(array $auction): string
    {
        if ($this->date($auction['start_date'] ?? null) === '-' || $this->date($auction['end_date'] ?? null) === '-') {
            return 'غير متوفر';
        }
        try {
            $timezone = new \DateTimeZone($auction['timezone'] ?? 'Asia/Riyadh');
            $start = new DateTimeImmutable($auction['start_date'].' '.($auction['start_time'] ?? '00:00'), $timezone);
            $end = new DateTimeImmutable($auction['end_date'].' '.($auction['end_time'] ?? '23:59'), $timezone);
            if ($end < $start) {
                return 'غير متوفر';
            }
            $days = (int) $start->setTime(0, 0)->diff($end->setTime(0, 0))->format('%a') + 1;

            return 'أيام المزاد: '.$this->arabicDigits((string) $days);
        } catch (\Exception) {
            return 'غير متوفر';
        }
    }

    public function hijriDate(?string $value): string
    {
        if ($this->date($value) === '-') {
            return '-';
        }
        $formatter = new IntlDateFormatter('ar_SA@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Riyadh', IntlDateFormatter::TRADITIONAL, 'yyyy/MM/dd');

        return $formatter->format(new DateTimeImmutable($value.'T12:00:00+03:00')) ?: '-';
    }

    public function dateParts(?string $value): array
    {
        if ($this->date($value) === '-') {
            return ['day' => '-', 'month' => '-', 'year' => '-', 'weekday' => '-'];
        }
        $date = new DateTimeImmutable($value);
        $weekdays = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

        return ['day' => $date->format('j'), 'month' => self::ARABIC_MONTHS[(int) $date->format('n')], 'year' => $date->format('Y'), 'weekday' => $weekdays[(int) $date->format('w')]];
    }
}
