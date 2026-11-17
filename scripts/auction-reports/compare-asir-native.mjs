import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer';
import { createCanvas, loadImage } from '@napi-rs/canvas';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';

const directory = path.resolve('output/pdf/asir-native-content');
const scenarios = process.argv.slice(2).length ? process.argv.slice(2) : ['reference', 'populated', 'sparse', 'many', 'long'];
const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
const receipt = {};
const review = [];
try {
    for (const scenario of scenarios) {
        execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-native-proof.php', scenario], { stdio: 'pipe' });
        const variants = ['reference', 'populated'].includes(scenario) ? ['original', 'native'] : ['native'];
        if (variants.includes('original') && !existsSync(path.join(directory, `${scenario}-original.html`))) {
            execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-native-proof.php', scenario, '--baseline'], { stdio: 'pipe' });
        }
        const shots = {};
        for (const variant of variants) {
            if (variant === 'original' && existsSync(path.join(directory, `${scenario}-original-1.png`))) {
                const html = readFileSync(path.join(directory, `${scenario}-original.html`), 'utf8');
                const pages = [...html.matchAll(/data-page-type="([^"]+)"/g)].map(match => ({ type: match[1] }));
                shots.original = pages.map((page, index) => ({ ...page, file: `${scenario}-original-${index + 1}.png` }));
                receipt[`${scenario}-original`] = { error: null, pages, pdf_pages: pages.length };
                continue;
            }
            const page = await browser.newPage();
            await page.setViewport({ width: 900, height: 1200, deviceScaleFactor: 1 });
            await page.emulateMediaType('print');
            await page.setContent(readFileSync(path.join(directory, `${scenario}${variant === 'original' ? '-original' : ''}.html`), 'utf8'), { waitUntil: 'load' });
            await page.evaluate(() => document.fonts.ready);
            if (variant === 'native') await page.waitForFunction('window.asirReportReady === true || window.asirReportError !== null');
            const data = await page.evaluate(() => ({
                error: window.asirReportError ?? null,
                pages: [...document.querySelectorAll('.auction-print-page')].map(node => ({ type: node.dataset.pageType, title: node.querySelector('h1')?.textContent })),
                failingRows: [...document.querySelectorAll('.asir-native-page tr')].filter(row => row.getBoundingClientRect().bottom - row.closest('.auction-print-page').getBoundingClientRect().top > 1088).slice(0, 2).map(row => ({ type: row.closest('.auction-print-page').dataset.pageType, text: row.textContent.slice(0, 80), top: (row.getBoundingClientRect().top - row.closest('.auction-print-page').getBoundingClientRect().top) * .75, height: row.getBoundingClientRect().height * .75 })),
            }));
            receipt[`${scenario}-${variant}`] = data;
            if (data.error) { console.log(JSON.stringify({ scenario, ...data, pages: data.pages.length })); await page.close(); continue; }
            await page.pdf({ path: path.join(directory, `${scenario}-${variant}.pdf`), preferCSSPageSize: true, printBackground: true });
            const pdf = await getDocument({ data: new Uint8Array(readFileSync(path.join(directory, `${scenario}-${variant}.pdf`))) }).promise;
            data.pdf_pages = pdf.numPages;
            await pdf.destroy();
            if (data.pdf_pages !== data.pages.length) throw new Error(`Physical PDF page count differs: ${scenario}-${variant}`);
            if (variants.length > 1) {
                shots[variant] = [];
                const elements = await page.$$('.auction-print-page');
                for (let index = 0; index < elements.length; index++) {
                    const filename = `${scenario}-${variant}-${index + 1}.png`;
                    await elements[index].screenshot({ path: path.join(directory, filename) });
                    shots[variant].push({ file: filename, ...data.pages[index] });
                }
            }
            console.log(JSON.stringify({ scenario, variant, pages: data.pages.length, error: data.error }));
            await page.close();
        }
        if (!shots.native || !shots.original) continue;
        const pairs = [];
        for (const original of shots.original) {
            if (original.type === 'asir-cover' || original.type === 'asir-closing') continue;
            const native = shots.native.find(shot => shot.type === original.type);
            if (!native || pairs.some(pair => pair.type === original.type)) continue;
            pairs.push({ type: original.type, original: original.file, native: native.file });
        }
        for (let start = 0; start < pairs.length; start += 6) {
            const subset = pairs.slice(start, start + 6);
            const canvas = createCanvas(1200, subset.length * 460);
            const context = canvas.getContext('2d');
            context.fillStyle = '#e3e8ed'; context.fillRect(0, 0, canvas.width, canvas.height);
            for (const [offset, pair] of subset.entries()) {
                context.fillStyle = '#123'; context.font = '16px Arial';
                context.fillText(`${pair.type} — original / native`, 10, offset * 460 + 19);
                context.drawImage(await loadImage(path.join(directory, pair.original)), 0, offset * 460 + 28, 300, 424);
                context.drawImage(await loadImage(path.join(directory, pair.native)), 310, offset * 460 + 28, 300, 424);
                review.push(`<section><h2>${scenario} — ${pair.type}</h2><div class="pair"><img src="${pair.original}" alt="Original"><img src="${pair.native}" alt="Native"></div><details><summary>Overlay</summary><input type="range" min="0" max="100" value="50"><div class="overlay"><img src="${pair.original}" alt="Original"><img class="layer" src="${pair.native}" alt="Native"></div></details></section>`);
            }
            writeFileSync(path.join(directory, `${scenario}-contact-${start + 1}.png`), canvas.toBuffer('image/png'));
        }
    }
} finally { await browser.close(); }
writeFileSync(path.join(directory, 'receipt.json'), JSON.stringify(receipt, null, 2));
if (review.length) writeFileSync(path.join(directory, 'review.html'), `<!doctype html><html><meta charset="utf-8"><title>Asir original / native comparison</title><style>body{font:16px Arial;background:#e3e8ed;color:#123;margin:24px}section{max-width:1400px;margin:30px auto;background:white;padding:20px}.pair{display:grid;grid-template-columns:1fr 1fr;gap:20px}img{width:100%;display:block}.overlay{position:relative;max-width:800px;margin:auto}.layer{position:absolute;inset:0;opacity:.5}input{width:80%}</style><h1>Asir — original / native</h1>${review.join('')}<script>document.querySelectorAll('input').forEach(input=>input.oninput=()=>input.closest('details').querySelector('.layer').style.opacity=input.value/100)</script></html>`);
