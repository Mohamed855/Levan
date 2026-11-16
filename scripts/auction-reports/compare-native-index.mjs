import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';
import { createCanvas, loadImage } from '@napi-rs/canvas';

const directory = path.resolve('output/pdf/haram-native-index');
const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
const receipt = {};
try {
    for (const scenario of ['reference', 'many', 'long']) {
        execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-native-index-proof.php', scenario], { stdio: 'pipe' });
        const page = await browser.newPage();
        await page.emulateMediaType('print');
        await page.setContent(readFileSync(path.join(directory, `${scenario}.html`), 'utf8'), { waitUntil: 'load' });
        await page.waitForFunction('window.haramReportReady === true');
        receipt[scenario] = await page.evaluate(() => {
            const pages = [...document.querySelectorAll('.haram-native-index')];
            return {
                error: window.haramReportError,
                pages: pages.length,
                rows: pages.map(page => page.querySelector('[data-native-property-table] tbody').rows.length),
                geometry: pages.map(page => {
                    const frame = page.getBoundingClientRect();
                    return [...page.querySelectorAll('h2, .haram-index-project-frame, .haram-index-date-grid, .haram-index-property-frame')].map(node => {
                        const box = node.getBoundingClientRect();
                        return { name: node.className || node.textContent, x: (box.x - frame.x) * .75, y: (box.y - frame.y) * .75, width: box.width * .75, height: box.height * .75 };
                    });
                }),
            };
        });
        if (receipt[scenario].error) throw new Error(`${scenario}: ${receipt[scenario].error}`);
        await page.evaluate(() => document.querySelectorAll('.auction-print-page:not(.haram-native-index)').forEach(node => node.remove()));
        await page.pdf({ path: path.join(directory, `${scenario}.pdf`), preferCSSPageSize: true, printBackground: true });
        await page.close();
    }
} finally {
    await browser.close();
}

for (const scenario of Object.keys(receipt)) {
    const document = await getDocument({ data: new Uint8Array(readFileSync(path.join(directory, `${scenario}.pdf`))), useSystemFonts: true }).promise;
    try {
        receipt[scenario].pdf_pages = document.numPages;
        if (document.numPages !== receipt[scenario].pages) throw new Error(`${scenario}: physical PDF page count differs from the measured layout.`);
    } finally {
        await document.destroy();
    }
}

async function raster(pdfPath, pageNumber, output) {
    const document = await getDocument({ data: new Uint8Array(readFileSync(pdfPath)), useSystemFonts: true }).promise;
    try {
        const page = await document.getPage(pageNumber);
        const viewport = page.getViewport({ scale: 2 });
        const canvas = createCanvas(viewport.width, viewport.height);
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        writeFileSync(output, canvas.toBuffer('image/png'));
        return canvas;
    } finally {
        await document.destroy();
    }
}

