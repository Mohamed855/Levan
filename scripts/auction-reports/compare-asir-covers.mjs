import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';

const root = 'output/pdf/asir-cover-fixes/';
const scenarios = process.argv.slice(2).length ? process.argv.slice(2) : ['normal', 'legacy', 'name-99', 'name-255-arabic', 'name-255-latin', 'contacts-long', 'phones-30', 'social-255', 'contacts-empty', 'white-logo', 'dark-logo', 'tall-logo', 'logos-empty', 'contacts-unicode', 'social-multiline'];
const receipt = {};
const review = [];
const escape = value => String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
for (const scenario of scenarios) {
    if (!/^[a-z0-9-]+$/.test(scenario)) throw new Error('Invalid proof scenario.');
    const variants = ['normal', 'legacy'].includes(scenario) ? ['original', 'native'] : ['native'];
    const shots = {};
    for (const variant of variants) {
        const suffix = variant === 'original' ? '-original' : '';
        const path = root + scenario + suffix + '.pdf';
        if (variant === 'native' || !existsSync(path)) {
            execFileSync(process.env.PHP_BINARY || 'php', ['scripts/auction-reports/render-asir-cover-proof.php', scenario, '--pdf', ...(variant === 'original' ? ['--baseline'] : [])], { stdio: 'pipe' });
        }
        const pdf = await getDocument({ data: new Uint8Array(readFileSync(path)), useSystemFonts: true }).promise;
        try {
            const pages = [1, pdf.numPages];
            shots[variant] = [];
            for (const [index, number] of pages.entries()) {
                const file = `${scenario}-${variant}-${index === 0 ? 'front' : 'closing'}.png`;
                // Poppler preserves the source PDF's luminosity masks. The
                // Node canvas PDF renderer flattens the legacy issuing logo.
                execFileSync('pdftoppm', ['-f', String(number), '-l', String(number), '-r', '108', '-singlefile', '-png', path, root + file.slice(0, -4)], { stdio: 'pipe' });
                shots[variant].push(file);
            }
            receipt[`${scenario}-${variant}`] = { pages: pdf.numPages, pdf: scenario + suffix + '.pdf', covers: shots[variant] };
            console.log(JSON.stringify({ scenario, variant, pages: pdf.numPages }));
        } finally { await pdf.destroy(); }
    }
    for (const [index, label] of ['الغلاف الأول', 'الغلاف الأخير'].entries()) {
        const images = shots.original ? [[shots.original[index], 'قبل'], [shots.native[index], 'بعد']] : [[shots.native[index], 'بعد الإصلاح']];
        review.push(`<section><h2>${escape(scenario)} — ${label}</h2><div class="pair">${images.map(([file, title]) => `<figure><figcaption>${title}</figcaption><a href="${file}"><img src="${file}" alt="${escape(scenario)} ${title}"></a></figure>`).join('')}</div><a href="${escape(scenario)}.pdf">PDF مُولّد بواسطة التطبيق</a></section>`);
    }
}
writeFileSync(root + 'receipt.json', JSON.stringify(receipt, null, 2));
writeFileSync(root + 'review.html', `<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>إصلاح غلافي Asir — قبل وبعد</title><style>body{font:17px/1.6 Arial,sans-serif;color:#183046;background:#eef2f5;margin:24px}section{max-width:1400px;margin:30px auto;padding:20px;background:white}.pair{display:flex;gap:20px;direction:ltr}figure{flex:1;min-width:0;margin:0}figcaption{text-align:center;font-weight:bold;padding:10px}img{width:100%;display:block}@media(max-width:800px){.pair{flex-direction:column}}</style><h1>إصلاح غلافي تقرير Asir</h1><p>الصور مرسومة من PDF مُولّد فعليًا بواسطة التطبيق. المقارنة تستخدم نفس البيانات في القالب الأصلي والنسخة المعدلة. الحالات الطويلة تُعرض كاملة بعد الإصلاح؛ القالب الأصلي يرفض توليدها.</p>${review.join('')}</html>`);
