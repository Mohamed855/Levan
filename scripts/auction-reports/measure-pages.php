<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Spatie\Browsershot\Browsershot;

require dirname(__DIR__, 2).'/vendor/autoload.php';
ini_set('memory_limit', '768M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$input = realpath($argv[1] ?? '');
if ($input === false || ! str_starts_with(str_replace('\\', '/', $input), str_replace('\\', '/', base_path()).'/output/pdf/')) {
    throw new InvalidArgumentException('Provide a report preview HTML under output/pdf.');
}
$browser = Browsershot::html(file_get_contents($input))->timeout(180)->waitUntilNetworkIdle()->waitForFunction('document.fonts.status === "loaded"');
foreach (['node_binary' => 'setNodeBinary', 'npm_binary' => 'setNpmBinary', 'chrome_path' => 'setChromePath'] as $key => $method) {
    if ($value = config('auction-renderer.'.$key)) {
        $browser->{$method}($value);
    }
}
if (in_array('--audit-images', $argv, true)) {
    echo $browser->evaluate(<<<'JS'
(async () => JSON.stringify(await Promise.all(Array.from(document.querySelectorAll('svg image')).map(async (node, index) => {
    const uri = node.getAttributeNS('http://www.w3.org/1999/xlink', 'href') || node.getAttribute('href');
    const image = new Image();
    image.src = uri;
    return {index, id:node.id, prefix:uri.slice(0,40), length:uri.length, result:await Promise.race([image.decode().then(()=> 'ready',error=>error.message),new Promise(resolve=>setTimeout(()=>resolve('timeout'),3000))])};
}))))()
JS).PHP_EOL;
    exit(0);
}
$result = $browser->evaluate(<<<'JS'
(async () => {
    await document.fonts.ready;
    return JSON.stringify(Array.from(document.querySelectorAll('.auction-print-page')).map((page, index) => {
        const frame = page.getBoundingClientRect();
        const svg = page.querySelector('svg');
        const scale = frame.width / svg.viewBox.baseVal.width;
        const regions = Array.from(page.querySelectorAll('[data-report-field], [data-report-cell], [data-report-media]')).map(node => {
            if (node.closest('defs')) return null;
            const box = node.getBoundingClientRect();
            const x = (box.left - frame.left) / scale;
            const y = (box.top - frame.top) / scale;
            const width = box.width / scale;
            const height = box.height / scale;
            const allowedWidth = Number(node.dataset.reportWidth || 0);
            return {
                id: node.dataset.reportField || node.dataset.reportCell || node.dataset.reportMedia,
                kind: node.hasAttribute('data-report-media') ? 'dynamic_image' : 'dynamic_text',
                text: node.textContent.trim(), x, y, width, height, allowedWidth,
                overWidth: allowedWidth > 0 && width > allowedWidth + 1,
                outsidePage: x < -1 || y < -1 || x + width > svg.viewBox.baseVal.width + 1 || y + height > svg.viewBox.baseVal.height + 1
            };
        }).filter(Boolean);
        return {page: index + 1, family: page.dataset.pageType, width: svg.viewBox.baseVal.width, height: svg.viewBox.baseVal.height, regions};
    }));
})()
JS);
$pages = json_decode($result, true, flags: JSON_THROW_ON_ERROR);
file_put_contents(substr($input, 0, -5).'.geometry.json', json_encode($pages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
$overflow = [];
foreach ($pages as $page) {
    foreach ($page['regions'] as $region) {
        if ($region['kind'] === 'dynamic_text' && ($region['overWidth'] || $region['outsidePage'])) {
            $overflow[] = ['page' => $page['page'], 'family' => $page['family']] + $region;
        }
    }
}
echo json_encode(['pages' => count($pages), 'overflow_count' => count($overflow), 'overflow' => $overflow], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
