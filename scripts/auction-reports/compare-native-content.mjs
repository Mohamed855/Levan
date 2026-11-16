import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';
import { createCanvas } from '@napi-rs/canvas';

const directory = path.resolve('output/pdf/haram-native-content');
const referencePath = process.argv[2];
const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
const receipt = {};
try {
    for (const scenario of ['reference', 'many', 'long', 'partial']) {
        execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-native-content-proof.php', scenario], { stdio: 'pipe' });
        const page = await browser.newPage();
        await page.emulateMediaType('print');
        await page.setContent(readFileSync(path.join(directory, `${scenario}.html`), 'utf8'), { waitUntil: 'load' });
        await page.waitForFunction('window.haramReportReady === true');
        receipt[scenario] = await page.evaluate(() => ({
            error: window.haramReportError,
            pages: [...document.querySelectorAll('.auction-print-page')].map(page => ({
                type: page.dataset.pageType,
                title: page.querySelector('h1')?.textContent,
                rows: page.querySelectorAll('[data-native-result-table] tbody tr').length,
                geometry: [...page.querySelectorAll('[data-native-section], [data-native-marketing-image], .haram-summary-grid, .haram-tiktok-panel, .haram-native-board-frame')].map(node => {
                    const frame = page.getBoundingClientRect();
                    const box = node.getBoundingClientRect();
                    return { class: node.className, x: (box.x - frame.x) * .75, y: (box.y - frame.y) * .75, width: box.width * .75, height: box.height * .75 };
                }),
            })),
        }));
        if (receipt[scenario].error) throw new Error(`${scenario}: ${receipt[scenario].error}`);
        await page.pdf({ path: path.join(directory, `${scenario}.pdf`), preferCSSPageSize: true, printBackground: true });
        await page.close();
        const pdf = await getDocument({ data: new Uint8Array(readFileSync(path.join(directory, `${scenario}.pdf`))) }).promise;
        if (pdf.numPages !== receipt[scenario].pages.length) throw new Error(`${scenario}: physical PDF page count differs from measured pages`);
        receipt[scenario].pdf_pages = pdf.numPages;
        await pdf.destroy();
    }
} finally {
    await browser.close();
}

async function raster(pdfPath, pageNumber, output) {
    const pdf = await getDocument({ data: new Uint8Array(readFileSync(pdfPath)), useSystemFonts: true }).promise;
    try {
        const page = await pdf.getPage(pageNumber);
        const viewport = page.getViewport({ scale: 2 });
        const canvas = createCanvas(viewport.width, viewport.height);
        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        writeFileSync(path.join(directory, output), canvas.toBuffer('image/png'));
        return canvas;
    } finally { await pdf.destroy(); }
}

const sections = [];
for (const [family, pageNumber, label] of [['results', 3, 'ملخص المزاد'], ['marketing', 4, 'التسويق الرقمي'], ['board-before', 5, 'تركيب وفك اللوحات']]) {
    const index = receipt.reference.pages.findIndex(page => page.type === `haram-${family}`);
    const native = await raster(path.join(directory, 'reference.pdf'), index + 1, `${family}-native.png`);
    if (!referencePath) continue;
    const original = await raster(referencePath, pageNumber, `${family}-original.png`);
    const pair = createCanvas(original.width + native.width, Math.max(original.height, native.height));
    pair.getContext('2d').drawImage(original, 0, 0);
    pair.getContext('2d').drawImage(native, original.width, 0);
    writeFileSync(path.join(directory, `${family}-comparison.png`), pair.toBuffer('image/png'));
    const overlay = createCanvas(native.width, native.height);
    const context = overlay.getContext('2d');
    context.drawImage(original, 0, 0);
    context.globalAlpha = .5;
    context.drawImage(native, 0, 0);
    writeFileSync(path.join(directory, `${family}-overlay.png`), overlay.toBuffer('image/png'));
    const uri = file => `data:image/png;base64,${readFileSync(path.join(directory, file)).toString('base64')}`;
    sections.push(`<section id="${family}" ${sections.length ? 'hidden' : ''}><div class="pair"><article><h2>التنفيذ الجديد</h2><img src="${uri(`${family}-native.png`)}" alt="${label} بعد التعديل"></article><article><h2>المثال الأصلي</h2><img src="${uri(`${family}-original.png`)}" alt="${label} في المثال الأصلي"></article></div><details><summary>المقارنة بتراكب الشفافية</summary><label>شفافية التنفيذ الجديد <input type="range" min="0" max="100" value="50"></label><div class="sheet"><img src="${uri(`${family}-original.png`)}" alt="الأصلي"><img class="layer" src="${uri(`${family}-native.png`)}" alt="الجديد"></div></details></section>`);
}
if (sections.length) {
    writeFileSync(path.join(directory, 'review.html'), `<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>مقارنة صفحات مخرجات المزاد</title><style>*{box-sizing:border-box}body{margin:0;background:#edf1f6;color:#214570;font:16px/1.8 Tahoma,Arial,sans-serif}header,nav,main{max-width:1450px;margin:auto;padding:18px 24px}h1{margin:0;font-size:25px}h2{font-size:18px;text-align:center}nav{display:flex;gap:12px;flex-wrap:wrap}button{padding:8px 18px;border:1px solid #b5c6d7;border-radius:6px;background:white;color:inherit;font:inherit;cursor:pointer}button[aria-pressed=true]{background:#214570;color:white}.pair{display:grid;grid-template-columns:1fr 1fr;gap:22px}.pair img,.sheet img{display:block;width:100%;height:auto}.sheet{max-width:740px;margin:auto;position:relative}.layer{position:absolute;inset:0;opacity:.5}label{display:flex;gap:12px;padding:12px}input{flex:1}[hidden]{display:none!important}@media(max-width:700px){.pair{grid-template-columns:1fr}}</style><header><h1>مقارنة صفحات مخرجات المزاد</h1><p>بنفس بيانات المثال وصوره. الاختلافات المطلوبة: حذف الصفوف والأقسام الفارغة، المحاذاة لليمين والمسافات الداخلية، وتمدد المحتوى بحسب البيانات.</p></header><nav>${[['results', 'ملخص المزاد'], ['marketing', 'التسويق الرقمي'], ['board-before', 'تركيب وفك اللوحات']].map(([id, label], i) => `<button data-target="${id}" aria-pressed="${i === 0}">${label}</button>`).join('')}</nav><main>${sections.join('')}</main><script>document.querySelectorAll('button').forEach(button=>button.onclick=()=>{document.querySelectorAll('main>section').forEach(section=>section.hidden=section.id!==button.dataset.target);document.querySelectorAll('button').forEach(item=>item.setAttribute('aria-pressed',String(item===button)))});document.querySelectorAll('input').forEach(input=>input.oninput=()=>input.closest('details').querySelector('.layer').style.opacity=input.value/100);</script></html>`);
}
writeFileSync(path.join(directory, 'receipt.json'), JSON.stringify(receipt, null, 2));
console.log(JSON.stringify(Object.fromEntries(Object.entries(receipt).map(([scenario, data]) => [scenario, { pages: data.pdf_pages, error: data.error }]))));
