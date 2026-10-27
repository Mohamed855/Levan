<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Spatie\Browsershot\Browsershot;

require dirname(__DIR__, 2).'/vendor/autoload.php';
ini_set('memory_limit', '768M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$browser = Browsershot::html(file_get_contents(base_path('output/pdf/reference-fixtures/asir.html')))->timeout(180)->waitUntilNetworkIdle();
foreach (['node_binary' => 'setNodeBinary', 'npm_binary' => 'setNpmBinary', 'chrome_path' => 'setChromePath'] as $key => $method) {
    if ($value = config('auction-renderer.'.$key)) {
        $browser->{$method}($value);
    }
}
$result = $browser->evaluate(<<<'JS'
(() => JSON.stringify(Array.from(document.querySelectorAll('.auction-print-page')).map((page, index) => {
    const svg = page.querySelector('svg');
    const frames = {};
    const bounds = (box, matrix) => {
        const points = [[box.x,box.y],[box.x+box.width,box.y],[box.x,box.y+box.height],[box.x+box.width,box.y+box.height]].map(([x,y])=>new DOMPoint(x,y).matrixTransform(matrix));
        return [Math.min(...points.map(p=>p.x)),Math.min(...points.map(p=>p.y)),Math.max(...points.map(p=>p.x)),Math.max(...points.map(p=>p.y))];
    };
    for (const node of page.querySelectorAll('[data-report-media]')) {
        if (node.closest('defs') || node.dataset.reportMedia.startsWith('report_image:')) continue;
        const id = node.dataset.imageDef || node.dataset.image;
        if (!id) continue;
        let container = node.parentElement;
        while (container !== svg && !container.hasAttribute('clip-path')) container = container.parentElement;
        const relative = container.getCTM().inverse().multiply(node.getCTM());
        let box = bounds(node.getBBox(), relative);
        const clipId = container.getAttribute('clip-path')?.match(/^url\(#([^)]+)\)$/)?.[1];
        if (clipId) {
            const clip = document.getElementById(clipId);
            const boxes = Array.from(clip.children).filter(child => child.getBBox).map(child => bounds(child.getBBox(), child.transform.baseVal.consolidate()?.matrix || new DOMMatrix()));
            if (boxes.length) {
                const clipped = [Math.min(...boxes.map(b=>b[0])),Math.min(...boxes.map(b=>b[1])),Math.max(...boxes.map(b=>b[2])),Math.max(...boxes.map(b=>b[3]))];
                box = [Math.max(box[0],clipped[0]),Math.max(box[1],clipped[1]),Math.min(box[2],clipped[2]),Math.min(box[3],clipped[3])];
            }
        }
        if (box[2] <= box[0] || box[3] <= box[1]) throw new Error('Invalid image frame: '+id);
        const frame = {clip:clipId ? clipId.replace(`report_${index+1}_`, '') : null, box};
        frames[id] ??= [];
        if (!frames[id].some(value => JSON.stringify(value) === JSON.stringify(frame))) frames[id].push(frame);
    }
    return frames;
})))()
JS);
$frames = json_decode($result, true, flags: JSON_THROW_ON_ERROR);
if (count($frames) !== 21) {
    throw new RuntimeException('Image-frame measurement requires the 21-page Asir fixture.');
}
file_put_contents(resource_path('auction-reports/asir/image-frames.php'), "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($frames, true).";\n");
echo array_sum(array_map('count', $frames)).' clipped image frames measured.'.PHP_EOL;
