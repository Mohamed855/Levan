<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Forms;

use App\Services\Tenants\Auctions\BrowsershotDocumentRenderer;
use InvalidArgumentException;

/** Standalone print templates. No model, database, tenant or management-screen dependency. */
final class AuctionRegulatoryForms
{
    public function html(array $data, string $type): string
    {
        if (! in_array($type, ['establishment', 'closing'], true)) {
            throw new InvalidArgumentException('Unsupported regulatory form type.');
        }

        $properties = $this->rows($data['properties'] ?? []);
        $agents = $this->rows($data['agents'] ?? []);
        foreach ($properties as &$property) {
            $result = $property['result'] ?? '';
            if (! in_array($result, ['', 'awarded', 'not_awarded'], true)) {
                throw new InvalidArgumentException('Result must be awarded, not_awarded or blank.');
            }
            $property['result_label'] = match ($result) {
                'awarded' => 'تمت الترسية',
                'not_awarded' => 'لم تتم الترسية',
                default => '',
            };
            $property['awardee'] = $result === 'awarded' ? ($property['awardee'] ?? '') : '';
            $property['award_value'] = $result === 'awarded' ? $this->money($property['award_value'] ?? '') : '';
            $property['unawarded_reason'] = $result === 'not_awarded' ? ($property['unawarded_reason'] ?? '') : '';
        }
        unset($property);

        $pages = [];
        if ($type === 'establishment') {
            foreach (['organization', 'auction', 'property'] as $template) {
                $pages[] = ['template' => $template, 'orientation' => 'portrait', 'watermark' => count($pages) + 1];
            }
            foreach (array_chunk($properties ?: [[]], 14) as $index => $rows) {
                $pages[] = ['template' => 'properties', 'orientation' => 'landscape', 'watermark' => 4, 'rows' => array_pad($rows, 14, []), 'offset' => $index * 14];
            }
            foreach (array_chunk($agents ?: [[]], 4) as $index => $rows) {
                $pages[] = ['template' => 'agents', 'orientation' => 'portrait', 'watermark' => 5, 'rows' => array_pad($rows, 4, []), 'offset' => $index * 4];
            }
            $pages[] = ['template' => 'declaration', 'orientation' => 'portrait', 'watermark' => 6];
        } else {
            foreach (array_chunk($properties ?: [[]], 17) as $index => $rows) {
                $pages[] = ['template' => 'closing', 'orientation' => 'landscape', 'watermark' => 'closing', 'rows' => array_pad($rows, 17, []), 'offset' => $index * 17];
            }
        }

        $value = static function (string $key, mixed $default = '') use ($data): string {
            $result = data_get($data, $key, $default);
            if ($result === null) {
                return '';
            }
            if (! is_scalar($result)) {
                throw new InvalidArgumentException('Form values must be scalar.');
            }

            return (string) $result;
        };

        return view('tenant.auctions.regulatory-forms.document', [
            'type' => $type, 'pages' => $pages, 'data' => $data, 'value' => $value,
            'firstProperty' => $properties[0] ?? [],
            'css' => $this->css(), 'asset' => fn (string $name): string => $this->asset($name),
            'layout' => file_get_contents(resource_path('auction-regulatory-forms/layout.js')),
        ])->render();
    }

    public function pdf(array $data, string $type, string $outputPath): string
    {
        $html = $this->html($data, $type);

        return app(BrowsershotDocumentRenderer::class)->pdf($html, $outputPath);
    }

    private function rows(mixed $rows): array
    {
        if (! is_array($rows) || ! array_is_list($rows)) {
            throw new InvalidArgumentException('Form rows must be a list.');
        }
        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Each form row must be an array.');
            }
            foreach ($row as $value) {
                if ($value !== null && ! is_scalar($value)) {
                    throw new InvalidArgumentException('Row values must be scalar.');
                }
            }
        }

        return $rows;
    }

    private function money(mixed $value): string
    {
        if ($value === '' || $value === null) {
            return '';
        }
        if (! is_scalar($value) || ! preg_match('/^\d+(?:\.\d{1,2})?$/D', (string) $value)) {
            throw new InvalidArgumentException('Award value must be a non-negative decimal with at most two decimal places.');
        }
        [$integer, $fraction] = array_pad(explode('.', (string) $value, 2), 2, '');
        $integer = ltrim($integer, '0') ?: '0';

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $integer).'.'.str_pad($fraction, 2, '0');
    }

    private function css(): string
    {
        $css = '';
        foreach (['Regular' => 400, 'SemiBold' => 600, 'Bold' => 700, 'ExtraBold' => 800, 'Black' => 900] as $face => $weight) {
            $file = resource_path('auction-regulatory-forms/fonts/REGA-'.$face.'.ttf');
            $css .= '@font-face{font-family:REGA;src:url(data:font/ttf;base64,'.base64_encode(file_get_contents($file)).') format("truetype");font-weight:'.$weight.';font-display:block;}';
        }
        foreach ([
            ['FormData', 400, 'auction-reports/fonts/the-sans-plain.ttf'],
            ['FormSans', 400, 'auction-reports/fonts/the-sans-plain.ttf'],
            ['FormSans', 700, 'auction-reports/fonts/the-sans-bold.ttf'],
            ['FormPlex', 500, 'auction-reports/fonts/ibm-plex-sans-arabic.ttf'],
            ['FormPlex', 700, 'auction-regulatory-forms/fonts/IBMPlexSansArabic-Bold.ttf'],
        ] as [$family, $weight, $path]) {
            $css .= '@font-face{font-family:'.$family.';src:url(data:font/ttf;base64,'.base64_encode(file_get_contents(resource_path($path))).') format("truetype");font-weight:'.$weight.';font-display:block;}';
        }

        return $css.file_get_contents(resource_path('auction-regulatory-forms/forms.css'));
    }

    private function asset(string $name): string
    {
        if (! preg_match('/^(?:logo\.png|watermark-(?:[1-6]|closing)\.svg)$/D', $name)) {
            throw new InvalidArgumentException('Unknown form artwork.');
        }

        return 'data:image/'.(str_ends_with($name, '.png') ? 'png' : 'svg+xml').';base64,'.base64_encode(file_get_contents(resource_path('auction-regulatory-forms/'.$name)));
    }
}