const native = await raster(path.join(directory, 'reference.pdf'), 1, path.join(directory, 'native.png'));
const referencePdf = process.argv[2];
if (referencePdf) {
    await raster(path.resolve(referencePdf), 2, path.join(directory, 'original.png'));
    const original = await loadImage(path.join(directory, 'original.png'));
    const comparison = createCanvas(original.width + native.width, Math.max(original.height, native.height) + 42);
    const context = comparison.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, comparison.width, comparison.height);
    context.fillStyle = '#214570';
    context.font = '22px Arial';
    context.fillText('Original reference', 20, 29);
    context.fillText('Native HTML / CSS', original.width + 20, 29);
    context.drawImage(original, 0, 42);
    context.drawImage(native, original.width, 42);
    writeFileSync(path.join(directory, 'comparison.png'), comparison.toBuffer('image/png'));
    const overlay = createCanvas(native.width, native.height);
    const overlayContext = overlay.getContext('2d');
    overlayContext.drawImage(original, 0, 0);
    overlayContext.globalAlpha = .5;
    overlayContext.drawImage(native, 0, 0);
    writeFileSync(path.join(directory, 'overlay.png'), overlay.toBuffer('image/png'));
    const source = createCanvas(native.width, native.height);
    const sourceContext = source.getContext('2d');
    sourceContext.drawImage(original, 0, 0);
    const left = sourceContext.getImageData(0, 0, native.width, native.height);
    const right = native.getContext('2d').getImageData(0, 0, native.width, native.height);
    const difference = sourceContext.createImageData(native.width, native.height);
    for (let index = 0; index < left.data.length; index += 4) {
        const delta = Math.max(...[0, 1, 2].map(channel => Math.abs(left.data[index + channel] - right.data[index + channel])));
        difference.data[index] = delta > 12 ? 220 : 255;
        difference.data[index + 1] = delta > 12 ? Math.max(0, 180 - delta) : 255;
        difference.data[index + 2] = delta > 12 ? Math.max(0, 180 - delta) : 255;
        difference.data[index + 3] = 255;
    }
    sourceContext.putImageData(difference, 0, 0);
    writeFileSync(path.join(directory, 'difference.png'), source.toBuffer('image/png'));
    const uri = name => 'data:image/png;base64,' + readFileSync(path.join(directory, name)).toString('base64');
    const originalUri = uri('original.png');
    const nativeUri = uri('native.png');
    writeFileSync(path.join(directory, 'review.html'), `<!doctype html>
<html lang="ar" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>مراجعة عينة مخرجات المزاد</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#edf1f6;color:#214570;font:16px/1.8 Tahoma,Arial,sans-serif}header,nav,main{max-width:1450px;margin:auto;padding:18px 24px}h1{margin:0;font-size:25px}p{margin:8px 0}nav{display:flex;gap:10px;padding-top:0}button{border:1px solid #b5c6d7;background:white;color:#214570;border-radius:6px;padding:8px 18px;font:inherit;cursor:pointer}button[aria-pressed=true]{background:#214570;color:white}.pair{display:grid;grid-template-columns:1fr 1fr;gap:22px}.pair img,.sheet img{display:block;width:100%;height:auto}.label{text-align:center;padding:8px;background:white;font-weight:bold}.sheet{max-width:740px;margin:auto;position:relative;box-shadow:0 3px 18px #16324b25}.layer{position:absolute;inset:0;opacity:.5}.control{max-width:740px;margin:0 auto 20px;display:flex;gap:12px;align-items:center}.control input{flex:1}[hidden]{display:none!important}@media(max-width:700px){.pair{grid-template-columns:1fr}header,nav,main{padding-inline:14px}nav{flex-wrap:wrap}}
</style>
<header><h1>عينة اعتماد: صفحة مخرجات المزاد</h1><p>مقارنة التنفيذ بـHTML/CSS مع الصفحة الثانية من المثال الأصلي، باستخدام نفس بيانات العقارين.</p><p>اختلافات مقصودة: حذف الصفوف الفارغة، محاذاة بيانات العقارات لليمين مع مسافات داخلية، وإضافة الوقت. صورة الفروق تكشف جميع التغييرات، بما فيها هذه التصحيحات.</p></header>
<nav aria-label="طريقة المقارنة"><button data-view="pair" aria-pressed="true">جنبًا إلى جنب</button><button data-view="overlay" aria-pressed="false">تراكب بشفافية</button><button data-view="difference" aria-pressed="false">مواضع الفروق</button></nav>
<main><section id="pair" class="pair"><div><div class="label">التنفيذ الجديد — HTML/CSS</div><img src="${nativeUri}" alt="التنفيذ الجديد"></div><div><div class="label">المثال الأصلي</div><img src="${originalUri}" alt="المثال الأصلي"></div></section>
<section id="overlay" hidden><label class="control">شفافية التنفيذ الجديد<input id="opacity" type="range" min="0" max="100" value="50"><output id="percentage">50%</output></label><div class="sheet"><img src="${originalUri}" alt="المثال الأصلي"><img id="native-layer" class="layer" src="${nativeUri}" alt="التنفيذ الجديد فوق الأصل"></div></section>
<section id="difference" hidden><div class="sheet"><img src="${uri('difference.png')}" alt="خريطة مواضع اختلاف الصور"></div></section></main>
<script>document.querySelectorAll('[data-view]').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('[data-view]').forEach(item=>item.setAttribute('aria-pressed',String(item===button)));['pair','overlay','difference'].forEach(id=>document.getElementById(id).hidden=id!==button.dataset.view)}));document.getElementById('opacity').addEventListener('input',event=>{document.getElementById('native-layer').style.opacity=event.target.value/100;document.getElementById('percentage').textContent=event.target.value+'%'});</script></html>`);
}
writeFileSync(path.join(directory, 'receipt.json'), JSON.stringify(receipt, null, 2));
console.log(JSON.stringify(Object.fromEntries(Object.entries(receipt).map(([scenario, result]) => [scenario, { pages: result.pages, rows: result.rows, error: result.error }]))));
