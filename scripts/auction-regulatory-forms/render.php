<?php

declare(strict_types=1);

use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryForms;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $options = getopt('', ['type:', 'data:', 'output:', 'html-only', 'proofs']);
    $output = $options['output'] ?? base_path('output/pdf/auction-regulatory-forms');
    if (! is_dir($output) && ! mkdir($output, 0775, true) && ! is_dir($output)) {
        throw new RuntimeException('Cannot create output directory.');
    }
    $forms = app(AuctionRegulatoryForms::class);
    $cases = [];
    if (isset($options['proofs'])) {
        $example = json_decode(file_get_contents(resource_path('auction-regulatory-forms/example.json')), true, flags: JSON_THROW_ON_ERROR);
        $extended = $example;
        $extended['properties'] = [];
        for ($i = 1; $i <= 35; $i++) {
            $row = $example['properties'][($i - 1) % 2];
            $row['deed_number'] = '000'.str_pad((string) $i, 9, '0', STR_PAD_LEFT);
            $extended['properties'][] = $row;
        }
        $extended['agents'] = array_fill(0, 5, $example['agents'][0]);
        foreach (['establishment', 'closing'] as $type) {
            $cases[$type.'-blank'] = [$type, []];
            $cases[$type.'-example'] = [$type, $example];
            $cases[$type.'-continuation'] = [$type, $extended];
        }
    } else {
        $type = $options['type'] ?? 'establishment';
        $data = isset($options['data']) ? json_decode(file_get_contents($options['data']), true, flags: JSON_THROW_ON_ERROR) : [];
        if (! is_array($data)) {
            throw new InvalidArgumentException('JSON data must be an object.');
        }
        $cases[$type] = [$type, $data];
    }

    foreach ($cases as $name => [$type, $data]) {
        $html = $forms->html($data, $type);
        $path = $output.DIRECTORY_SEPARATOR.$name;
        file_put_contents($path.'.html', $html);
        if (! isset($options['html-only'])) {
            $forms->pdf($data, $type, $path.'.pdf');
        }
        echo json_encode(['case' => $name, 'initial_pages' => substr_count($html, 'data-form-page='), 'html' => $path.'.html'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
} catch (Throwable) {
    fwrite(STDERR, 'Form generation failed. Check the input contract, font/image loading and available print area.'.PHP_EOL);
    exit(1);
}
